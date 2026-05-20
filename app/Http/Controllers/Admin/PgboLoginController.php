<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PgboLoginController extends Controller
{
    private string $origin = 'https://ct.pgbo.io';
    private string $basePath = '/casino-trader';


    public function customerSelect($id = null)
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
            CURLOPT_POSTFIELDS =>'javax.faces.partial.ajax=true&javax.faces.source=dtcustomers&javax.faces.partial.execute=dtcustomers&javax.faces.partial.render=recordFrm%3ArecordDlgPanel&javax.faces.behavior.event=rowDblselect&javax.faces.partial.event=rowDblselect&dtcustomers_instantSelectedRowKey='.$id.'&frm=frm&dtcustomers_selection='.$id.'&javax.faces.ViewState=-4252224226359144332%3A8365929838431358365',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/xml, text/xml, */*; q=0.01',
                'Accept-Language: tr,tr-TR;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
                'Faces-Request: partial/ajax',
                'Origin: https://ct.pgbo.io',
                'Pragma: no-cache',
                'Referer: https://ct.pgbo.io/customers.xhtml',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
                'X-Requested-With: XMLHttpRequest',
                'sec-ch-ua: "Google Chrome";v="143", "Chromium";v="143", "Not A(Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'Cookie: JSESSIONID=6a718829b87143ac09cd27c14746; JSESSIONID=6a64489db43e3a18f2886d684624; __nxquid=uvBaLwIuOCGBnBBP+pAmLxcd7bPBVg==0010; language-id=1'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function customerBonuses()
    {

    }
}
