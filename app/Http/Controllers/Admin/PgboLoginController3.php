<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PgboLoginController3 extends Controller
{
    private string $origin = 'https://ct.pgbo.io';


    // ---------------------------
    // Helpers
    // ---------------------------

    private function url(string $path): string
    {
        if (!str_starts_with($path, '/')) $path = '/' . $path;

        // zaten /casino-trader ile gelirse dokunma
        if (str_starts_with($path, $this->basePath . '/')) {
            return $this->origin . $path;
        }

        return $this->origin . $this->basePath . $path;
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

    private function resetRunLog(): void
    {
        file_put_contents($this->debugFile(), "==== NEW RUN " . now()->toDateTimeString() . " ====\n");
    }

    // ---------------------------
    // Cookie (curl tarzı)
    // ---------------------------
    private function cookieRawGet(): string
    {
        return (string) session('pgbo.cookie_raw', 'language-id=1');
    }

    private function cookieRawSet(string $cookie): void
    {
        session(['pgbo.cookie_raw' => trim($cookie)]);
    }
    private function cookieRawAppendIfMissing(string $name, string $value): void
    {
        $raw = $this->cookieRawGet();

        // exact "name=value" zaten var mı?
        $needle = $name . '=' . $value;
        if (str_contains($raw, $needle)) {
            return;
        }

        if ($raw !== '' && !str_ends_with(trim($raw), ';')) {
            $raw .= '; ';
        }

        $raw .= $needle;

        $this->cookieRawSet($raw);
    }
    private function cookieRawUpdateFromHeaders(string $rawHeaders): void
    {
        preg_match_all('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/mi', $rawHeaders, $m, PREG_SET_ORDER);
        if (!$m) return;

        foreach ($m as $row) {
            $name = trim($row[1]);
            $value = trim($row[2]);
            if ($name === '') continue;

            $this->cookieRawAppendIfMissing($name, $value);
        }
    }

    private function cookieJarGet(): array
    {
        // cookie map: ['JSESSIONID'=>'...', '__nxquid'=>'...', ...]
        return session('pgbo.cookie_map', []);
    }

    private function cookieJarSet(array $map): void
    {
        session(['pgbo.cookie_map' => $map]);
    }

    private function cookieHeaderBuild(): string
    {
        return $this->cookieRawGet();
    }

    private function cookieJarUpdateFromRawHeaders(string $rawHeaders): void
    {
        // Set-Cookie satırlarını yakala (redirect zincirleri de dahil header text içinde olabilir)
        preg_match_all('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/mi', $rawHeaders, $m, PREG_SET_ORDER);

        if (!$m) return;

        $map = $this->cookieJarGet();

        foreach ($m as $row) {
            $name = trim($row[1]);
            $value = trim($row[2]);

            if ($name === '') continue;

            // bazı cookie değerleri boş gelebilir -> yine de yaz
            $map[$name] = $value;
        }

        $this->cookieJarSet($map);
    }

    private function cookieEntriesGet(): array
    {
        return session('pgbo.cookie_entries', []);
    }


    private function cookieEntriesSet(array $entries): void
    {
        session(['pgbo.cookie_entries' => array_values($entries)]);
    }

    private function cookieEntriesAddOrKeep(array $new): void
    {
        // Aynı name+value+path+domain varsa tekrar ekleme
        $entries = $this->cookieEntriesGet();

        foreach ($entries as $e) {
            if (
                ($e['name'] ?? '') === ($new['name'] ?? '') &&
                ($e['value'] ?? '') === ($new['value'] ?? '') &&
                ($e['path'] ?? '/') === ($new['path'] ?? '/') &&
                ($e['domain'] ?? '') === ($new['domain'] ?? '')
            ) {
                return;
            }
        }

        $entries[] = $new;
        $this->cookieEntriesSet($entries);
    }


    private function cookieEntriesUpdateFromHeaders(string $rawHeaders): void
    {
        // tüm Set-Cookie satırlarını yakala
        preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $rawHeaders, $m);
        if (empty($m[1])) return;

        foreach ($m[1] as $line) {
            // ör: JSESSIONID=abc; Path=/casino-trader; HttpOnly; Secure
            $parts = array_map('trim', explode(';', $line));
            if (empty($parts[0]) || !str_contains($parts[0], '=')) continue;

            [$name, $value] = explode('=', $parts[0], 2);
            $name = trim($name);
            $value = trim($value);

            $path = '/';
            $domain = ''; // yoksa boş kalsın

            foreach ($parts as $p) {
                if (stripos($p, 'Path=') === 0) {
                    $path = trim(substr($p, 5)) ?: '/';
                } elseif (stripos($p, 'Domain=') === 0) {
                    $domain = trim(substr($p, 7));
                }
            }

            if ($name !== '') {
                $this->cookieEntriesAddOrKeep([
                    'name' => $name,
                    'value' => $value,
                    'path' => $path,
                    'domain' => $domain,
                ]);
            }
        }
    }

    private function cookieHeaderBuildForUrl(string $url): string
    {
        $entries = $this->cookieEntriesGet();

        $u = parse_url($url);
        $reqPath = $u['path'] ?? '/';

        // defaults: tarayıcıdaki gibi sürekli dursun
        $this->cookieEntriesAddOrKeep(['name' => 'language-id', 'value' => '1', 'path' => '/', 'domain' => '']);
        $this->cookieEntriesAddOrKeep(['name' => 'displayed-category-translations', 'value' => '"12,1"', 'path' => '/', 'domain' => '']);

        $entries = $this->cookieEntriesGet();

        $matched = array_filter($entries, function ($c) use ($reqPath) {
            $p = $c['path'] ?? '/';
            if ($p === '') $p = '/';
            return str_starts_with($reqPath, $p);
        });

        // Path uzun olan öne gelsin
        usort($matched, function ($a, $b) {
            return strlen($b['path'] ?? '/') <=> strlen($a['path'] ?? '/');
        });

        $parts = [];
        foreach ($matched as $c) {
            $name = $c['name'] ?? '';
            $value = $c['value'] ?? '';
            if ($name === '') continue;
            $parts[] = $name . '=' . $value;
        }

        return implode('; ', $parts);
    }

    // ---------------------------
    // HTTP (header+body dön, cookie update)
    // ---------------------------

    /**
     * @return array{0:string,1:array,2:string} [body, info, rawHeaders]
     */
    /**
     * @return array{0:string,1:array,2:string} [body, info, rawHeadersAll]
     */
    private function http(string $method, string $url, array $headers = [], $postFields = null): array
    {
        $ch = curl_init($url);

        $rawHeadersAll = '';

        $baseHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
            'Connection: keep-alive',
            'Cookie: ' . $this->cookieHeaderBuildForUrl($url), // ✅ URL’e göre cookie üret
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => array_merge($baseHeaders, $headers),

            // ✅ tüm header satırlarını topla
            CURLOPT_HEADERFUNCTION => function ($ch, $headerLine) use (&$rawHeadersAll) {
                $rawHeadersAll .= $headerLine;
                return strlen($headerLine);
            },
        ];

        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = is_array($postFields)
                ? http_build_query($postFields)
                : (string)$postFields;
        }

        curl_setopt_array($ch, $opts);

        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            $erno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL failed ({$erno}): {$err}");
        }

        $info = curl_getinfo($ch);
        curl_close($ch);

        // ✅ redirect zincirindeki TÜM Set-Cookie’leri yakala
        $this->cookieEntriesUpdateFromHeaders($rawHeadersAll);

        return [$body, $info, $rawHeadersAll];
    }


    // ---------------------------
    // JSF ViewState
    // ---------------------------

    private function extractViewStateFromHtml(string $html): ?string
    {
        if (preg_match('~name="javax\.faces\.ViewState"[^>]*value="([^"]+)"~', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        return null;
    }

    private function extractViewStateFromPartial(string $xml): ?string
    {
        if (preg_match('~<update id="j_id1:javax\.faces\.ViewState:0"><!\[CDATA\[(.*?)\]\]></update>~s', $xml, $m)) {
            return trim($m[1]);
        }
        if (preg_match('~javax\.faces\.ViewState:0"><!\[CDATA\[(.*?)\]\]></update>~s', $xml, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function viewStateGet(): ?string
    {
        return session('pgbo.viewstate');
    }

    private function viewStateSet(?string $vs): void
    {
        if ($vs) session(['pgbo.viewstate' => $vs]);
    }

    // ---------------------------
    // Page detectors
    // ---------------------------

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

    private function looksLoggedIn(string $html): bool
    {
        $positiveMarkers = [
            'customers.xhtml',
            'recordFrm',
            'PrimeFaces.cw("DataTable"',
            'ui-datatable',
            'ui-menubar',
            'ui-layout',
        ];

        foreach ($positiveMarkers as $marker) {
            if (str_contains($html, $marker)) return true;
        }

        if ($this->isLoginPage($html) || $this->isOtpPage($html)) return false;

        if (str_contains($html, 'logout.xhtml') || str_contains($html, 'Çıkış')) return true;

        return false;
    }

    // ---------------------------
    // Endpoints
    // ---------------------------

    // GET /admin/pgbo/login  -> env’den login at, OTP’ye yönlendir
    public function login()
    {
        $this->resetRunLog();

        // 0) Eğer zaten login ise direkt customers’a git
        try {
            [$cHtml, $cInfo, $cHdr] = $this->http('GET', $this->url('/customers.xhtml'), [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Referer: ' . $this->url('/customers.xhtml'),
            ]);

            $this->dbg('PRECHECK_CUSTOMERS', [
                'http_code' => $cInfo['http_code'] ?? null,
                'final_url' => $cInfo['url'] ?? null,
                'looks_logged_in' => $this->looksLoggedIn($cHtml),
                'cookie_now' => $this->cookieHeaderBuild(),
            ]);

            if ($this->looksLoggedIn($cHtml)) {
                $vs = $this->extractViewStateFromHtml($cHtml);
                $this->viewStateSet($vs);
                return redirect('/admin/pgbo/customers');
            }
        } catch (\Throwable $e) {
            $this->dbg('PRECHECK_ERR', ['msg' => $e->getMessage()]);
        }

        // 1) GET login.xhtml (ViewState al)
        [$loginHtml, $info1, $hdr1] = $this->http('GET', $this->origin . '/login.xhtml', [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $this->origin . '/login.xhtml',
        ]);

        $vs1 = $this->extractViewStateFromHtml($loginHtml);
        $this->dbg('STEP1_GET_LOGIN', [
            'http_code' => $info1['http_code'] ?? null,
            'final_url' => $info1['url'] ?? null,
            'vs_found' => (bool)$vs1,
            'cookie_now' => $this->cookieHeaderBuild(),
            'head_800' => substr($loginHtml, 0, 800),
        ]);

        if (!$vs1) {
            return response('Login sayfasından ViewState alınamadı', 500);
        }

        // 2) POST login (env’den)
        $post = [
            'frm' => 'frm',
            'txtTraderCode' => config('pronet.code'),
            'txtUsername'   => config('pronet.username'),
            'txtPassword'   => config('pronet.password'),
            'j_idt34'       => '',
            'javax.faces.ViewState' => $vs1,
        ];

        [$afterLogin, $info2, $hdr2] = $this->http('POST', $this->origin . '/login.xhtml', [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . $this->origin,
            'Referer: ' . $this->origin . '/login.xhtml',
        ], $post);

        $this->dbg('STEP2_POST_LOGIN', [
            'http_code' => $info2['http_code'] ?? null,
            'final_url' => $info2['url'] ?? null,
            'is_otp' => $this->isOtpPage($afterLogin),
            'is_login' => $this->isLoginPage($afterLogin),
            'cookie_now' => $this->cookieHeaderBuild(),
            'head_1200' => substr($afterLogin, 0, 1200),
        ]);

        // OTP ekranı geldiyse otp sayfasına geç
        if ($this->isOtpPage($afterLogin)) {
            $vsOtp = $this->extractViewStateFromHtml($afterLogin);

            session([
                'pgbo.otp_viewstate' => $vsOtp,
                'pgbo.otp_captured_at' => now()->timestamp,
            ]);

            return redirect('/admin/pgbo/otp');
        }

        // bazen redirect/get ile otp’ye düşüyor
        [$maybeOtp, $info3, $hdr3] = $this->http('GET', $this->origin . '/login.xhtml', [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $this->origin . '/login.xhtml',
        ]);

        $this->dbg('STEP3_GET_LOGIN_AGAIN', [
            'http_code' => $info3['http_code'] ?? null,
            'final_url' => $info3['url'] ?? null,
            'is_otp' => $this->isOtpPage($maybeOtp),
            'cookie_now' => $this->cookieHeaderBuild(),
            'head_1200' => substr($maybeOtp, 0, 1200),
        ]);

        if ($this->isOtpPage($maybeOtp)) {
            $vsOtp = $this->extractViewStateFromHtml($maybeOtp);
            session([
                'pgbo.otp_viewstate' => $vsOtp,
                'pgbo.otp_captured_at' => now()->timestamp,
            ]);

            return redirect('/admin/pgbo/otp');
        }

        return response('Login sonrası OTP state oluşmadı (logları kontrol et).', 500);
    }

    public function showOtp()
    {
        return response()->view('admin.pgbo.otp');
    }

    // POST /admin/pgbo/otp
    public function postOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{4,8}$/'],
        ]);

        $vsOtp = session('pgbo.otp_viewstate');
        if (!$vsOtp) {
            return redirect('/admin/pgbo/login')->withErrors(['msg' => 'OTP ViewState yok. Tekrar login ol.']);
        }

        $post = [
            'frm' => 'frm',
            'j_idt43' => $request->otp,
            'j_idt45' => '',
            'javax.faces.ViewState' => $vsOtp,
        ];

        [$afterOtp, $info, $hdr] = $this->http('POST', $this->origin . '/login.xhtml', [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Content-Type: application/x-www-form-urlencoded',
            'Origin: ' . $this->origin,
            'Referer: ' . $this->origin . '/login.xhtml',
        ], $post);

        $this->dbg('OTP_POST', [
            'http_code' => $info['http_code'] ?? null,
            'final_url' => $info['url'] ?? null,
            'looks_logged_in' => $this->looksLoggedIn($afterOtp),
            'cookie_now' => $this->cookieHeaderBuild(),
            'head_1200' => substr($afterOtp, 0, 1200),
        ]);

        if ($this->looksLoggedIn($afterOtp)) {
            session()->forget(['pgbo.otp_viewstate', 'pgbo.otp_captured_at']);
            return redirect('/admin/pgbo/customers');
        }

        return back()->withErrors(['msg' => 'OTP başarısız veya oturum düştü (logları kontrol et).']);
    }

    // GET /admin/pgbo/customers
    public function customers()
    {
        [$html, $info, $hdr] = $this->http('GET', $this->url('/customers.xhtml'), [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $this->url('/customers.xhtml'),
        ]);

        $vs = $this->extractViewStateFromHtml($html);
        $this->viewStateSet($vs);

        $this->dbg('CUSTOMERS_GET', [
            'http_code' => $info['http_code'] ?? null,
            'final_url' => $info['url'] ?? null,
            'vs_len' => $vs ? strlen($vs) : 0,
            'cookie_now' => $this->cookieHeaderBuild(),
        ]);

        return response($html);
    }

    // GET /admin/pgbo/customer-select?id=18353955
    public function customerSelect(Request $request)
    {

        //$this->cookieRawSet('language-id=1; displayed-category-translations="12,1"; __nxquid=IFjEHQIuOCGBnBBP+pA+3RYdQr/BVg==0010; JSESSIONID=5f858f458245e346605c88d97195; JSESSIONID=650359548713a5988108896ce580; __nxquid=dk7fCgIuOCGBnBBP+pCPOhcdY03BVg==0010; language-id=1');

        $id = (string)($request->get('id', '18353955'));

        // ViewState gerek
        $vs = $this->viewStateGet();
        if (!$vs) {
            // customers sayfasına git ve al
            [$html] = $this->http('GET', $this->url('/customers.xhtml'), [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Referer: ' . $this->url('/customers.xhtml'),
            ]);
            $vs = $this->extractViewStateFromHtml($html);
            $this->viewStateSet($vs);
        }

        if (!$vs) {
            return response('customers.xhtml ViewState bulunamadı', 500);
        }
        //$vs = "2843430633058005753:6851927013469250870";

        // ✅ Senin çalışan curl’ünle birebir aynı yaklaşım:
        // - string body (encoded) gibi göndermek istersen: aşağıdaki satırla tamamen aynı yaparız.
        $postFields = 'javax.faces.partial.ajax=true'
            . '&javax.faces.source=dtcustomers'
            . '&javax.faces.partial.execute=dtcustomers'
            . '&javax.faces.partial.render=recordFrm%3ArecordDlgPanel'
            . '&javax.faces.behavior.event=rowDblselect'
            . '&javax.faces.partial.event=rowDblselect'
            . '&dtcustomers_instantSelectedRowKey=' . rawurlencode($id)
            . '&frm=frm'
            . '&dtcustomers_selection=' . rawurlencode($id)
            . '&javax.faces.ViewState=' . rawurlencode($vs);

        [$xml, $info, $hdr] = $this->http('POST', $this->url('/customers.xhtml'), [
            'Accept: application/xml, text/xml, */*; q=0.01',
            'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Faces-Request: partial/ajax',
            'Origin: ' . $this->origin,
            'Referer: ' . $this->url('/customers.xhtml'),
            'X-Requested-With: XMLHttpRequest',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ], $postFields);

        // ViewState update (partial-response)
        $newVs = $this->extractViewStateFromPartial($xml);
        if ($newVs) $this->viewStateSet($newVs);

        // ✅ her işlemden sonra XML log (tamamı çok büyükse başını logluyoruz)
        $this->dbg('CUSTOMERSELECT_ROWDBLSELECT', [
            'id' => $id,
            'http_code' => $info['http_code'] ?? null,
            'final_url' => $info['url'] ?? null,
            'xml_len' => strlen($xml),
            'xml_head_2000' => substr($xml, 0, 2000),
            'has_error' => str_contains($xml, '<error>'),
            'cookie_now' => $this->cookieHeaderBuild(),
            'vs_len' => strlen($this->viewStateGet() ?? ''),
        ]);

        //dd($vs,$this->cookieHeaderBuild());
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function customerSelect2(Request $request)
    {

        $id = (string)($request->get('id', '18353955'));

        // ViewState gerek
        $vs = $this->viewStateGet();
        if (!$vs) {
            // customers sayfasına git ve al
            [$html] = $this->http('GET', $this->url('/customers.xhtml'), [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Referer: ' . $this->url('/customers.xhtml'),
            ]);
            $vs = $this->extractViewStateFromHtml($html);
            $this->viewStateSet($vs);
        }

        if (!$vs) {
            return response('customers.xhtml ViewState bulunamadı', 500);
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
            CURLOPT_POSTFIELDS =>'javax.faces.partial.ajax=true&javax.faces.source=dtcustomers&javax.faces.partial.execute=dtcustomers&javax.faces.partial.render=recordFrm%3ArecordDlgPanel&javax.faces.behavior.event=rowDblselect&javax.faces.partial.event=rowDblselect&dtcustomers_instantSelectedRowKey=18353955&frm=frm&dtcustomers_selection=18353955&javax.faces.ViewState=2843430633058005753:6851927013469250870',
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
    }
}
