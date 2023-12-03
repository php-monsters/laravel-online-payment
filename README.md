# Laravel Iranian Online Payment Component
Online Payment Module handler for Laravel 5+ known as LaraPay component completely compatible with [BankTest](http://banktest.ir) sandbox.
Larapay integrated all Iranian payment gateways into one component. 

Here are a few short examples of what you can do:
* create new transaction form your order model and generate bank form
```php
 $transaction = $order->createTransaction(Bank::MELLAT);
 $form = $transaction->generateForm();
```
* handle gateway callback (verify/settle/...)
```php
 $transaction = Larapay::verifyTransaction($request);
 //if the gateway supports reverse method
 $transaction->reverseTransaction();
 $order = $transaction->model;
```
* get order transaction information
```php
 $allTransactions = $order->transations;
 $accomplishedTransactions = $order->accomplishedTransactions;
 $isPaid = $order->isPaid();
 $paidAmount = $order->paidAmount();
```

## Currenctly supports:

- Mellat Bank Gateway - درگاه بانک ملت لاراول
- Saman Bank Gateway - درگاه بانک سامان لاراول
- Saderat/Sepehr Pay Bank Gateway - درگاه بانک صادرات / سپهر
- Pasargad Bank Gateway - درگاه بانک پاسارگاد لاراول
- Parsian Bank Gateway - درگاه بانک پارسیان لاراول
- Melli/Sadad Bank Gateway (Sadad) - درگاه بانک ملی / سداد لاراول
- Pay.ir Gateway / درگاه پرداخت پی
- Zarinpal Gateway / درگاه پرداخت زرین پال
- IDPay Gateway / درگاه آیدی پی
- Zibal Gateway / درگاه زیبال
- nextpay Gateway / درگاه نکست پی

- ...
- Other gateways, coming soon... لطفا شما هم در تکمیل پکیج مشارکت کنید

#### But what is B‌anktest sandbox?
- [BankTest](http://banktest.ir) is a sandbox service for all Iranian online payment gateways
- [بانک تست](http://banktest.ir) یک سرویس شبیه ساز درگاه های پرداخت آنلاین ایرانی برای اهداف توسعه و تست نرم افزار می باشد


## Requirements
Larapay Version 6+ required PHP 7+

## Installation
1. Installing via composer

```bash
composer require tartan/laravel-online-payment
```
2. Add package service provider to your app service providers (only for Laravel < 5.5):

```php
PhpMonsters\Larapay\LarapayServiceProvider::class,
PhpMonsters\Log\XLogServiceProvider::class,
```
3. Add package alias to your app aliases (only for Laravel < 5.5):

```php
'Larapay' => PhpMonsters\Larapay\Facades\Larapay::class,
'XLog'    => PhpMonsters\Log\Facades\XLog::class,
```
4. Publish package assets and configs

```bash
php artisan vendor:publish --provider="PhpMonsters\Larapay\LarapayServiceProvider"
```

5. Run migration
```bash
php artisan migrate
```

## Configuration
If you complete installation step correctly, you can find Larapay config file as larapay.php in you project config file.

For sandbox (banktest) you should set ```LARAPAY_MODE=development``` in your .env file otherwise set ```LARAPAY_MODE=production```

If you choose development mode, Larapay use banktest.ir as it's payment gateway.

Set your gateway(s) configs in your .env file. Here are some example:
```ini
LARAPAY_MODE=development

SAMAN_MERCHANT_ID=bmcf****
SAMAN_MERCHANT_PASS=98221***

MELLAT_USERNAME=user***
MELLAT_PASSWORD=80714***
MELLAT_TERMINAL_ID=747
```

### Setup callback route
you should create a route for handling callback from bank and set your route name in .env

For example create a POST route in routes folder, web.php like this:
```php
Route::post('payment/callback', 'YourController@handleCallback')->name('payment.callback');
```

then set the route name in .env file:

```ini
LARAPAY_PAYMENT_CALLBACK=payment.callback
```


## Usage

### Prepare payable model

Use `Payable` trait in your order model or any other model like user which will get payment feature and implement it.

You can impalement getAmount() method to return `Iranian Rail` amount of your model.
```php
use PhpMonsters\Larapay\Payable;

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
use PhpMonsters\Larapay\Models\Enum\Bank;

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
        //some additional data that you need store on transaction
        $additionalData = [];
        //create transaction 
        $transaction = $order->createTransaction(Bank::MELLAT, $amount, $description, $additionalData);
        
        //auto submit bank form and transfer user to gateway
        $autoSubmit = true;
        //callback route name. if you set it on your .env file you can set this to null
        $callbackRouteName = 'payment.callback';
        //adapter config
        $adapterConfig = [];
        //generate bank form
        $form = $transaction->generateForm($autoSubmit, $callbackRouteName, $adapterConfig);
        
        return view('go-to-bank',[
            'form' => $form,
        ]);
    }
}
```

### 2- show bank transfer form

Now you can show you `$form` in your `go-to-bank` view file:
```php
<div>
    {!! $form !!}
</div>
```

You can modify bank forms in:
```
resources/views/vendor/larapy
```

### 3- handle callback

After payment, bank call you callback route

```php
use Illuminate\Http\Request;
use PhpMonsters\Larapay\Facades\Larapay;

class YourController extends Controller
{
    public function handleCallback(Request $request)
    {
         try{
            $adapterConfig = [];
            $transaction = Larapay::verifyTransaction($request, $adapterConfig);
            $order = $transaction->model;
            //transaction done. payment is successful         
         } catch (\Exception $e){
            // transaction not complete!!!
            // show error to your user
         }
    }
}
```

If you want to revers transaction and your bank support it, you can do this way:
```php
$transaction->reverseTransaction();
```

## Methods

### Methods available in `Paybel` trait and your order model:
 
* `$order->transactions` : get all transactions of this model
* `$order->accomplishedTransactions`: get all accomplished transactions
* `$order->isPaid()`: return true if this model has at least one accomplished transaction
* `$order->paidAmount()`: return sum of accomplished transactions amount in Rial
* `$order->createTransaction(
                   $paymentGateway,
                   $amount = null,
                   $description = null,
                   array $additionalData = []
               )`:  create a transaction.


### Methods available in `LarapayTransaction`  model:

* `$transaction->model`: return the model that create this transaction. for example `$order`
* `$transaction->reverseTransaction()`: reverse transaction and get back money to user. (if bank support reverse transaction)
* `$transaction->generateForm($autoSubmit = false, $callback = null)`: generate bank transfer form
* `$transaction->gatewayHandler()`: get gatewayHandler for advance use.

### Fields available in `LarapayTransaction`  model:
* `id`
* `created_at`
* `updated_at`

Status in boolean:
* `accomplished`
* `verified`
* `after_verified`
* `reversed`
* `submitted`
* `approved`
* `rejected`

 Gate information:
 * `payment_method`
 * `bank_order_id`
 * `gate_name`
 * `gate_refid`
 * `gate_status`
 * `extra_params`
 * `additional_data`
 
 Order information:
 * `amount`
 * `description`
 * `paid_at`


## LarapayTransaction

You can use `LarapayTransaction` model to find your transaction:

```php
use PhpMonsters\Larapay\Models\LarapayTransaction;

public function getTransaction($transactionId){

    //find single transaction by transaction id
    $transaction = LarapayTransaction::find($transactionId);
    
    //get all accomplished transaction
    $accomplishedTransactions = LarapayTransaction::where('accomplished',true)->get();
    
    //get all reversed transaction
    $reversedTransactions = LarapayTransaction::where('reversed',true)->get();
}
```

This class use SoftDeletes. you can call delete() on your transaction model to softDelete it or forceDelete() to truly remove it from your database.

## Security

If you discover any security related issues, please email a6oozar@gmail.com or milad.kian@gmail.com instead of using the issue tracker.

## Team

This component is developed by the following person(s) and a bunch of [awesome contributors](https://github.com/php-monsters/laravel-online-payment/graphs/contributors).

[![Aboozar Ghaffari](https://avatars2.githubusercontent.com/u/502961?v=3&s=130)](https://github.com/samuraee) | [![Milad Kianmehr](https://avatars3.githubusercontent.com/u/4578704?v=3&s=130)](https://github.com/miladkian) | [![Sina Miandashti](https://avatars3.githubusercontent.com/u/195868?v=3&s=130)](https://github.com/sinamiandashti) | [![XShaan](https://avatars3.githubusercontent.com/u/4527899?v=3&s=130)](https://github.com/xshaan)
| --- | --- | --- | --- |
[Aboozar Ghaffari](https://github.com/samuraee) | [Milad Kianmehr](https://github.com/miladkian) | [Sina Miandashti](https://github.com/sinamiandashti) | [XShaan](https://github.com/xshaan)


## Support This Project

Please contribute in package completion. This is the best support.

## License

The Laravel Online Payment Module is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
 




