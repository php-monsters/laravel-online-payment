#Laravel Iranian Online Payment Component
Online Payment Module handler for Laravel 5+ and 6 known as LaraPay component completely compatible with [BankTest](http://banktest.ir) simulator.
Larapay integrated all Iranian payment gateways to one component. 

Here are a few short examples of what you can do:
* create new transaction form your order model and generate bank form
```php
 $transaction = $order->createTransaction(Bank::MELLAT);
 $form = $transaction->generateForm();
```
* handle bank callback 
```php
 $transaction = Larapay::verifyTransaction($request);
 //if bank support reverse
 $transaction->reverseTransaction();
 $order = $transaction->model;
```
* get order transaction information
```php
 $accomplishedTransactions = $order->accomplishedTransactions;
 $allTransactions = $order->transation;
 $isPaid = $order->isPaid();
 $paidAmount = $order->paidAmount();
```

## What is B‌anktest?
- [BankTest](http://banktest.ir) is a sandbox service for all Iranian online payment gateways
- [بانک تست](http://banktest.ir) یک سرویس شبیه ساز درگاه های پرداخت آنلاین ایرانی برای اهداف توسعه و تست نرم افزار می باشد


## Currenctly support:

- Mellat Bank Gateway - درگاه بانک ملت لاراول
- Saman Bank Gateway - درگاه بانک سامان لاراول
- Saderat/Mabna Card Bank Gateway - درگاه بانک صادرات / مبناکارت لاراول
- Pasargad Bank Gateway - درگاه بانک پاسارگاد لاراول
- Parsian Bank Gateway - درگاه بانک پارسیان لاراول
- Melli/Sadad Bank Gateway (Sadad) - درگاه بانک ملی / سداد لاراول
- Pay.ir Gateway / درگاه پرداخت پی
- Zarinpal Gateway / درگاه پرداخت زرین پال
- ...
- Other gateways, coming soon... لطفا شما هم در تکمیل پکیج مشارکت کنید

## Requirements
Larapay Version 6+ required PHP 7+

## Installation
1. Installing via composer

```bash
composer require tartan/laravel-online-payment
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
php artisan vendor:publish --provider="Tartan\Larapay\LarapayServiceProvider"
```

5. Run migration
```bash
php artisan migrate
```

## Configuration
If you complete installation step correctly, you can find Larapay config file as larapay.php in you project config file.

for sandbox you should set ```LARAPAY_MODE=development``` in your .env file otherwise set ```LARAPAY_MODE=production```

if you choose development mode, Larapay use banktest.ir for payment gateway.

set your bank username or password in your .env. here are some example:
```
LARAPAY_MODE=development

SAMAN_MERCHANT_ID=bmcf****
SAMAN_MERCHANT_PASS=98221***

MELLAT_USERNAME=user***
MELLAT_PASSWORD=80714***
MELLAT_TERMINAL_ID=747
```

### Setup callback route
you should create a route for handling callback from bank and set your route name in .env

for example create a POST route in routes folder, web.php like this:
```php
Route::post('payment/callback', 'YourController@handleCallback')->name('payment.callback');
```

and set route name in .env file:

```
LARAPAY_PAYMENT_CALLBACK=payment.callback
```


## Usage

### Prepare payable model

Use `Payable` trait in your order model or any other model like user which will get payment feature and implement it.

you can impalement getAmount() method to return `Iranian Rail` amount of your model.
```php
use Tartan\Larapay\Payable;

class Order extends Model 
{
    use Payable;

    public function getAmount(){
        return intval($this->amount) * 10;
    }   

}
```

Now you just have 3 steps to complete your payment:

### 1- create transaction

In your bank controller create a transaction for your order and generate bank for to transfer user to payment gateway.
```php
use Tartan\Larapay\Models\Enum\Bank;

class BankController extends Controller
{
    public function index()
    {
        //your logic and prepare your order
        // ...

        //if you implement getAmount() method you can set amount to null
        $amount = 1200000;  //Rial at least 1000 
        //order or user description
        $description = 'I pay my order with Larapay <3';
        //create transaction 
        $transaction = $order->createTransaction(Bank::MELLAT, $amount, $description);
        
        //auto submit bank form and transfer user to gateway
        $autoSubmit = true;
        //callback route name. if you set it on your .env file you can ignore it
        $callbackRouteName = 'payment.callback';
        //generate bank form
        $form = $transaction->generateForm($autoSubmit, $callbackRouteName);
        
        return view('go-to-bank',[
            'form' => $form,
        ]);
    }
}
```

### 2- show bank transfer form

now you can show you `$form` in your `go-to-bank` view file:
```php
<div>
    {!! $form !!}
</div>
```

you can modify bank forms in:
```
resources/views/vendor/larapy
```

### 3- handle callback

After payment, bank call you callback route

```php
use Illuminate\Http\Request;
use Tartan\Larapay\Facades\Larapay;

class YourController extends Controller
{
    public function handleCallback(Request $request)
    {
         try{
            $transaction = Larapay::verifyTransaction($request);
            $order = $transaction->model;
            //transaction done. payment is successful         
         } catch (\Exception $e){
            // transaction not complete!!!
            // show error to your user
         }
    }
}
```

if you want to revers transaction and your bank support it, you can do this way:
```php
$transaction->reverseTransaction();
```

## Security

If you discover any security related issues, please email a6oozar@gmail.com or milad.kian@gmail.com instead of using the issue tracker.

## Team

This component is developed by the following person(s) and a bunch of [awesome contributors](https://github.com/iamtartan/laravel-online-payment/graphs/contributors).

[![Aboozar Ghaffari](https://avatars2.githubusercontent.com/u/502961?v=3&s=130)](https://github.com/iamtartan) | [![Sina Miandashti](https://avatars3.githubusercontent.com/u/195868?v=3&s=130)](https://github.com/sinamiandashti) | [![Milad Kianmehr](https://avatars3.githubusercontent.com/u/4578704?v=3&s=130)](https://github.com/miladkian)
--- | --- | --- |
[Aboozar Ghaffari](https://github.com/iamtartan) |[Sina Miandashti](https://github.com/sinamiandashti)| [Milad Kianmehr](https://github.com/miladkian)


## Support This Project

Please contribute in package completion. This is the best support.

## License

The Laravel Online Payment Module is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
 




