<?php
namespace Tartan\Larapay\Adapter\Zibal;

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
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, count($fields_arr));
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($fields_arr));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        //execute post
        $res = curl_exec($ch);

        //close connection
        curl_close($ch);

        XLog::debug('Zibal call result: '. $res);
        return $res;
    }
}
