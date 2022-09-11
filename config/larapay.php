<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tartan e-payment component`s operation mode
    |--------------------------------------------------------------------------
    |
    | *** very important config ***
    | please do not change it if you don't know what BankTest is
    |
    | production: component operates with real payments gateways
    | development: component operates with simulated "Bank Test" (banktest.ir) gateways
    |
    */
    'mode'     => env('LARAPAY_MODE', 'production'),

    /*
    |--------------------------------------------------------------------------
    | ready to serve gateways
    |--------------------------------------------------------------------------
    |
    | specifies ready to serve gateways.
    | gateway characters are case sensitive and should be exactly same as their folder name.
    |    eg, "Asanpay" is correct not "AsanPay" or "asanpay"
    | the gateways list is comma separated
    |
    */
    'gateways' => env('LARAPAY_GATES', 'Mellat,Saman,Pasargad,Parsian,ZarinPal,Idpay,Payir,Saderat,Zibal,Nextpay'),

    /*
    |--------------------------------------------------------------------------
    | Mellat gateway configuration
    |--------------------------------------------------------------------------
    */
    'mellat'   => [
        'username'    => env('MELLAT_USERNAME', ''),
        'password'    => env('MELLAT_PASSWORD', ''),
        'terminal_id' => env('MELLAT_TERMINAL_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Parsian gateway configuration
    |--------------------------------------------------------------------------
    */
    'parsian'  => [
        'pin'     => env('PARSIAN_PIN', ''),
        'timeout' => env('PARSIAN_TIMEOUT', 15),

    ],
    /*
    |--------------------------------------------------------------------------
    | Pasargad gateway configuration
    |--------------------------------------------------------------------------
    */
    'pasargad' => [
        'terminalId'       => env('PASARGAD_TERMINAL_ID', ''),
        'merchantId'       => env('PASARGAD_MERCHANT_ID', ''),
        'certificate_path' => storage_path(env('PASARGAD_CERT_PATH', 'payment/pasargad/certificate.xml')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sadad gateway configuration
    |--------------------------------------------------------------------------
    */
    'sadad'    => [
        'merchant'        => env('SADAD_MERCHANT', ''),
        'transaction_key' => env('SADAD_TRANS_KEY', ''),
        'terminal_id'     => env('SADAD_TERMINAL_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Saderat - Mabna Card Aria gateway configuration
    |--------------------------------------------------------------------------
    */
    'saderat'  => [
        'MID'              => env('SADERAT_MID', ''),
        'TID'              => env('SADERAT_TID', ''),
        'public_key_path'  => storage_path(env('SADERAT_CERT_PATH', 'payment/saderat/public.key')),
        'private_key_path' => storage_path(env('SADERAT_CERT_PATH', 'payment/saderat/private.key')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Saman gateway configuration
    |--------------------------------------------------------------------------
    */
    'saman'    => [
        'merchant_id'   => env('SAMAN_MERCHANT_ID', ''),
        'merchant_pass' => env('SAMAN_MERCHANT_PASS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zarinpal gateway configuration
    |--------------------------------------------------------------------------
    |
    | types: acceptable values  --- zarin-gate or normal
    | server: acceptable values --- germany or iran or test
    |
    */
    'zarinpal' => [
        'merchant_id'  => env('ZARINPAL_MERCHANT_ID', ''),
        'type'         => env('ZARINPAL_TYPE', 'zarin-gate'),
        'callback_url' => env('ZARINPAL_CALLBACK_URL', ''),
        'server'       => env('ZARINPAL_SERVER', 'germany'),
        'email'        => env('ZARINPAL_EMAIL', ''),
        'mobile'       => env('ZARINPAL_MOBILE', '09xxxxxxxxx'),
        'description'  => env('ZARINPAL_DESCRIPTION', 'powered-by-Larapay'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pay.ir gateway configuration
    |--------------------------------------------------------------------------
    |
    | api: For the sandbox gateway, set API to 'test'
    |
    */
    'payir' => [
        'api' => env('PAY_IR_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idpay gateway configuration
    |--------------------------------------------------------------------------
    |
    | types: acceptable values  --- normal
    |
    */
    'idpay' => [
        'merchant_id'  => env('IDPAY_MERCHANT_ID', ''),
        'type'         => env('IDPAY_TYPE', 'normal'),
        'callback_url' => env('IDPAY_CALLBACK_URL', ''),
        'email'        => env('IDPAY_EMAIL', ''),
        'mobile'       => env('IDPAY_MOBILE', '09xxxxxxxxx'),
        'description'  => env('IDPAY_DESCRIPTION', 'powered-by-Larapay'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Zibal gateway configuration
    |--------------------------------------------------------------------------
    |
    | merchant_id: For the sandbox gateway, set merchant_id to 'zibal'
    |
    */
    'zibal' => [
        'merchant_id'  => env('ZIBAL_MERCHANT_ID', ''),
        'type'         => env('ZIBAL_TYPE', 'normal'),
        'callback_url' => env('ZIBAL_CALLBACK_URL', ''),
        'email'        => env('ZIBAL_EMAIL', ''),
        'mobile'       => env('ZIBAL_MOBILE', '09xxxxxxxxx'),
        'description'  => env('ZIBAL_DESCRIPTION', 'powered-by-Larapay'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nextpay gateway configuration
    |--------------------------------------------------------------------------
    |
    */
    'nextpay' => [
        'api_key'      => env('NEXTPAY_MERCHANT_ID', ''),
        'type'         => env('NEXTPAY_TYPE', 'normal'),
        'callback_url' => env('NEXTPAY_CALLBACK_URL', ''),
        'email'        => env('NEXTPAY_EMAIL', ''),
        'mobile'       => env('NEXTPAY_MOBILE', '09xxxxxxxxx'),
        'description'  => env('NEXTPAY_DESCRIPTION', 'powered-by-Larapay'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SoapClient Options
    |--------------------------------------------------------------------------
    |
    | useOptions: true/false
    | options: soapClient Options
    |
    */
    'soap'   => [
        'useOptions' => env('SOAP_HAS_OPTIONS', false),
        'options'    => [
            'proxy_host'     => env('SOAP_PROXY_HOST', ''),
            'proxy_port'     => env('SOAP_PROXY_PORT', ''),
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                ]
            ),
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Route name for handle payment callback
    |--------------------------------------------------------------------------
    */

    'payment_callback' => env('LARAPAY_PAYMENT_CALLBACK' , '')
];
