<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class PgboLoginController2 extends Controller
{
    private string $origin = 'https://ct.pgbo.io';
    private string $basePath = '/casino-trader';
    private ?string $viewState = null;

    private function url(string $path): string
    {
        if (!str_starts_with($path, '/')) $path = '/' . $path;

        if (str_starts_with($path, $this->basePath . '/')) {
            return $this->origin . $path;
        }

        return $this->origin . $this->basePath . $path;
    }

    private function cookieFile(): string
    {
        return storage_path('app/ct_pgbo_cookie.txt');
    }

    private function debugFile(): string
    {
        return storage_path('logs/pgbo_debug.log');
    }

    private function dbg(string $step, array $data = []): void
    {
        $payload = '[' . now()->toDateTimeString() . "] {$step} " . json_encode($data, JSON_UNESCAPED_UNICODE);

        Log::info($payload);
        file_put_contents($this->debugFile(), $payload . PHP_EOL, FILE_APPEND);
    }

    private function cookieSnapshot(string $cookieFile): array
    {
        if (!file_exists($cookieFile)) return ['exists' => false];

        $content = file_get_contents($cookieFile);
        $short = mb_substr($content, 0, 2000);

        $jsession = null;
        if (preg_match('~JSESSIONID\s+([^\s]+)~', $content, $m)) {
            $jsession = $m[1];
        }

        return [
            'exists' => true,
            'size' => filesize($cookieFile),
            'jsessionid' => $jsession,
            'head' => $short,
        ];
    }

    private function jsfGet(string $url, string $cookieFile, array $headers = []): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_ENCODING       => '',
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_HTTPHEADER     => array_merge([
                'User-Agent: Mozilla/5.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Connection: keep-alive',
            ], $headers),
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err  = curl_error($ch);
            $erno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $this->dbg('CURL_GET_FAILED', [
                'url' => $url,
                'errno' => $erno,
                'error' => $err,
                'info' => $info,
            ]);

            throw new \RuntimeException("cURL GET failed ({$erno}): {$err}");
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        $headerSize = $info['header_size'] ?? 0;
        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        if (!is_string($body)) {
            $this->dbg('CURL_GET_BODY_NOT_STRING', [
                'url' => $url,
                'type' => gettype($body),
                'info' => $info,
            ]);
            $body = '';
        }

        return [$body, $info, $rawHeaders];
    }

    /**
     * PrimeFaces AJAX (partial-response) POST
     */
    private function jsfPost(string $url, array $fields, string $cookieFile, array $headers = []): string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_ENCODING       => '',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_HTTPHEADER     => array_merge([
                'User-Agent: Mozilla/5.0',
                'Accept: application/xml, text/xml, */*; q=0.01',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request: partial/ajax',
                'Origin: ' . $this->origin,
                'X-Requested-With: XMLHttpRequest',
                'Connection: keep-alive',
            ], $headers),
        ]);

        $body = curl_exec($ch);

        if ($body === false) {
            $err  = curl_error($ch);
            $erno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $this->dbg('CURL_POST_FAILED', [
                'url' => $url,
                'errno' => $erno,
                'error' => $err,
                'info' => $info,
            ]);

            throw new \RuntimeException("POST error ({$erno}): {$err}");
        }

        curl_close($ch);
        return $body;
    }

    /**
     * Normal form submit POST (login/otp gibi)
     */
    private function jsfPostForm(string $url, array $fields, string $cookieFile, array $headers = []): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,

            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),

            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,

            CURLOPT_ENCODING       => '',

            CURLOPT_HTTPHEADER     => array_merge([
                'User-Agent: Mozilla/5.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: ' . $this->origin,
                'Referer: ' . $this->url('/login.xhtml'),
                'Connection: keep-alive',
            ], $headers),
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err  = curl_error($ch);
            $erno = curl_errno($ch);
            $info = curl_getinfo($ch);
            $this->dbg('POST_FORM_ERROR', ['url' => $url, 'errno' => $erno, 'err' => $err, 'info' => $info]);
            curl_close($ch);
            throw new \RuntimeException("cURL POST error ({$erno}): {$err}");
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        $headerSize = $info['header_size'] ?? 0;
        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        return [$body, $info, $rawHeaders];
    }

    private function extractViewStateFromHtml(string $html): ?string
    {
        if (preg_match('~name="javax\.faces\.ViewState"[^>]*value="([^"]+)"~', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        return null;
    }

    private function extractViewStateFromPartial(string $xml): ?string
    {
        // id birebir aynı olmayabilir: gevşek yakalama
        if (preg_match('~<update[^>]+id="[^"]*javax\.faces\.ViewState[^"]*"[^>]*>\s*<!\[CDATA\[(.*?)\]\]>\s*</update>~s', $xml, $m)) {
            return trim($m[1]);
        }

        if (preg_match('~javax\.faces\.ViewState[^<]*<!\[CDATA\[(.*?)\]\]>\s*</update>~s', $xml, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function isOtpPage(string $html): bool
    {
        return str_contains($html, 'Tek kullanımlık şifre')
            || str_contains($html, 'name="j_idt43"')
            || str_contains($html, 'id="j_idt43"');
    }

    private function isLoginPage(string $html): bool
    {
        return str_contains($html, 'name="txtPassword"')
            || str_contains($html, 'id="txtPassword"')
            || (str_contains($html, 'Giriş') && str_contains($html, 'txtUsername'));
    }

    private function bodyFingerprint(string $html): array
    {
        $len = strlen($html);
        return [
            'len' => $len,
            'head' => substr($html, 0, 250),
            'tail' => substr($html, max(0, $len - 250)),
        ];
    }

    private function summarizePage(?string $html): array
    {
        if ($html === null) {
            return [
                'is_login' => false,
                'is_otp' => false,
                'viewState' => null,
                'len' => 0,
                'head' => '',
                'tail' => '',
                'note' => 'html is null',
            ];
        }

        return [
            'is_login' => $this->isLoginPage($html),
            'is_otp'   => $this->isOtpPage($html),
            'viewState' => $this->extractViewStateFromHtml($html),
            'len' => strlen($html),
            'head' => substr($html, 0, 250),
            'tail' => substr($html, max(0, strlen($html) - 250)),
        ];
    }

    private function ensureCustomersViewState(string $cookieFile): string
    {
        [$html, $info, $hdr] = $this->jsfGet($this->url('/customers.xhtml'), $cookieFile, [
            'Referer: ' . $this->url('/customers.xhtml'),
        ]);

        $vs = $this->extractViewStateFromHtml($html);
        if (!$vs) {
            $this->dbg('CUSTOMERS_VIEWSTATE_FAIL', [
                'http_code' => $info['http_code'] ?? null,
                'final_url' => $info['url'] ?? null,
                'fingerprint' => $this->bodyFingerprint($html),
            ]);
            throw new \RuntimeException('customers.xhtml içinden ViewState alınamadı');
        }

        $this->viewState = $vs;
        session(['pgbo.customers_viewstate' => $vs]);

        $this->dbg('CUSTOMERS_VIEWSTATE_OK', [
            'len' => strlen($vs),
            'http_code' => $info['http_code'] ?? null,
            'final_url' => $info['url'] ?? null,
        ]);

        return $vs;
    }

    private function looksLoggedIn(string $html, ?array $info = null): bool
    {
        // 1) Eğer login veya OTP sayfası marker'ı varsa ASLA logged-in değil
        if ($this->isLoginPage($html) || $this->isOtpPage($html)) {
            return false;
        }

        // 2) cURL final URL bilgisi geldiyse, customers’a gerçekten ulaştık mı kontrol et
        if ($info && !empty($info['url'])) {
            $finalUrl = (string) $info['url'];

            // login.xhtml'e redirect olduysa logged-in değil
            if (str_contains($finalUrl, '/login.xhtml')) {
                return false;
            }

            // customers.xhtml'e ulaştıysa büyük ihtimal logged-in
            if (str_contains($finalUrl, '/customers.xhtml')) {
                return true;
            }
        }

        // 3) HTML içindeki güçlü marker'lar (customers sayfasına özgü)
        $positiveMarkers = [
            'dtcustomers',                 // datatable id
            'PrimeFaces.cw("DataTable"',   // PF widget init
            'ui-datatable',
            'ui-menubar',
            'logout.xhtml',
            'Çıkış',
        ];

        foreach ($positiveMarkers as $marker) {
            if (str_contains($html, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function getSessionViewState(): ?string
    {
        return session('pgbo.customers_viewstate');
    }

    private function setSessionViewState(string $vs): void
    {
        session(['pgbo.customers_viewstate' => $vs]);
    }

    private function extractUpdateBlock(string $xml, string $updateId): ?string
    {
        // <update id="toolbar"><![CDATA[ ... ]]></update>
        $pattern = '~<update[^>]+id="' . preg_quote($updateId, '~') . '"[^>]*>\s*(?:<!\[CDATA\[(.*?)\]\]>|(.+?))\s*</update>~s';
        if (preg_match($pattern, $xml, $m)) {
            return isset($m[1]) && $m[1] !== '' ? $m[1] : ($m[2] ?? null);
        }
        return null;
    }

    private function ensureLoggedInOrRedirectToLogin(): ?\Illuminate\Http\RedirectResponse
    {
        $cookieFile = $this->cookieFile();

        [$html, $info, $hdr] = $this->jsfGet($this->url('/customers.xhtml'), $cookieFile, [
            'Referer: ' . $this->url('/customers.xhtml'),
        ]);

        // customers'a gidemiyorsak login'e düşmüşsündür
        if ($this->isLoginPage($html) || (isset($info['url']) && str_contains($info['url'], '/login.xhtml'))) {
            return redirect('/admin/pgbo/login');
        }

        // viewstate yakala (customers HTML)
        $vs = $this->extractViewStateFromHtml($html);
        if ($vs) $this->setSessionViewState($vs);

        return null;
    }

    public function parsePrimefacesDataTableToArray(string $rawResponse, string $tableBaseId = 'recordFrm:tabview:bonusesDt'): array
    {
        // 1) JSF partial-response içindeki update CDATA'sını yakala
        //    (Senin örnekte: <update id="recordFrm:tabview"><![CDATA[ ... ]]></update>)
        $updateHtml = null;

        // Önce en garanti: recordFrm:tabview update'ını ara
        if (preg_match('~<update\s+id="recordFrm:tabview"><!\[CDATA\[(.*?)\]\]></update>~s', $rawResponse, $m)) {
            $updateHtml = $m[1];
        } else {
            // Alternatif: herhangi bir update içinde tablo baseId geçiyorsa onu çek
            if (preg_match('~<update\s+id="[^"]+"><!\[CDATA\[(?:(?!\]\]></update>).)*' . preg_quote($tableBaseId, '~') . '.*?\]\]></update>~s', $rawResponse, $m2)) {
                // Bu regex komple update bloğunu dönebilir; tekrar CDATA içini ayıkla
                if (preg_match('~<!\[CDATA\[(.*?)\]\]>~s', $m2[0], $m3)) {
                    $updateHtml = $m3[1];
                }
            }
        }

        if ($updateHtml === null) {
            return [];
        }

        // 2) CDATA HTML'ini DOM'a yükle
        //    HTML parçalı gelebileceği için libxml hatalarını bastırıyoruz.
        $internalErrors = libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $html = '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $updateHtml . '</body></html>';
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $xpath = new DOMXPath($dom);

        // DOMXPath'ta id seçerken ":" kaçış ister; translate ile id eşleştirmek daha stabil
        // 3) Kolon başlıklarını al
        $headId = $tableBaseId . '_head';
        $headerNodes = $xpath->query(
            "//*[@id='$headId']//span[contains(@class,'ui-column-title')]"
        );

        $headers = [];
        if ($headerNodes) {
            foreach ($headerNodes as $node) {
                $title = trim(preg_replace('/\s+/', ' ', $node->textContent));
                $headers[] = $title !== '' ? $title : 'COL_' . (count($headers) + 1);
            }
        }

        // Header yoksa td sayısına göre generic key üretmek gerekebilir
        // 4) Satırları al
        $bodyId = $tableBaseId . '_data';
        $rowNodes = $xpath->query(
            "//*[@id='$bodyId']//tr"
        );

        if (!$rowNodes || $rowNodes->length === 0) {
            return [];
        }

        $rows = [];

        foreach ($rowNodes as $tr) {
            $cellNodes = (new DOMXPath($dom))->query("./td", $tr);
            if (!$cellNodes) {
                continue;
            }

            // Eğer header yoksa, bu satırın hücre sayısından header üret
            if (count($headers) === 0) {
                for ($i = 0; $i < $cellNodes->length; $i++) {
                    $headers[] = 'COL_' . ($i + 1);
                }
            }

            $rowAssoc = [];
            for ($i = 0; $i < $cellNodes->length; $i++) {
                $key = $headers[$i] ?? ('COL_' . ($i + 1));
                $td  = $cellNodes->item($i);

                // Checkbox var mı?
                $checkbox = (new DOMXPath($dom))->query(".//input[@type='checkbox']", $td);
                if ($checkbox && $checkbox->length > 0) {
                    /** @var DOMElement $cb */
                    $cb = $checkbox->item(0);
                    $rowAssoc[$key] = $cb->hasAttribute('checked'); // PrimeFaces bazen checked koymayabilir
                    continue;
                }

                // Link (a) içindeki metni al (bonus adı gibi)
                $a = (new DOMXPath($dom))->query(".//a", $td);
                if ($a && $a->length > 0) {
                    $val = trim(preg_replace('/\s+/', ' ', $a->item(0)->textContent));
                    $rowAssoc[$key] = $val;
                    continue;
                }

                // Normal text
                $val = trim(preg_replace('/\s+/', ' ', $td->textContent));
                $rowAssoc[$key] = $val;
            }

            // Boş satırsa ekleme
            if (count(array_filter($rowAssoc, fn($v) => $v !== '' && $v !== null)) > 0) {
                $rows[] = $rowAssoc;
            }
        }

        return $rows;
    }

    // -------------------- ROUTES --------------------

    public function postLogin()
    {
        /*$request->validate([
            'trader_code' => ['required', 'string'],
            'username'    => ['required', 'string'],
            'password'    => ['required', 'string'],
        ]);*/

        $cookieFile = $this->cookieFile();

        // Debug reset
        file_put_contents($this->debugFile(), "==== NEW RUN " . now()->toDateTimeString() . " ====\n");
        $this->dbg('COOKIE_BEFORE', $this->cookieSnapshot($cookieFile));

        // 0) Already logged in? (customers'a gerçekten ulaşabiliyor muyuz)
        [$cHtml, $cInfo, $cHdr] = $this->jsfGet($this->url('/customers.xhtml'), $cookieFile, [
            'Referer: ' . $this->url('/customers.xhtml'),
        ]);

        $this->dbg('PRECHECK_CUSTOMERS', [
            'http_code' => $cInfo['http_code'] ?? null,
            'final_url' => $cInfo['url'] ?? null,
            'is_login'  => $this->isLoginPage($cHtml),
            'is_otp'    => $this->isOtpPage($cHtml),
        ]);

        if ($this->looksLoggedIn($cHtml, $cInfo)) {
            $vs = $this->extractViewStateFromHtml($cHtml);
            if ($vs) session(['pgbo.customers_viewstate' => $vs]);
            $this->dbg('DECISION', ['next' => 'customers', 'reason' => 'already_logged_in_verified']);
            return redirect('/admin/pgbo/customers');
        }

        // 1) GET login.xhtml
        [$loginHtml, $info1, $hdr1] = $this->jsfGet($this->url('/login.xhtml'), $cookieFile, [
            'Referer: ' . $this->url('/login.xhtml'),
        ]);

        $this->dbg('STEP1_GET_LOGIN_INFO', [
            'http_code' => $info1['http_code'] ?? null,
            'final_url' => $info1['url'] ?? null,
        ]);
        $this->dbg('STEP1_GET_LOGIN_HEADERS', ['headers' => substr($hdr1, 0, 2000)]);
        $this->dbg('STEP1_GET_LOGIN_PAGE', $this->summarizePage($loginHtml));

        $viewState1 = $this->extractViewStateFromHtml($loginHtml);
        if (!$viewState1) {
            $this->dbg('STEP1_FAIL', ['reason' => 'ViewState not found']);
            return back()->withErrors(['msg' => 'Login sayfasından ViewState çekilemedi.']);
        }

        $this->dbg('COOKIE_AFTER_STEP1', $this->cookieSnapshot($cookieFile));

        // 2) POST login
        $post = [
            'frm' => 'frm',
            'txtTraderCode' =>  config('pronet.code'),
            'txtUsername'   => config('pronet.username'),
            'txtPassword'   => config('pronet.password'),
            'j_idt34'       => '', // login butonu
            'javax.faces.ViewState' => $viewState1,
        ];

        [$afterLogin, $info2, $hdr2] = $this->jsfPostForm($this->url('/login.xhtml'), $post, $cookieFile, [
            'Referer: ' . $this->url('/login.xhtml'),
        ]);

        $this->dbg('STEP2_POST_LOGIN_INFO', [
            'http_code' => $info2['http_code'] ?? null,
            'final_url' => $info2['url'] ?? null,
        ]);
        $this->dbg('STEP2_POST_LOGIN_HEADERS', ['headers' => substr($hdr2, 0, 2000)]);
        $this->dbg('STEP2_POST_LOGIN_PAGE', $this->summarizePage($afterLogin));

        $this->dbg('COOKIE_AFTER_STEP2', $this->cookieSnapshot($cookieFile));

        // 2.1) POST dönüşü OTP ise -> OTP sayfasına yönlendir
        if ($this->isOtpPage($afterLogin)) {
            $viewStateOtp = $this->extractViewStateFromHtml($afterLogin);

            $this->dbg('OTP_CAPTURED_FROM_LOGIN_POST', [
                'viewState_len' => $viewStateOtp ? strlen($viewStateOtp) : 0,
                'len' => strlen($afterLogin),
            ]);

            session([
                'pgbo.cookie_file' => $cookieFile,
                'pgbo.otp_html' => $afterLogin,
                'pgbo.otp_viewstate' => $viewStateOtp,
                'pgbo.otp_captured_at' => now()->timestamp,
            ]);

            return redirect('/admin/pgbo/otp');
        }

        // 2.2) Bazı sistemlerde OTP GET ile gelir: login.xhtml tekrar GET
        [$maybeOtp, $info3, $hdr3] = $this->jsfGet($this->url('/login.xhtml'), $cookieFile, [
            'Referer: ' . $this->url('/login.xhtml'),
        ]);

        $this->dbg('STEP3_GET_LOGIN_AGAIN_INFO', [
            'http_code' => $info3['http_code'] ?? null,
            'final_url' => $info3['url'] ?? null,
        ]);
        $this->dbg('STEP3_GET_LOGIN_AGAIN_HEADERS', ['headers' => substr($hdr3, 0, 2000)]);
        $this->dbg('STEP3_GET_LOGIN_AGAIN_PAGE', $this->summarizePage($maybeOtp));

        if ($this->isOtpPage($maybeOtp)) {
            $viewStateOtp = $this->extractViewStateFromHtml($maybeOtp);

            session([
                'pgbo.cookie_file' => $cookieFile,
                'pgbo.otp_html' => $maybeOtp,
                'pgbo.otp_viewstate' => $viewStateOtp,
                'pgbo.otp_captured_at' => now()->timestamp,
            ]);

            $this->dbg('DECISION', ['next' => 'redirect_otp', 'reason' => 'OTP page detected on GET after login']);
            return redirect('/admin/pgbo/otp');
        }

        // 2.3) Login başarısız mı?
        if ($this->isLoginPage($afterLogin)) {
            $this->dbg('DECISION', ['next' => 'back_login', 'reason' => 'still login page']);
            return back()->withErrors(['msg' => 'Login başarısız veya OTP state oluşmadı (log’a bakın).']);
        }

        // 2.4) Belki direkt customers'a geçti? (OTP olmadan)
        if ($this->looksLoggedIn($afterLogin, $info2)) {
            $this->dbg('DECISION', ['next' => 'customers', 'reason' => 'login_success_no_otp']);
            $vs = $this->extractViewStateFromHtml($afterLogin);
            if ($vs) session(['pgbo.customers_viewstate' => $vs]);
            return redirect('/admin/pgbo/customers');
        }

        $this->dbg('DECISION', ['next' => 'unknown', 'reason' => 'unexpected response']);
        return back()->withErrors(['msg' => 'Beklenmeyen login cevabı (log’a bakın).']);
    }
    public function showOtp()
    {
        return response()->view('admin.pgbo.otp');
    }

    public function postOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,8}$/'],
        ]);

        $cookieFile = $this->cookieFile();

        $otpHtml = session('pgbo.otp_html');
        $viewState2 = session('pgbo.otp_viewstate');

        $this->dbg('OTP_SESSION_READ', [
            'has_otp_html' => (bool)$otpHtml,
            'viewState_len' => $viewState2 ? strlen($viewState2) : 0,
            'captured_at' => session('pgbo.otp_captured_at'),
        ]);

        if (!$otpHtml || !$viewState2) {
            $this->dbg('OTP_FAIL', ['reason' => 'OTP HTML / ViewState missing in session']);
            return redirect('/admin/pgbo/login')->withErrors([
                'msg' => 'OTP state bulunamadı. Tekrar login yapın. (session boş)',
            ]);
        }

        $post = [
            'frm' => 'frm',
            'j_idt43' => $request->otp,
            'j_idt45' => '',
            'javax.faces.ViewState' => $viewState2,
        ];

        [$afterOtp, $info5, $hdr5] = $this->jsfPostForm($this->url('/login.xhtml'), $post, $cookieFile, [
            'Referer: ' . $this->url('/login.xhtml'),
        ]);

        $this->dbg('STEP5_POST_OTP_INFO', [
            'http_code' => $info5['http_code'] ?? null,
            'final_url' => $info5['url'] ?? null,
        ]);
        $this->dbg('STEP5_POST_OTP_PAGE', $this->summarizePage($afterOtp));

        if ($this->looksLoggedIn($afterOtp)) {
            $this->dbg('OTP_SUCCESS', ['msg' => 'looks logged in']);
            session()->forget(['pgbo.otp_html','pgbo.otp_viewstate','pgbo.otp_captured_at']);

            // customers viewstate yakala
            $this->ensureCustomersViewState($cookieFile);

            return redirect('/admin/pgbo/customers');
        }

        if ($this->isOtpPage($afterOtp)) {
            return back()->withErrors(['msg' => 'OTP hatalı veya süresi geçti.']);
        }

        if ($this->isLoginPage($afterOtp)) {
            return redirect('/admin/pgbo/login')->withErrors(['msg' => 'Oturum düştü, tekrar login olun.']);
        }

        return back()->withErrors(['msg' => 'Beklenmeyen OTP cevabı.']);
    }

    public function customers()
    {
        $cookieFile = $this->cookieFile();

        [$html, $info, $hdr] = $this->jsfGet($this->url('/customers.xhtml'), $cookieFile, [
            'Referer: ' . $this->url('/customers.xhtml'),
        ]);

        $this->dbg('CUSTOMERS_INFO', [
            'http_code' => $info['http_code'] ?? null,
            'final_url' => $info['url'] ?? null,
        ]);
        $this->dbg('CUSTOMERS_PAGE', $this->summarizePage($html));

        $vs = $this->extractViewStateFromHtml($html);
        if ($vs) {
            session(['pgbo.customers_viewstate' => $vs]);
        }

        return response($html);
    }

// ✅ PgboLoginController içindeki customersSelect() fonksiyonunu KOMPLE bununla değiştir
// (customer seç → hemen ardından bonusesDt_rows=200 isteği at → asıl response bunu döner)

    public function customersSelect(Request $request, string $id)
    {
        $cookieFile = $this->cookieFile();

        // ✅ NOT: Senin verdiğin curl’de Cookie header içinde aynı cookie’ler iki kere var.
        // Biz header ile Cookie basmayacağız; zaten cookie.txt ile doğru şekilde yönetiyoruz.
        // Aksi halde çakışma yaratıp “boş sonuç / null” durumlarını tetikleyebilir.

        // ViewState’i session’dan al, yoksa customers.xhtml GET ile çek
        $vs = session('pgbo.customers_viewstate');
        if (!$vs) {
            $vs = $this->ensureCustomersViewState($cookieFile);
        }

        // Ortak headers (curl’e en yakın)
        $headers = [
            'Accept: application/xml, text/xml, */*; q=0.01',
            'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Faces-Request: partial/ajax',
            'Origin: https://ct.pgbo.io',
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $this->url('/customers.xhtml'),
            'Connection: keep-alive',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ];

        // 1) rowDblselect (senin verdiğin curl)
        $fields1 = [
            'javax.faces.partial.ajax' => 'true',
            'javax.faces.source' => 'dtcustomers',
            'javax.faces.partial.execute' => 'dtcustomers',
            'javax.faces.partial.render' => 'recordFrm:recordDlgPanel',
            'javax.faces.behavior.event' => 'rowDblselect',
            'javax.faces.partial.event' => 'rowDblselect',
            'dtcustomers_instantSelectedRowKey' => $id,
            'frm' => 'frm',
            'dtcustomers_selection' => $id,
            'javax.faces.ViewState' => $vs,
        ];

        $xml1 = $this->jsfPost($this->url('/customers.xhtml'), $fields1, $cookieFile, $headers);
        $vs1 = $this->extractViewStateFromPartial($xml1);
        if ($vs1) {
            $vs = $vs1;
            session(['pgbo.customers_viewstate' => $vs]);
        }


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://ct.pgbo.io/casino-trader/customers.xhtml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'javax.faces.partial.ajax=true&javax.faces.source=dtcustomers&javax.faces.partial.execute=dtcustomers&javax.faces.partial.render=recordFrm%3ArecordDlgPanel&javax.faces.behavior.event=rowDblselect&javax.faces.partial.event=rowDblselect&dtcustomers_instantSelectedRowKey=18353955&frm=frm&dtcustomers_selection=18353955&javax.faces.ViewState=2843430633058005753%3A6851927013469250870',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/xml, text/xml, */*; q=0.01',
                'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request: partial/ajax',
                'Origin: https://ct.pgbo.io',
                'Pragma: no-cache',
                'Referer: https://ct.pgbo.io/casino-trader/customers.xhtml',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'X-Requested-With: XMLHttpRequest',
                'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Cookie: language-id=1; displayed-category-translations="12,1"; __nxquid=IFjEHQIuOCGBnBBP+pA+3RYdQr/BVg==0010; JSESSIONID=5f858f458245e346605c88d97195; JSESSIONID=650359548713a5988108896ce580; __nxquid=dk7fCgIuOCGBnBBP+pCPOhcdY03BVg==0010; language-id=1'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

        return response($response);
    }



    private function extractFormFieldsFromHtmlFragment(string $html): array
    {
        $fields = [];

        // 1) Standart form alanları (mevcut kodunuz)
        if (preg_match_all('~<(input|textarea|select)\b[^>]*\bname="([^"]+)"[^>]*>~i', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $tag = strtolower($row[1]);
                $name = html_entity_decode($row[2], ENT_QUOTES);

                if ($name === 'javax.faces.ViewState') continue;

                $value = '';

                if ($tag === 'input') {
                    if (preg_match('~\bvalue="([^"]*)"~i', $row[0], $vm)) {
                        $value = html_entity_decode($vm[1], ENT_QUOTES);
                    }
                } elseif ($tag === 'textarea') {
                    if (preg_match('~<textarea\b[^>]*\bname="' . preg_quote($row[2], '~') . '"[^>]*>(.*?)</textarea>~is', $html, $tm)) {
                        $value = html_entity_decode($tm[1], ENT_QUOTES);
                    }
                } elseif ($tag === 'select') {
                    if (preg_match('~<select\b[^>]*\bname="' . preg_quote($row[2], '~') . '"[^>]*>(.*?)</select>~is', $html, $sm)) {
                        $inner = $sm[1];
                        if (preg_match('~<option\b[^>]*selected[^>]*value="([^"]*)"~i', $inner, $om)) {
                            $value = html_entity_decode($om[1], ENT_QUOTES);
                        }
                    }
                }

                $fields[$name] = $value;
            }
        }

        // ✅ 2) PrimeFaces DataTable widgetVar state'lerini JavaScript'ten yakala
        // Örnek: PrimeFaces.cw("DataTable","widget_recordFrm_tabview_bonusesDt",{...})
        if (preg_match_all('~PrimeFaces\.cw\("DataTable"\s*,\s*"([^"]+)"\s*,\s*\{[^}]*id\s*:\s*"([^"]+)"~', $html, $dtMatches, PREG_SET_ORDER)) {
            foreach ($dtMatches as $dtm) {
                $dtId = $dtm[2]; // recordFrm:tabview:bonusesDt

                // DataTable için gerekli state alanlarını boş da olsa ekle
                $fields[$dtId . '_checkbox'] = '';
                $fields[$dtId . '_selection'] = '';
                $fields[$dtId . '_scrollState'] = '0,0';
            }
        }

        return $fields;
    }



}
