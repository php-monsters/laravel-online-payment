<?php
namespace PhpMonsters\Larapay\Adapter\Zarinpal;

use PhpMonsters\Log\Facades\XLog;

class Helper
{
    /**
     * CURL POST TO HTTPS
     *
     * @param $fields_arr
     * @param $url
     * @return mixed
     */
    public static function post2https($fields_arr, $url)
    {
        $header = [
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields_arr));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        //execute post
        $res = curl_exec($ch);

        // error
        $err = curl_error($ch);

        //close connection
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Error #:$err");
        }

        XLog::debug('Zarinpal call response: ' . $res);
        return $res;
    }
}
