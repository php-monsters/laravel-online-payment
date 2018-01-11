# Laravel Online Payment

Online Payment Module handler for Laravel 5+

## Installation

1.Installing via composer

```bash
composer require iamtartan/laravel-online-payment:"^2.0"
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
	public function setReferenceId($referenceId, $save = true){}

	public function checkForRequestToken(){}

	public function checkForVerify(){}

	public function checkForInquiry(){}

	public function checkForReverse(){}

	public function checkForAfterVerify(){}

	public function setCardNumber($cardNumber){}

	public function setVerified(){}

	public function setAfterVerified(){}

	public function setSuccessful($flag){}

	public function setReversed(){}

	public function getAmount(){}

	public function setPaidAt($time = 'now'){}

	public function setExtra($key, $value, $save = false){}
}
```



## Team

This component is developed by the following person(s) and a bunch of [awesome contributors](https://github.com/iamtartan/laravel-online-payment/graphs/contributors).

[![Aboozar Ghaffari](https://avatars2.githubusercontent.com/u/502961?v=3&s=70)](https://github.com/iamtartan) |
--- |
[Aboozar Ghaffari](https://github.com/iamtartan) |


## Support This Project

[![Donate via Paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LXEL22GFTXTKN)

### License

The Laravel Online Payment Module is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
