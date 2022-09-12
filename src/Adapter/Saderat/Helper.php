<?php
namespace Tartan\Larapay\Adapter\Saderat;

use Tartan\Log\Facades\XLog;

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
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields_arr));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields_arr));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $res = curl_exec($ch);

        //close connection
        curl_close($ch);

        XLog::debug('Saderat call result: '. $res);
        return $res;
    }
}
