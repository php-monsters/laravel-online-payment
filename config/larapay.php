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
    |    eg, "Jahanpay" is correct not "JahanPay" or "jahanpay"
    | the gateways list is comma separated
    |
    */
    'gateways' => env('LARAPAY_GATES', 'Mellat,Saman,Pasargad'),

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
        'description'  => env('ZARINPAL_MOBILE', 'powered-by-TartanPayment'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pay.ir gateway configuration
    |--------------------------------------------------------------------------
    */
    'pay_ir' => [
        'api' => env('PAY_IR_API_KEY', ''),
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
];
