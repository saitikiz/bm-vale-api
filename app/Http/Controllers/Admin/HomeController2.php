<?php

namespace App\Http\Controllers\Admin;
use DOMDocument;
use DOMXPath;

class HomeController2
{
    public $cookie = 'language-id=1; displayed-category-translations="12,1"; __nxquid=IFjEHQIuOCGBnBBP+pA+3RYdQr/BVg==0010; JSESSIONID=5e7f7a3938b50bb1a9e7e375aad2';
    public $viewState = "597386999085880272%3A4900257479413530571";
    public function index()
    {

        //$id = "16616737";
        //$id = "16868593";
        /*$id = "18353955";
        $step1 = $this->step1($id);
        $step2 = $this->step2();
        $step3 = $this->step3();
        dd($step1, $step2, $step3);
        return $step3;*/
        //return view('home');

        $cookieFile = storage_path('app/ct_pgbo_cookie.txt');
        $loginHtml = $this->jsfGet('https://ct.pgbo.io/login.xhtml', $cookieFile);
        $viewState = $this->extractViewStateFromHtml($loginHtml);
        if (!$viewState) {
            abort(500, 'Login sayfasından ViewState çekilemedi.');
        }

        $post = [
            'frm' => 'frm',
            'txtTraderCode' => "1830",
            'txtUsername'   => "onurbonus",
            'txtPassword'   => "Cas23400!!*",

            // submit button name/value (value şart olmayabilir ama koyalım)
            'j_idt34' => 'j_idt34',

            'javax.faces.ViewState' => $viewState,
        ];

        $afterLogin = $this->jsfPostForm('https://ct.pgbo.io/login.xhtml', $post, $cookieFile);
        $errors = $this->extractPrimefacesErrors($afterLogin);

        if (!empty($errors)) {
            dd(['login_failed' => true, 'errors' => $errors]);
        }
        dd(substr($afterLogin, -3000000)); // sayfanın sonuna bak
    }
    private function extractPrimefacesErrors(string $html): array
    {
        $errors = [];

        // PrimeFaces field message detayları (en yaygın)
        if (preg_match_all('~ui-message-error-detail[^>]*>(.*?)<~s', $html, $m)) {
            foreach ($m[1] as $t) {
                $t = trim(strip_tags(html_entity_decode($t, ENT_QUOTES)));
                if ($t !== '') $errors[] = $t;
            }
        }

        // Bazı temalarda summary kullanılır
        if (preg_match_all('~ui-message-error-summary[^>]*>(.*?)<~s', $html, $m2)) {
            foreach ($m2[1] as $t) {
                $t = trim(strip_tags(html_entity_decode($t, ENT_QUOTES)));
                if ($t !== '') $errors[] = $t;
            }
        }

        // Global messages
        if (preg_match_all('~ui-messages-error-summary[^>]*>(.*?)<~s', $html, $m3)) {
            foreach ($m3[1] as $t) {
                $t = trim(strip_tags(html_entity_decode($t, ENT_QUOTES)));
                if ($t !== '') $errors[] = $t;
            }
        }

        return array_values(array_unique($errors));
    }
    private function jsfGet(string $url, string $cookieFile): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);

        $body = curl_exec($ch);
        if ($body === false) throw new \RuntimeException(curl_error($ch));
        curl_close($ch);

        return $body;
    }

    private function extractViewStateFromHtml(string $html): ?string
    {
        // regex ile hızlı ve sağlam:
        if (preg_match('~name="javax\.faces\.ViewState"[^>]*value="([^"]+)"~', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        return null;
    }



    private function jsfPostForm(string $url, array $fields, string $cookieFile, array $headers = []): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_HTTPHEADER     => array_merge([
                'User-Agent: Mozilla/5.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Content-Type: application/x-www-form-urlencoded',
                'Origin: https://ct.pgbo.io',
                'Referer: https://ct.pgbo.io/login.xhtml',
            ], $headers),
        ]);

        $body = curl_exec($ch);
        if ($body === false) throw new \RuntimeException(curl_error($ch));
        curl_close($ch);

        return $body;
    }


    public function step1($id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://ct.pgbo.io/customers.xhtml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'javax.faces.partial.ajax=true&javax.faces.source=dtcustomers&javax.faces.partial.execute=dtcustomers&javax.faces.partial.render=toolbar&javax.faces.behavior.event=rowSelect&javax.faces.partial.event=rowSelect&dtcustomers_instantSelectedRowKey='.$id.'&frm=frm&dtcustomers_selection='.$id.'&javax.faces.ViewState='.$this->viewState,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/xml, text/xml, */*; q=0.01',
                'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request: partial/ajax',
                'Origin: https://ct.pgbo.io',
                'Pragma: no-cache',
                'Referer: https://ct.pgbo.io/login.xhtml',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'X-Requested-With: XMLHttpRequest',
                'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Cookie: '.$this->cookie,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }

    public function step2()
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://ct.pgbo.io/customers.xhtml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'javax.faces.partial.ajax=true&javax.faces.source=recordFrm%3Atabview%3AbonusesDt&javax.faces.partial.execute=recordFrm%3Atabview%3AbonusesDt&javax.faces.partial.render=recordFrm%3Atabview%3AbonusesDt&recordFrm%3Atabview%3AbonusesDt=recordFrm%3Atabview%3AbonusesDt&recordFrm%3Atabview%3AbonusesDt_pagination=true&recordFrm%3Atabview%3AbonusesDt_first=0&recordFrm%3Atabview%3AbonusesDt_rows=200&recordFrm%3Atabview%3AbonusesDt_encodeFeature=true&recordFrm=recordFrm&recordFrm%3Atabview%3Aj_idt194=%C4%B0dris&recordFrm%3Atabview%3Aj_idt197=Kosucu&recordFrm%3Atabview%3AcustomerStatusTypes_focus=&recordFrm%3Atabview%3AcustomerStatusTypes_input=1&recordFrm%3Atabview%3AactiveNotesDt_selection=&recordFrm%3Atabview%3AactiveNotesDt_scrollState=0%2C0&recordFrm%3Atabview%3AbonusesShowType_focus=&recordFrm%3Atabview%3AbonusesShowType_input=&recordFrm%3Atabview%3AbonusesDt%3Aj_idt550%3Afilter=&recordFrm%3Atabview%3AbonusesDt_selection=&recordFrm%3Atabview%3AbonusesDt_scrollState=0%2C0&recordFrm%3Atabview_activeIndex=2&javax.faces.ViewState='.$this->viewState,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/xml, text/xml, */*; q=0.01',
                'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request: partial/ajax',
                'Origin: https://ct.pgbo.io',
                'Pragma: no-cache',
                'Referer: https://ct.pgbo.io/login.xhtml',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'X-Requested-With: XMLHttpRequest',
                'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Cookie: '.$this->cookie,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }

    public function step3()
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://ct.pgbo.io/customers.xhtml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'javax.faces.partial.ajax=true&javax.faces.source=recordFrm%3Atabview&javax.faces.partial.execute=recordFrm%3Atabview&javax.faces.partial.render=recordFrm%3Atabview&javax.faces.behavior.event=tabChange&javax.faces.partial.event=tabChange&recordFrm%3Atabview_contentLoad=true&recordFrm%3Atabview_newTab=recordFrm%3Atabview%3AtabBonus&recordFrm%3Atabview_tabindex=2&recordFrm=recordFrm&recordFrm%3Atabview%3Aj_idt194=Sezgin&recordFrm%3Atabview%3Aj_idt197=Somakl%C4%B1&recordFrm%3Atabview%3AcustomerStatusTypes_focus=&recordFrm%3Atabview%3AcustomerStatusTypes_input=1&recordFrm%3Atabview%3AactiveNotesDt_selection=&recordFrm%3Atabview%3AactiveNotesDt_scrollState=0%2C0&recordFrm%3Atabview_activeIndex=2&javax.faces.ViewState='.$this->viewState,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/xml, text/xml, */*; q=0.01',
                'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request: partial/ajax',
                'Origin: https://ct.pgbo.io',
                'Pragma: no-cache',
                'Referer: https://ct.pgbo.io/login.xhtml',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'X-Requested-With: XMLHttpRequest',
                'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Cookie: '.$this->cookie,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseArray = $this->parsePrimefacesDataTableToArray($response);
        return $responseArray;

    }

    public function addBonus()
    {


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
            CURLOPT_POSTFIELDS => 'javax.faces.partial.ajax=true&javax.faces.source=bonusFrm%3Aj_idt926&javax.faces.partial.execute=bonusFrm&bonusFrm%3Aj_idt926=bonusFrm%3Aj_idt926&bonusFrm=bonusFrm&bonusFrm%3AinscTraderBonusId_focus=&bonusFrm%3AinscTraderBonusId_input=3336&bonusFrm%3AinscBonusAmount_input=10%2C00&bonusFrm%3AinscBonusAmount_hinput=10&javax.faces.ViewState='.$this->viewState,
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
                'Cookie: language-id=1; displayed-category-translations="12,1"; __nxquid=2tjDSAIuOCGBnBBP+pBbdS0dNbvBVg==0010; JSESSIONID=43fc6b2f2fa855d7826f02b10549'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

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
}
