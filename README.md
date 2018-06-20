# Laravel Online Payment :: LaraPay component

Online Payment Module handler for Laravel 5+ known as LaraPay component completely compatible with [banktest.ir](banktest.ir) simulator

## What is B‌anktest?
- [BankTest](banktest.ir) is a sandbox service for all Iranian online payment gateways
- [بانک تست](banktest.ir) یک سرویس شبیه ساز درگاه های پرداخت آنلاین ایرانی برای اهداف توسعه وتست نرم افزار می باشد


## Currenctly support:

- Mellat Bank Gateway - درگاه بانک ملت لاراول
- Saman Bank Gateway - درگاه بانک سامان لاراول
- Saderat Bank Gateway - درگاه بانک صادرات لاراول
- Pasargad Bank Gateway - درگاه بانک پاسارگاد لاراول
- Parsian Bank Gateway - درگاه بانک پارسیان لاراول
- Melli Bank Gateway (Sadad) - درگاه بانک ملی / سداد لاراول

## Installation

1.Installing via composer

```bash
composer require tartan/laravel-online-payment:"^5.0"
```
2.Add this to your app service providers for laravel version < 5.4 :

```php
Tartan\Larapay\LarapayServiceProvider::class,
```
3.Add this to your aliases :

```php
'Larapay' => Tartan\Larapay\Facades\Larapay::class,
```
4.Publish the package assets and configs

```bash
php artisan vendor:publish
```

5. Preparing your db (eloquent) model for larapay integration

    * Your Transaction/Invoice (Eloquent) model MUST implement

```php
namespace App\Model;

use Tartan\Larapay\Transaction;

class Transaction extends Model implements TransactionInterface
{
    // set order reference Id
	public function setReferenceId($referenceId, $save = true){}

    // check if you transaction is ready for requesting payment token 
	public function checkForRequestToken(){}

    // check if transaction is ready for requesting verify transaction
	public function checkForVerify(){}

    // check if transaction is ready for requesting inqury transaction (if supported by gateway)
	public function checkForInquiry(){}

    // check if transaction is ready for requesting reverse transaction (if supported by gateway)
	public function checkForReverse(){}

    // check if transaction is ready for requesting settle/... transaction (if needed by gateway)
	public function checkForAfterVerify(){}

    // update transaction by paid card number (if provided by gateway)
	public function setCardNumber($cardNumber){}
    
    // mark transaction as verified
	public function setVerified(){}
    
    // mark transaction as settled/...
	public function setAfterVerified(){}

    // mark transaction as completed
	public function setSuccessful($flag){}

    // mark transaction as reversed
	public function setReversed(){}

    // get transaction amount
	public function getAmount(){}

    // set transactions's paid tme
	public function setPaidAt($time = 'now'){}

    // set transaction's extra details
	public function setExtra($key, $value, $save = false){}
}
```

6. Prepare for online payment

```php
    public function payOnline (Request $request, Transaction $transaction)
    {
        // check if the selected payment is active or not from your gateways table
        $paymentGateway = Gateway::activeGate()
            ->where('slug', $request->input('by_online_gateway'))
            ->first();

        if (empty($paymentGateway)) {
            return view('gateway.notfound');
        }

        // get some additional parameters for updating transaction
        $parameters = [
            'description' => $request->input('by_online_description', ''),
            'bank'        => $request->input('by_online_gateway'),
        ];

        // update transaction payment method
        $transaction = $this->transactionsRepository->setTransactionPaid(
            $transaction, TransactionPayment::ONLINE, $parameters
        );

        // make larapay payment gateway instance
        $paymentGatewayHandler = Larapay::make($paymentGateway->slug, $transaction);


        // set payment params 
        $paymentParams = [
            'order_id'     => $transaction->getBankOrderId(),
            'redirect_url' => route('payment.callback', [
                'bank' => $paymentGateway->slug,
                'transactionId' => $transaction->guid
            ]),
            'amount'       => $transaction->amount,
            'submit_label' => trans('larapay::larapay.goto_gate')
        ];

        try {
            // get goto gate form
            $form = $paymentGatewayHandler->form($paymentParams);
        } catch (\Exception $e) {
            // could not generate goto gate form
            Log::emergency($paymentGateway->slug . ' #' . $e->getCode() . '-' . $e->getMessage());
            Session::flash('alert-danger', trans('t.could_not_create_goto_bank_form', ['gateway' => $paymentGateway->name]));

            return redirect()->back()->withInput();
        }
        if (is_null($form)) {
            return redirect()->back()->withInput();
        }

        // view goto gate view
        return view('gateway.gotogate', [
            'gateway'     => $paymentGateway,
            'form'        => $form,
        ]);
    }
```

## Component Configuration

```php
return [

	/*
	|--------------------------------------------------------------------------
	| Tartan e-payment component`s operation mode
	|--------------------------------------------------------------------------
	|
	| *** very important config ***
	| please do not change it if you don't know what BankTest is
	|
	| > production: component operates with real payments gateways
	| > development: component operates with simulated "BankTest" (banktest.ir) gateways
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
		'username'     => env('MELLAT_USERNAME', ''),
		'password'     => env('MELLAT_PASSWORD',''),
		'terminal_id'  => env('MELLAT_TERMINAL_ID', ''),
		'callback_url' => env('MELLAT_CALLBACK_URL', '')
	],

	/*
	|--------------------------------------------------------------------------
	| Parsian gateway configuration
	|--------------------------------------------------------------------------
	*/
	'parsian'  => [
		'pin'          => env('PARSIAN_PIN', ''),
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
		'callback_url'     => env('PASARGAD_CALLBACK_URL', '')
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
		'callback_url'    => env('SADAD_CALLBACK_URL', ''),
	],

	'saderat' => [
		'MID' => env('SADERAT_MID', ''),
		'TID' => env('SADERAT_TID', ''),
		'public_key_path' => storage_path(env('SADERAT_CERT_PATH', 'payment/saderat/public.key')),
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
    | SoapClient Options
    |--------------------------------------------------------------------------
    |
    | useOptions: true/false
    | options: soapClient Options
    |
    */
    'soap' => [
        'useOptions' => env('SOAP_HAS_OPTIONS', false),
        'options' => [
            'proxy_host' => env('SOAP_PROXY_HOST', ''),
            'proxy_port' => env('SOAP_PROXY_PORT', ''),
            'stream_context' => stream_context_create(
                [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]
            ),
        ]
    ]
];
```

## Team

This component is developed by the following person(s) and a bunch of [awesome contributors](https://github.com/iamtartan/laravel-online-payment/graphs/contributors).

[![Aboozar Ghaffari](https://avatars2.githubusercontent.com/u/502961?v=3&s=130)](https://github.com/iamtartan) |
--- |
[Aboozar Ghaffari](https://github.com/iamtartan) |


## Support This Project

[![Donate via Paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LXEL22GFTXTKN)

### License

The Laravel Online Payment Module is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
