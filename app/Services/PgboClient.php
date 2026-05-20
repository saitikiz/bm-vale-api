<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PgboClient
{
    private string $origin = 'https://ct.pgbo.io';
    private string $basePath = '/casino-trader';
    private string $cookieFile;

    private ?string $viewState = null;

    // İsterseniz .env üzerinden de alabilirsiniz
    public function __construct(?string $cookieFile = null)
    {
        $this->cookieFile = $cookieFile ?: storage_path('app/pgbo/cookie.txt');

        $dir = dirname($this->cookieFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!file_exists($this->cookieFile)) {
            @touch($this->cookieFile);
        }
    }

    /**
     * Base URL builder (basePath sabit!)
     */
    private function url(string $path): string
    {
        // path "/customers.xhtml" gibi gelirse basePath ekle
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // zaten /casino-trader ile başladıysa olduğu gibi kullan
        if (str_starts_with($path, $this->basePath . '/')) {
            return $this->origin . $path;
        }

        // "/login.xhtml" -> "/casino-trader/login.xhtml"
        return $this->origin . $this->basePath . $path;
    }

    /**
     * Public getter (debug/chain için)
     */
    public function getViewState(): ?string
    {
        return $this->viewState;
    }

    /**
     * JSF HTML'den ViewState çek
     */
    public function extractViewStateFromHtml(string $html): ?string
    {
        // name="javax.faces.ViewState" value="..."
        if (preg_match('/name="javax\.faces\.ViewState"\s+[^>]*value="([^"]+)"/i', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        return null;
    }

    /**
     * JSF partial-response XML'den ViewState çek
     * PrimeFaces genelde: <update id="j_id1:javax.faces.ViewState:0"><![CDATA[...]]></update>
     */
    public function extractViewStateFromPartial(string $xml): ?string
    {
        // CDATA
        if (preg_match('/<update[^>]+id="[^"]*javax\.faces\.ViewState[^"]*"[^>]*>\s*<!\[CDATA\[(.*?)\]\]>\s*<\/update>/is', $xml, $m)) {
            return trim($m[1]);
        }
        // CDATA yoksa
        if (preg_match('/<update[^>]+id="[^"]*javax\.faces\.ViewState[^"]*"[^>]*>(.*?)<\/update>/is', $xml, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    /**
     * Tek noktadan ViewState güncelle (HTML veya partial-response)
     * Bunu her jsfPost sonrası çağıracağız (unutma derdi bitiyor)
     */
    private function autoUpdateViewState(string $responseBody): void
    {
        $new = null;

        // partial-response ihtimali
        if (stripos($responseBody, '<partial-response') !== false || stripos($responseBody, 'javax.faces.ViewState') !== false) {
            $new = $this->extractViewStateFromPartial($responseBody);
        }

        // HTML ihtimali
        if (!$new) {
            $new = $this->extractViewStateFromHtml($responseBody);
        }

        if ($new) {
            $this->viewState = $new;
            Log::debug('[PGBO] ViewState updated', ['len' => strlen($new)]);
        } else {
            Log::warning('[PGBO] ViewState not found in response');
        }
    }

    /**
     * cURL low-level request
     * - Redirectleri takip eder (302->301->200 vb.)
     * - Header+body ayrıştırır
     * - curl_exec false kontrolü
     */
    private function curlRequest(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init();

        $baseHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Connection: keep-alive',
        ];

        // Header normalize (string list)
        $finalHeaders = array_merge($baseHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $finalHeaders,

            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,

            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,

            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR  => $this->cookieFile,

            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 20,

            // SSL (prod ortamda normalde doğrulamayı kapatmayın)
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err = curl_error($ch);
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            Log::error('[PGBO] cURL error', [
                'errno' => $errno,
                'error' => $err,
                'url' => $url,
                'info' => $info,
            ]);

            throw new \RuntimeException("cURL failed: ({$errno}) {$err}");
        }

        $info = curl_getinfo($ch);
        $headerSize = $info['header_size'] ?? 0;

        $headerStr = substr($raw, 0, $headerSize);
        $bodyStr   = substr($raw, $headerSize);

        curl_close($ch);

        return [
            'status' => (int)($info['http_code'] ?? 0),
            'info' => $info,
            'headers' => $this->parseHeaders($headerStr),
            'raw_headers' => $headerStr,
            'body' => $bodyStr,
        ];
    }

    private function parseHeaders(string $headerStr): array
    {
        // Redirectlerde birden fazla header bloğu olabilir. En son bloğu parse etmeye çalışalım.
        $blocks = preg_split("/\r\n\r\n|\n\n|\r\r/", trim($headerStr));
        $last = trim(end($blocks));

        $lines = preg_split("/\r\n|\n|\r/", $last);
        $out = [];
        foreach ($lines as $i => $line) {
            if ($i === 0) {
                $out['_status_line'] = $line;
                continue;
            }
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $out[trim($k)] = trim($v);
            }
        }
        return $out;
    }

    /**
     * JSF GET wrapper
     */
    public function jsfGet(string $path, array $headers = []): array
    {
        $url = $this->url($path);

        Log::debug('[PGBO] GET', ['url' => $url]);

        $res = $this->curlRequest('GET', $url, $headers);

        // GET sonrası sayfa HTML ise ViewState al
        $this->autoUpdateViewState($res['body']);

        return $res;
    }

    /**
     * JSF POST wrapper (form-urlencoded)
     */
    public function jsfPost(string $path, array $fields, array $headers = []): array
    {
        $url = $this->url($path);

        $body = http_build_query($fields, '', '&');

        $defaultPostHeaders = [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: ' . $this->origin,
            'Referer: ' . $url,
        ];

        Log::debug('[PGBO] POST', [
            'url' => $url,
            'fields_keys' => array_keys($fields),
        ]);

        $res = $this->curlRequest('POST', $url, array_merge($defaultPostHeaders, $headers), $body);

        // Kritik: her POST sonrası ViewState güncelle
        $this->autoUpdateViewState($res['body']);

        return $res;
    }

    /**
     * customers sayfası "login olmuş" göstergesi mi?
     * (dtcustomers, customers.xhtml içerikleri vs.)
     */
    private function isCustomersPage(string $html): bool
    {
        return (stripos($html, 'dtcustomers') !== false)
            || (stripos($html, 'customers.xhtml') !== false);
    }

    /**
     * OTP ekranı mı? (sayfada OTP input / form var mı)
     * Kendi HTML’inize göre bu kontrolleri artırabilirsiniz.
     */
    private function isOtpPage(string $html): bool
    {
        // OTP input name/id genelde farklı olur; örnek kontrol:
        return (stripos($html, 'otp') !== false && stripos($html, 'ViewState') !== false)
            || (stripos($html, 'One Time Password') !== false);
    }

    /**
     * Already logged in kontrolü:
     * customers sayfası geliyorsa login akışını atlar.
     */
    public function ensureLoggedIn(string $traderCode, string $username, string $password, ?string $otp = null): void
    {
        // 1) Direkt customers’a gitmeyi dene (already logged in)
        $customers = $this->jsfGet('/customers.xhtml');
        if ($this->isCustomersPage($customers['body'])) {
            Log::info('[PGBO] Already logged in (customers reachable)');
            return;
        }

        // 2) Login sayfasına git -> ViewState al
        $loginPage = $this->jsfGet('/login.xhtml');
        if (!$this->viewState) {
            throw new \RuntimeException('Login page ViewState not found.');
        }

        // 3) Login POST (alan adlarını sizin sayfanıza göre güncelleyin!)
        // NOT: JSF form name/id çoğu zaman "frm" olur; sizde farklıysa değiştirin.
        $loginFields = [
            'frm' => 'frm',
            // --- AŞAĞIDAKİ 3 ALANIN NAME'LERİNİ HTML'DEN BİR KEZ DOĞRULAYIN ---
            'traderCode' => $traderCode,
            'username' => $username,
            'password' => $password,

            // JSF submit:
            // 'frm:loginBtn' => 'frm:loginBtn', // varsa ekleyin
            'javax.faces.ViewState' => $this->viewState,
        ];

        $afterLogin = $this->jsfPost('/login.xhtml', $loginFields);

        // Login başarılı olup customers'a düştüyse
        if ($this->isCustomersPage($afterLogin['body'])) {
            Log::info('[PGBO] Login success (customers reached)');
            return;
        }

        // OTP ekranına geldiyse
        if ($this->isOtpPage($afterLogin['body'])) {
            if (!$otp) {
                throw new \RuntimeException('OTP required but not provided.');
            }

            // OTP form alan adlarını sizin sayfaya göre güncelleyin!
            $otpFields = [
                'frm' => 'frm',
                'otp' => $otp,
                // 'frm:otpBtn' => 'frm:otpBtn', // varsa
                'javax.faces.ViewState' => $this->viewState,
            ];

            $afterOtp = $this->jsfPost('/login.xhtml', $otpFields);

            if ($this->isCustomersPage($afterOtp['body'])) {
                Log::info('[PGBO] OTP success (customers reached)');
                return;
            }

            throw new \RuntimeException('OTP submitted but customers page not reached.');
        }

        // Ne customers ne OTP -> hata
        throw new \RuntimeException('Login failed or unexpected page flow.');
    }

    /**
     * customersSayfasinaGit() -> ViewState al
     */
    public function customersSayfasinaGit(): void
    {
        $res = $this->jsfGet('/customers.xhtml');
        if (!$this->isCustomersPage($res['body'])) {
            throw new \RuntimeException('customers.xhtml not reached.');
        }
        if (!$this->viewState) {
            throw new \RuntimeException('customers page ViewState not found.');
        }
    }

    /**
     * customersRowSelect($id)
     * - toolbar update
     * - ViewState otomatik güncellenir (jsfPost içinde)
     */
    public function customersRowSelect(string|int $id): array
    {
        if (!$this->viewState) {
            // güvenlik: customers'a gidip ViewState al
            $this->customersSayfasinaGit();
        }

        $fields = [
            'javax.faces.partial.ajax' => 'true',
            'javax.faces.source' => 'dtcustomers',
            'javax.faces.partial.execute' => 'dtcustomers',
            'javax.faces.partial.render' => 'toolbar',
            'javax.faces.behavior.event' => 'rowSelect',
            'javax.faces.partial.event' => 'rowSelect',
            'dtcustomers_instantSelectedRowKey' => (string)$id,
            'frm' => 'frm',
            'dtcustomers_selection' => (string)$id,
            'javax.faces.ViewState' => $this->viewState,
        ];

        $headers = [
            'Faces-Request: partial/ajax',
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/xml, text/xml, */*; q=0.01',
        ];

        $res = $this->jsfPost('/customers.xhtml', $fields, $headers);

        // jsfPost zaten autoUpdateViewState yaptı.
        return $res;
    }
}
