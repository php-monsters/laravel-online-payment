# Laravel Online Payment :: LaraPay component

Online Payment Module handler for Laravel 5+ known as LaraPay component completely compatible with [banktest.ir](http://banktest.ir) simulator

پکیج لاراپی یک کامپونتت لاراول است برای اتصال به درگاه های پرداخت ایرانی و انجام تراکنشهای آنلاین. معماری این پکیج به شما اجازه می دهد به سادگی درگاه
های جدید به پکیج اضافه کنید. همچنین در صورت استفاده از پکیج لاراپی شما نیاز به انجام کمترین تغییرات در ساختار کد خود خواهید داشت و با ویرایش تنها یک مدل در پروژه لاراول خود می توانید با پکبج لاراپی هماهنگ شوید.

پکیج لاراپی کاملا همانگ با سرویس شبیه ساز درگاه های پرداخت ایرانی »بانک تست« طراحی شده و شما میتوانید تنها با تغییر یک پارامتر در تنظمیات پکیج محیط پرداخت خود را از یک درگاه واقعی به درگاه پرداخت شبیه سازی شده ی »بانک تست« سوییچ کنید. 

## What is B‌anktest?
- [BankTest](http://banktest.ir) is a sandbox service for all Iranian online payment gateways
- [بانک تست](http://banktest.ir) یک سرویس شبیه ساز درگاه های پرداخت آنلاین ایرانی برای اهداف توسعه وتست نرم افزار می باشد


## Currenctly support:

- Mellat Bank Gateway - درگاه بانک ملت لاراول
- Saman Bank Gateway - درگاه بانک سامان لاراول
- Saderat/Mabna Card Bank Gateway - درگاه بانک صادرات / مبناکارت لاراول
- Pasargad Bank Gateway - درگاه بانک پاسارگاد لاراول
- Parsian Bank Gateway - درگاه بانک پارسیان لاراول
- Melli/Sadad Bank Gateway (Sadad) - درگاه بانک ملی / سداد لاراول
- Pay.ir Gateway / درگاه پرداخت پی
- ...
- Other gateways, coming soon... لطفا شما هم در تکمیل پکیج مشارکت کنید


## Vote Up
Please star the repository if you like it
لطفا در صورتیکه از لاراپی استفاده کردید یا آن را پسندیدید به پکیج ستاره بدهید

## Installation

### [Part 1] -- Install the Larapay package

Larapay Version 6+ required PHP 7+

1. Installing via composer

```bash
composer require tartan/laravel-online-payment:"^7.0"
```
2. Add package service provider to your app service providers:

```php
Tartan\Larapay\LarapayServiceProvider::class,
```
3. Add package alias to your app aliases:

```php
'Larapay' => Tartan\Larapay\Facades\Larapay::class,
```
4. Publish package assets and configs

```bash
php artisan vendor:publish
```

## [Part 2] -- Config Larapay

1. config Larapay package and set variables in your project's `.env` file
```ini
LARAPAY_MODE=development

MELLAT_USERNAME=yourMerchantUsername
MELLAT_PASSWORD=yourMerchantPassword
MELLAT_TERMINAL_ID=12345
```

You can find all available configuration in package config file at config/larapay.php

```php

return [

    /*
    |--------------------------------------------------------------------------
    | Tartan e-payment component`s operation mode
    |--------------------------------------------------------------------------
    |
    | *** very important config ***
    | please do not change it if you don't know what BankTest is
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
    'pay_ir'    => [
        'api'   => env('PAY_IR_API_KEY', ''),
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



### [Part 3] -- Prepare your transaction/invoice model
1. Preparing your db (eloquent) model for larapay integration

    * Your Transaction/Invoice model MUST implements Larapay's TransactionInterface class

```php
<?php
namespace App\Model;

use Tartan\Larapay\Transaction\TransactionInterface;

class Transaction extends Model implements TransactionInterface 
{
    // ...
}
```

```php
<?php

namespace Tartan\Larapay\Transaction;

interface TransactionInterface
{
    /**
     * set gateway token of transaction
     * این تابع برای ذخیره سازی token دریافتی از درگاه پرداخت را روی تراکنش استفاده میشود. *
     * اگر درگاه پرداخت با مکانیزم توکنی کار نمیکند نیازی به پیاده سازی این تابع در مدل شما نیست *
     * و میتوانید فقط یک return true در بدنه تابع خود قرار دهید *
     *
     * @param string $token
     * @param bool $save
     *
     * @return mixed
     */
    public function setGatewayToken(string $token, bool $save = true): bool;

    /**
     * set reference ID of transaction
     * 
     * این تابع برای ذخیره سازی reference id دریافتی از درگاه پرداخت را روی تراکنش استفاده میشود *
     *
     * @param string $referenceId
     * @param bool $save
     *
     * @return mixed
     */
    public function setReferenceId(string $referenceId, bool $save = true): bool;

    /**
     * check if transaction is ready for requesting token from payment gateway or not
     * 
     * این تابع باید مشخص کند که آیا تراکنش شرایط لازم برای دریفات توکن از درگاه پرداخت و شروع عملیات پرداخت را دارد یا خیر *
     *
     * @return boolean
     */
    public function checkForRequestToken(): bool;

    /**
     * check if transaction is ready for requesting verify method from payment gateway or not
     * 
     * این تابع باید مشخص می کند که آیا تراکنش شرایط لازم برای تایید شدن را دارا هست یا خیر *
     *
     * @return bool
     */
    public function checkForVerify(): bool;

    /**
     * check if transaction is ready for requesting inquiry method from payment gateway or not
     * This feature does not append to all payment gateways
     * 
     * این تابع باید مشخص کند که آیا تراکنش شرایط لازم برای فراخوانی تابع inqury درگاه پرداخت را دارا هست یا خیر *
     *  فراخوانی این تابع فقط برای بعضی درگاههای خاص لازم است نه تمام درگاهها *
     *
     * @return bool
     */
    public function checkForInquiry(): bool;

    /**
     * check if transaction is ready for requesting after verify method from payment gateway or not
     * This feature does not append to all payment gateways.
     * for example in Mellat gateway this method can assume as SETTLE method
     *
     * این تابع باید مشخص کند که آیا تراکنش شرایط لازم برای فراخوانی توابع after verify نظیر تایع settle در بانک ملت را دارا هست یا خیر *
     * 
     * @return bool
     */
    public function checkForAfterVerify(): bool;

    /**
     * check if transaction is ready for requesting refund method from payment gateway or not
     * This feature does not append to all payment gateways
     *
     * این تابع باید مشخص کند که آیا تراکنش شرایط لازم برای برگشت خوردن را دارا می باشد یا خیر *
     * مثلا بعضی درگاهها فقط تا ۳۰ دقیقه اجازه برگشت تراکنش را میدهند که این قبیل موضوعات در این تابع بررسی میشوند *
     * 
     * @return bool
     */
    public function checkForRefund(): bool;

    /**
     * Set the card number (hash of card number) that used for paying the transaction
     * This data does not provide by all payment gateways
     *
     * چنانچه درگاه پرداخت شما شماره کارت پرداخت کننده را در فرایند بازگشت برای شما ارسال میکند می توانید به کمک این تابع شماره کارت را در تراکنش ذخیره کنید *
     * 
     * @param string $cardNumber
     * @param bool $save
     *
     * @return bool
     */
    public function setCardNumber(string $cardNumber, bool $save = true): bool;

    /**
     * Mark transaction as a verified transaction
     * 
     * اگر همه مراحل verify به درستی انجام شد می توانید از این تابع برای فلگ زدن تراکنش به عنوان verify شده استفده کنید *
     *
     * @param bool $save
     *
     * @return bool
     */
    public function setVerified(bool $save = true): bool;

    /**
     * Mark transaction as a after verified transaction
     * For example SETTLED in Mellat gateway
     *
     * اگر همه مراحل after verify نظیر settle در بانک ملت  به درستی انجام شد می توانید از این تابع برای فلگ زدن تراکنش به عنوان after verify شده استفده کنید *
     * @param bool $save
     *
     * @return bool
     */
    public function setAfterVerified(bool $save = true): bool;

    /**
     * Mark transaction as a paid/successful transaction
     *
     * اگر همه مراحل verify و after verify به درستی انجام شدند می توانید از این تابع برای فلگ زدن تراکنش به عنوان یم تراکنش موفق و کامل استفده کنید *
     * 
     * @param bool $save
     *
     * @return bool
     */
    public function setAccomplished(bool $save = true): bool;

    /**
     * Mark transaction as a refunded transaction
     *
     * اگر درگاه شما برگشت تراکنش را پشتیبانی میکرد و به هر دلیلی مجبور به برگشت زدن تراکنش شدید می توانید از این تابع برای فلگ زدن تراکنش به عنوان برگشت زده شده استفده کنید *
     * 
     * @param bool $save
     *
     * @return bool
     */
    public function setRefunded(bool $save = true): bool;

    /**
     * Returns the payable amount af the transaction
     *
     * این تابع در مدل شما باید مقدار قابل پرداخت فاکتور/تراکنش شما نزد درگاه پرداخت را برگرداند *
     * 
     * @return int
     */
    public function getPayableAmount(): int;

    /**
     * Set callback parameters from payment gateway
     *
     * به کمک این تابع می توانید جهت اهداف عیب یابی پارامترهای برگشتی از درگاه پرداخت را بصورت خام و مستقیم روی تراکنش خود ذخیره کنید *
     * استفاده از این تابع الزامی نیست *
     * 
     * @param array $parameters
     * @param bool $save
     *
     * @return bool
     */
    public function setCallBackParameters(array $parameters, bool $save = true): bool;

    /**
     * Set extra values of the transaction. Every key/value pair that you want to bind to the transaction
     *
     * @param string $key
     * @param $value
     * @param bool $save
     *
     * از این تابع میتوانید برای ذخیره سازی پارامترهای سفارشی و دلخواه اضافه روی تراکنش خود استفاده نمایید *
     *                  
     * @return bool
     */
    public function setExtra(string $key, $value, bool $save = true): bool;
}

```

### [Part 4] -- Prepare an online payment requirement in your app

1. Add a route and an action for an online payment in your app

    * در این مرحله باید برنامه خود را برای شروع یک پرداخت آنلاین آماده کنید. برای این کار یک روت و یک اکشن ایجاد کنید و دو پارامتر نام انگلیسی درگاه پرداخت و شماره تراکنش را روی این اکشن  ارسال کنید
    
  این اکشن یک اکشن نمونه برای دریافت فرم انتقال به درگاه پرداخت است.
  در واقع در این اکشن از کامپوننت لاراپی استفاده میشه که فرم مناسب برای انتقال به درگاه پرداخت آماده کند.  
 این فرم که در این مثال در متغیر فرم ذخیره شده به ویو پاس داده شده و در داخل ویو به کاربر نمایش داده میشه. 

```php
<?php

    // Sample Goto Payment Gateway Action
    // your Laravel action for rendering payment gateway form
    public function payOnline (Request $request, Transaction $transaction)
    {
        // Your desired payment gateway. for example: saman
        $paymentGateway = $request->input('payment_gateway');
        
        // Your desired transaction/invoice id. for example: 10
        $transaction = Trannsaction::find($request->input('transaction_id'));

        /**
         *  make larapay payment gateway instance
         *  example: $paymentGatewayHandler = Larapay::make('saman', $transaction); 
         **/
        $paymentGatewayHandler = Larapay::make($paymentGateway, $transaction);


        // set payment params 
        $paymentParams = [
            'order_id'     => $transaction->getBankOrderId(),      // شماره تراکنش/فاکتور برای ارسال به درگاه پرداخت
            'redirect_url' => route('payment.callback', [          // نمونه ایجاد url برگشت بعد از پرداخت
                'bank' => $paymentGateway,
                'transactionId' => $transaction->id
            ]),
            'amount'       => $transaction->amount,                // مبلغ تراکنش/فاکتور برای ارسال به درگاه به ریال
            'submit_label' => trans('larapay::larapay.goto_gate')  // متن دلخواه دکمه ی انتقال به درگاه پرداخت
        ];

        try {
            // get goto gate form
            // دریافت فرم از طریق لاراپی
            
            $form = $paymentGatewayHandler->form($paymentParams);
        } catch (\Exception $e) {
            // could not generate goto gate form
            // در صورتیکه خطایی در ایجاد فرم ایجاد شود خطای مربوطه لاگ شده و پیغام خطا به کاربر نمایش داده شود
            
            Log::emergency($paymentGateway->slug . ' #' . $e->getCode() . '-' . $e->getMessage());
            Session::flash('alert-danger', trans('trans.could_not_create_goto_bank_form', ['gateway' => $paymentGateway->slug]));

            return redirect()->back()->withInput();
        }
        
        if (is_null($form)) {
            // اگر به هر دلیلی فرم درست ایجاد نشده بود و برنامه قادر به ادامه عملیات پرداخت نبود
            return redirect()->back()->withInput();
        }


        // view goto gate form/view
        // نمایش صفحه انتقال به درگاه پرداخت به کاربر
        return view('gateway.gotogate', [
            'gateway'     => $paymentGateway,
            'form'        => $form,
        ]);
    }
    
    
    /**
    * 
    * sample rendered form by Larapay that passed to $form variable 
    * نمونه فرم ایجاد شده توسط لاراپی برای درگاه بانک ملت *
    *
    <form id="goto_mellat_bank" class="form-horizontal goto-bank-form" method="POST" action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat">
        <input type="hidden" name="RefId" value="BE09D6EA3507689A">
        <div class="control-group">
            <div class="controls">
                <button type="submit" class="btn btn-success">اتصال به درگاه پرداخت</button>
            </div>
        </div>
    </form>
    **/
```
2. Prepare your view file

example: `resources/views/gateway.gotogate.blade.php`
فرم تولید شده توسط کامپوننت لاراپی را داخل ویو نمایش دهید تا کاربر بتوانید دکمه انتقال به درگاه پرداخت را دیده و روی آن کلیک کند

```php
<div>
    {{!! $form !!}}
</div>
```

- If you want to hide the submit form from the user and redirect him/her to payment gateway directly you can use javascript in same view and submit the form without any user action
اگر میخواهید فرم انتقال به درگاه پرداخت به کاربر نمایش داده نشود میتوانید فرم تولید شده را بصورت اتوماتیک توسط جاوا اسکریپت سابمیت کنید و دکمه انتقال به درگاه بانک هم توسط سی اس اس مخفی نمایید مانند مثال زیر

```html
<style>
    form.goto-bank-form button[type=submit] {
        visibility: hidden;
        display: none;
    }
</style>

<script type="text/javascript">
$(function() {
    $( "#goto_mellat_bank" ).submit();
});
</script>
```

در این مرحله عملا شما کاربر را به درگاه پرداخت منتقل کرده اید و باید بخش برگشت کاربر از درگاه پرداخت به سایت را مطابق مراحل زیر آماده کنید

### [Part 5] -- Prepare an online payment requirement in your app

1. In order to verify or reverse the transaction you shoul prepare ac action as callback action

قبل از هر چیز یک روت بعنوان روت برگشت کاربر از درگاه پرداخت به سایت ایجاد کنید.
این روت باید هر دو متد پست و گت را پشتیبانی کند و از چک شدن سی اس آر اف در آن نیز جلوگیری کنید و همچنین ترجیحا این روت نباید احراز هویت کاربر را چک کند
بعنوان مثال:

```php
// gateway callback route
Route::any('/{gateway}/callback/{invoiceId}', ['as' => 'payment::callback', 'uses' => 'GatewayController@callback']);
```
2. Create callback action in your desired controller
در این اکشن شما باید تراکنش کاربر را با درگه پرداخت تایید کنید و در صورتیکه نتوانستید به مشتری خدمات بدهید تراکنش کاربر را مرجوع کنید.
دقت کنید که تمام درگاه های پرداخت مرجوعی تراکنش را پشتیبانی نمی کنند
```php
<?php
// Sample Payment Callback Action
// این اکشن نمونه پارامترهای برگشتی از درگاه پرداخت را دریافت کرده و عملیات مربوط به تایید تراکنش را انجام میدهد
// این اکشن کاملا بصورت فرضی و صرفا جهت آشنایی شما نوشته شده و ممکن است جزییات آن نیاز به اصلاح و تغییر داشته باشد

public function callback(string $gateway, string $transactionId, Request $request, Transaction $transaction)
    {
        XLog::info('gateway callback parameters', $request->all());

        $referenceId      = '';
        $paidTime         = '';
        $amount           = '';

        do {
            try {

                $validator = Validator::make([
                    'transactionId' => $transactionId,
                    'gateway'       => $gateway,
                ], [
                    'transactionId'   => [
                        'required',
                        'numeric',
                    ],
                    'gateway' => [
                        'required',
                        'exists:gateways,name'
                    ],
                ]);

                // validate required route parameters
                if ($validator->fails()) {
                    return view('gateway.callback')->withErrors([__('Code N1 - Transaction not found')]);
                }

                // find the transaction by token
                $transaction = $transaction->find($transactionId);
                if (!$transaction) {
                    return view('gateway.callback')->withErrors([__('Code N2 - Transaction not found')]);
                }

                $gateway = Gateway::fingByName($gateway);

                // basic check
                if ($transaction->bank != $gateway->bank) {
                    return view('gateway.callback')->withErrors([__('Code N3 - Transaction not found')]);
                }

                // update transaction`s callback parameter
                $transaction->setCallBackParameters($request->all());

                // load payment gateway properties
                $gatewayProperties = json_decode($transaction->gateway->properties, true);

                // ایجاد یک instance از کامپوننت Larapay
                $paymentGatewayHandler = Larapay::make($gateway, $transaction, $gatewayProperties);

                // با توجه به پارمترهای بازگشتی از درگاه پرداخت آیا امکان ادامه فرایند وجود دارد یا خیر؟
                if ($paymentGatewayHandler->canContinueWithCallbackParameters($request->all()) !== true) {
                    Session::flash('alert-danger', trans('gate.could_not_continue_because_of_callback_params'));
                    break;
                }

                // گرفتن Reference Number از پارامترهای دریافتی از درگاه پرداخت
                $referenceId = $paymentGatewayHandler->getGatewayReferenceId($request->all());

                // جلوگیری از double spending یک شناسه مرجع تراکنش
                $doubleInvoice = Transaction::where('gateway_ref_id', $referenceId)
                    ->where('verified', true)//قبلا وریفای شده
                    ->where('gateway_id', $transaction->gateway_id)
                    ->where('bank_id', $transaction->bank_id)
                    ->first();

                if (!empty($doubleInvoice)) {
                    // double spending شناسایی شد
                    Xlog::emergency('referenceId double spending detected', [
                        'tag'      => $referenceId,
                        'order_id' => $transaction->gateway_order_id,
                        'ips'      => $request->ips(),
                        'gateway'  => $gateway,
                    ]);
                    Session::flash('alert-danger', trans('gate.double_spending'));
                    // آپدیت کردن توصیحات فاکتور
                    if (!preg_match('/DOUBLE_SPENDING/i', $transaction->description)) {
                        $transaction->description = "#DOUBLE_SPENDING_BY_{$doubleInvoice->id}#\n" . $transaction->description;
                        $transaction->save();
                    }
                    break;
                }

                $transaction->setReferenceId($referenceId);

                // verify start ----------------------------------------------------------------------------------------
                $verified = false;
                // سه بار تلاش برای تایید تراکنش
                for ($i = 1; $i <= 3; $i++) {
                    try {
                        XLog::info('trying to verify payment',
                            ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);

                        $verifyResult = $paymentGatewayHandler->verify($request->all());
                        if ($verifyResult) {
                            $verified = true;
                        }
                        XLog::info('verify result',
                            ['result' => $verifyResult, 'try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                        break;
                    } catch (\Exception $e) {
                        XLog::error('Exception: ' . $e->getMessage(),
                            ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                        continue;
                    }
                }

                if ($verified !== true) {
                    XLog::error('transaction verification failed', ['tag' => $referenceId, 'gateway' => $gateway]);
                    break;
                } else {
                    XLog::info('invoice verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
                }

                // verify end ------------------------------------------------------------------------------------------

                // after verify start ----------------------------------------------------------------------------------
                $afterVerified = false;
                for ($i = 1; $i <= 3; $i++) {
                    try {
                        XLog::info('trying to after verify payment',
                            ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);

                        $afterVerifyResult = $paymentGatewayHandler->afterVerify($request->all());
                        if ($afterVerifyResult) {
                            $afterVerified = true;
                        }
                        XLog::info('after verify result', [
                            'result'  => $afterVerifyResult,
                            'try'     => $i,
                            'tag'     => $referenceId,
                            'gateway' => $gateway,
                        ]);
                        break;
                    } catch (\Exception $e) {
                        XLog::error('Exception: ' . $e->getMessage(),
                            ['try' => $i, 'tag' => $referenceId, 'gateway' => $gateway]);
                        continue;
                    }
                }

                if ($afterVerified !== true) {
                    XLog::error('transaction after verification failed',
                        ['tag' => $referenceId, 'gateway' => $gateway]);
                    break;
                } else {
                    XLog::info('invoice after verified successfully', ['tag' => $referenceId, 'gateway' => $gateway]);
                }
                // after verify end ------------------------------------------------------------------------------------

                $paidSuccessfully = true;

            } catch (Exception $e) {
                XLog::emergency($e->getMessage() . ' code:' . $e->getCode() . ' ' . $e->getFile() . ':' . $e->getLine());
                break;
            }
            
            // Start to serve to customer  -----------------------------------------------------------------------------
            
            $ifCustomerServed = false;
            // write your code login and serve to your customer and set $ifCustomerServed to TRUE
            
            // End customer serve  -------------------------------------------------------------------------------------

            if (!$ifCustomerServed) {
                // خدمات به مشتری ارائه نشد
                // reverse start ---------------------------------------------------------------------------------
                // سه بار تلاش برای برگشت زدن تراکنش
                $reversed = false;
                for ($i = 1; $i <= 3; $i++) {
                    try {
                        // ایجاد پازامترهای مورد نیاز برای برگشت زدن فاکتور
                        $reverseParameters = $request->all();
                        
                        $reverseResult = $paymentGatewayHandler->reverse($reverseParameters);
                        if ($reverseResult) {
                            $reversed = true;
                        }
                        
                        break;
                    } catch (Exception $e) {
                        XLog::error('Exception: ' . $e->getMessage(), ['try' => $i, 'tag' => $referenceId]);
                        continue;
                    }
                }

                if ($reversed !== true) {
                    XLog::error('invoice reverse failed', ['tag' => $referenceId]);
                    Flash::error(trans('gate.transaction_reversed_failed'));
                    break;
                }
                else {
                    XLog::info('invoice reversed successfully', ['tag' => $referenceId]);
                    Flash::success(trans('gate.transaction_reversed_successfully'));
                }
                // end reverse -----------------------------------------------------------------------------------
            }
            else {
                // خدمات به مشتری ارائه شد
                Flash::success(trans('gate.invoice_paid_successfully'));
                Log::info('invoice completed successfully', ['tag' => $referenceId, 'gateway' => $paidGateway->slug]);
                // فلگ زدن فاکتور بعنوان فاکتور موفق
                $transaction->setAccomplished(true);
            }

        } while (false); // do not repeat

        return view('gateway.callback', [
            'gateway'        => $gateway,
            'referenceId'    => $referenceId,
            'transaction'    => $transaction,
            'paidTime'       => $paidTime,
            'amount'         => $amount,
        ]);
    }
```


## Team

This component is developed by the following person(s) and a bunch of [awesome contributors](https://github.com/iamtartan/laravel-online-payment/graphs/contributors).

[![Aboozar Ghaffari](https://avatars2.githubusercontent.com/u/502961?v=3&s=130)](https://github.com/iamtartan) | [![Sina Miandashti](https://avatars3.githubusercontent.com/u/195868?v=3&s=130)](https://github.com/sinamiandashti) |
--- | --- |
[Aboozar Ghaffari](https://github.com/iamtartan) |[Sina Miandashti](https://github.com/sinamiandashti)


## Support This Project

Please contribute in package completion. This is the best support.

### License

The Laravel Online Payment Module is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
