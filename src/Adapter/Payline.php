<?php
namespace Tartan\Larapay\Adapter;

class Payline extends AdapterAbstract implements AdapterInterface
{
    const TOKEN   = 1;
    const CONFIRM = 2;

    const ERROR_S1_0 = 'خطای ناشناخته در دریافت کد خرید';
    const ERROR_S1_1 = 'api ‫ ارسالی با نوع‬api ‫ تعریف شده در‬payline ‫سازگار نیست‬.';
    const ERROR_S1_2 = '‫ مقدار‬amount ‫ ریال است‬1000 ‫داده عددي نمی باشد و یا کمتر';
    const ERROR_S1_3 = '‫ مقدار‬redirect ‫ رشته‬null ‫است‬.';
    const ERROR_S1_4 = '‫درگاهی با اطلاعات ارسالی شما یافت نشده و یا در حالت انتظار می باشد‬.';

    const ERROR_S2_0 = 'خطای ناشناخته در بررسی صحت تراکنش';
    const ERROR_S2_2 = 'trans_id ‫ارسال شده معتبر نمی باشد‬.';
    const ERROR_S2_1 = 'api ‫ ارسالی با نوع‬api ‫ تعریف شده در‬payline ‫سازگار ن یست‬.';
    const ERROR_S2_3 =  'id_get ‫ارسالی معتبر نمی باشد‬.';
    const ERROR_S2_4 = '‫چنین تراکنشی در سیستم وجود ندارد و یا موفقیت آمیز نبوده است‬.';

    const API_GET_TOKEN_URL      = '‫‪http://payline.ir/payment-test/gateway-send‬‬';
    const API_CONFIRM_URL        = '‫‪http://payline.ir/payment-test/gateway-result-second‬‬';

    const TEST_API_GET_TOKEN_URL = 'https://banktest.ir/gateway/payline/gateway-send';
    const TEST_API_CONFIRM_URL   = 'https://banktest.ir/gateway/payline/gateway-result-second';

    /**
     * @var Zend_Http_Client
     */
    protected $client;
    protected $_END_POINT             = "http://payline.ir/payment-test/gateway-%s";
    protected $_MOBILE_END_POINT      = "http://payline.ir/payment-test/gateway-%s";

    protected $_TEST_END_POINT        = 'http://banktest.ir/gateway/payline/payment/gateway-%s';
    protected $_TEST_MOBILE_END_POINT = 'http://banktest.ir/gateway/payline/payment/gateway-%s';

    public $reverseSupport = false;
    public $validateReturnsAmount = false;

    public function init()
    {
        $this->client = new Zend_Http_Client();
    }

    public function setOptions(array $options = array())
    {
        parent::setOptions($options);
    }

    public function getInvoiceId()
    {
        if (!isset($this->_config['invoice_id'])) {
            return null;
        }
        return $this->_config['invoice_id'];
    }

    public function getReferenceId()
    {
        if (!isset($this->_config['trans_id'])) {
            return null;
        }
        return $this->_config['trans_id'];
    }

    public function doGenerateForm(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(array(
            'merchant_code', 'amount', 'redirectaddress'
        ));

        $this->log($this->getApiUrl(static::TOKEN));

        try {
            $client = $this->getClient();
            $client->setUri($this->getApiUrl(static::TOKEN));
            $client->setMethod(Zend_Http_Client::POST);
            $client->setParameterPost(array(
                'api'      => $this->_config['merchant_code'],
                'amount'   => $this->_config['amount'],
                'redirect' => $this->_config['redirectaddress']
            ));
            $response = $client->request();

        } catch (Zend_Exception $e) {
            $this->log($e->getMessage());
            throw new Exception('HTTP Request Exception: ' . $e->getMessage());
        }


        if ($response->getStatus() == 200)
        {
            $code = $response->getBody();

            // VALID TOKEN RETURNED
            if (is_numeric($code) && $code > 0)
            {
                $form = '<form id="gotobank-form" method="post" action="' . sprintf($this->getEndPoint(), intval($code)) . '" class="form-horizontal">';

                $label =  isset($this->_config['submitlabel']) ? $this->_config['submitlabel'] : '';

                if (Zend_Registry::isRegistered('Zend_Translate')) {
                    $label = Zend_Registry::get('Zend_Translate')->translate($label);
                }

                $submit = sprintf('<div class="control-group"><div class="controls"><input type="submit" class="btn btn-success" value="%s"></div></div>', $label);

                $form .= $submit;
                $form .= '</form>';

                return $form;
            } else {
                switch($code){
                    case '-1':
                        $error = static::ERROR_S1_1;
                        break;
                    case '-2':
                        $error = static::ERROR_S1_2;
                        break;
                    case '-3':
                        $error = static::ERROR_S1_3;
                        break;
                    case '-4':
                        $error = static::ERROR_S1_4;
                        break;
                    default:
                        $error = static::ERROR_S1_0;
                }

                $this->log($error);
                throw new Exception($error);
            }
        } else {
            $this->log($response->getMessage());
            throw new Exception($response->getMessage(), $response->getStatus());
        }
    }

    public function doVerifyTransaction(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(array('merchant_code', 'trans_id', 'id_get'));

        try
        {
            $client = $this->getClient();
            $client->setUri($this->getApiUrl(static::CONFIRM));
            $client->setMethod(Zend_Http_Client::GET);
            $client->setParameterGet(array(
                'api'      => $this->_config['merchant_code'],
                'id_get'   => $this->_config['id_get'],
                'trans_id' => $this->_config['trans_id']
            ));
            $response = $client->request();


            if ($response->getStatus() == 200)
            {
                $code = $response->getBody();

                // VALID TOKEN RETURNED
                if (is_numeric($code) && $code > 0)
                {
                    // 1 is OK
                    return $code;
                } else {
                    switch($code){
                        case '-1':
                            $error = static::ERROR_S2_1;
                            break;
                        case '-2':
                            $error = static::ERROR_S2_2;
                            break;
                        case '-3':
                            $error = static::ERROR_S2_3;
                            break;
                        case '-4':
                            $error = static::ERROR_S2_4;
                            break;
                        default:
                            $error = static::ERROR_S2_0;
                    }

                    $this->log($error);
                    throw new Exception($error);
                }
            } else {
                $this->log($response->getMessage());
                throw new Exception($response->getMessage(), $response->getStatus());
            }


        } catch (Zend_Exception $e) {
            $this->log($e->getMessage());
            throw new Exception('HTTP Request Exception: ' . $e->getMessage());
        }


    }

    public function getStatus()
    {
        return false;
    }

    public function doReverseTransaction(array $options = array())
    {
        return false;
    }

    private function getClient()
    {
        return $this->client;
    }

    private function getApiUrl($step)
    {
        if (config('app.env') == 'production'){
            if ($step == 1) {
                return static::API_GET_TOKEN_URL;
            } else {
                return static::API_CONFIRM_URL;
            }
        } else {
            if ($step == 1) {
                return static::TEST_API_GET_TOKEN_URL;
            } else {
                return static::TEST_API_CONFIRM_URL;
            }
        }
    }
}
