<?php
namespace Tartan\Larapay\Adapter;

class Melli extends AdapterAbstract implements AdapterInterface
{
    protected $_END_POINT      = 'https://damoon.bankmelli-iran.com/DamoonPrePaymentController';

    protected $_VERIFY         = 'https://damoon.bankmelli-iran.com/DamoonVerificationController';

    protected $_TEST_END_POINT = 'http://banktest.ir/gateway/damoonprlarapay';

    protected $_TEST_VERIFY    = 'http://banktest.ir/gateway/damoonverification';

    protected $_PAYMENT_FORM   = 'x_show_form';

    const CURRENCY = 'Rial';

    public $reverseSupport = false;

    public $validateReturnsAmount = false;

    public function setOptions(array $options = array())
    {
        parent::setOptions($options);
        foreach ($this->_config as $name => $value)
        {
            switch ($name) {
            case 'x_fp_sequence':
                $this->reservationnumber = $value;
                break;
            case 'x_trans_id':
                $this->referenceid = $value;
                break;
            case 'x_login':
                $this->merchantcode = $value;
                break;
            case 'x_amount':
                $this->amount = $value;
                break;
            }
        }
    }

    private function _signRequest()
    {
        if (strlen($this->x_fp_timestamp) < 5)
            $this->x_fp_timestamp = time();

        $data = $this->merchantcode . '^' . $this->reservationnumber . '^' .
            $this->x_fp_timestamp . '^' . $this->amount . '^' . self::CURRENCY;

        return bin2hex(mhash(MHASH_MD5, $data, $this->key));
    }

    private function _validateHash($options)
    {
        $originalHash = array_pop($options);
        if (isset($options['x_currency_code'])) {
            $options['x_currency_code'] = self::CURRENCY;
        }
        $data = implode('^', array_values($options));
        $realHash = bin2hex(mhash(MHASH_MD5, $data, $this->key));

        if ($originalHash !== $realHash)
            return false;
        else
            return true;
    }

    public function getInvoiceId()
    {
        return $this->_config['reservationnumber'];
    }

    public function getReferenceId()
    {
        return $this->_config['referenceid'];
    }

    public function getStatus()
    {
    }

    public function doGenerateForm(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(array(
            'reservationnumber', 'merchantcode', 'amount', 'key'
        ));

        $signedData = $this->_signRequest();

        $view = Zend_Layout::getMvcInstance()->getView();

        $form = '<form id="payment" method="post" action="' . $this->getEndPoint() . '">';

        $hash        = $view->formHidden('x_fp_hash',                  $signedData);
        $login       = $view->formHidden('x_login',            $this->merchantcode);
        $amount      = $view->formHidden('x_amount',                 $this->amount);
        $submit      = $view->formSubmit('submit',                              '');
        $currency    = $view->formHidden('x_currency_code',         self::CURRENCY);
        $showForm    = $view->formHidden('x_show_form',        $this->PAYMENT_FORM);
        $sequence    = $view->formHidden('x_fp_sequence', $this->reservationnumber);
        $timestamp   = $view->formHidden('x_fp_timestamp',   $this->x_fp_timestamp);
        $description = $view->formHidden('x_description',                    "ADP");

        $form .= $sequence . $timestamp . $hash . $description . $login . $amount .
            $currency . $showForm . $submit . '</form>';

        return $form;
    }

    public function doVerifyTransaction(array $options = array())
    {
        $this->setOptions($options);
        $this->_checkRequiredOptions(array(
            'reservationnumber', 'merchantcode', 'amount', 'key'
        ));
        $signedData = $this->_signRequest();

        $adapter = new Zend_Http_Client_Adapter_Curl();
        $adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        $client = new Zend_Http_Client(
            (APPLICATION_ENV == 'production') ? $this->_VERIFY : $this->_TEST_VERIFY,
            array(
                'adapter' => $adapter,
            )
        );
        $client->setMethod(Zend_Http_Client::POST);
        $client->setParameterPost('x_fp_hash',       $signedData);
        $client->setParameterPost('x_login',         $this->merchantcode);
        $client->setParameterPost('x_amount',        $this->amount);
        $client->setParameterPost('x_currency_code', self::CURRENCY);
        $client->setParameterPost('x_fp_sequence',   $this->reservationnumber);
        $client->setParameterPost('x_fp_timestamp',  $this->x_fp_timestamp);
        $client->setParameterPost('x_description',   "ADP");
        $result = $client->request();
        $body   = $result->getBody();

        $contents = explode("\r\n", $body);
        preg_match_all('|(?<key>\w+)=(?<value>\w+)?&?|', $contents[0], $response);
        $response = array_combine($response['key'], $response['value']);

        if ($this->_validateHash($response) && $response['x_response_code'] == '1')
            return 1;
        else
            return 0;
    }

    public function doReverseTransaction(array $options = [])
    {
        throw new NotImplementedException();
    }
}
