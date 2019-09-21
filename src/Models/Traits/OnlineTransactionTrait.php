<?php


namespace Tartan\Larapay\Models\Traits;


use Tartan\Zaman\Facades\Zaman;

trait OnlineTransactionTrait
{
    public function setReferenceId($referenceId, $save = true): bool
    {
        $this->gate_refid = $referenceId;
        if ($save) {
            return $this->save();
        } else {
            return $this;
        }
    }

    public function checkForRequestToken(): bool
    {
        return $this->veified != true;
    }

    public function checkForVerify(): bool
    {
        return $this->veified != true;
    }

    public function checkForInquiry(): bool
    {
        return true;
    }

    public function checkForAfterVerify(): bool
    {
        return $this->after_verified != true;
    }

    public function checkForReverse(): bool
    {
        return $this->after_verified == true && $this->reversed != true;
    }

    public function setCardNumber(string $cardNumber, bool $save = true): bool
    {
        return $this->setExtra('customer_card_number', $cardNumber, $save);
    }

    public function setVerified(bool $save = true): bool
    {
        $this->verified = true;

        if ($save) {
            return $this->save();
        }

        return $this;
    }

    public function setAfterVerified(bool $save = true): bool
    {
        $this->after_verified = true;

        if ($save) {
            return $this->save();
        }

        return $this;
    }

    public function setSuccessful($flag, $save = true): bool
    {
        $this->accomplished = boolval($flag);

        if ($save) {
            return $this->save();
        }

        return $this;
    }

    public function setReversed($save = true): bool
    {
        $this->reversed     = true;
        $this->accomplished = false;

        if ($save) {
            return $this->save();
        }

        return $this;
    }

    public function getAmount()
    {
        return abs($this->amount);
    }

    public function getBankOrderId()
    {
        return $this->bank_order_id;
    }

    public function setPaidAt($time = 'now', $save = false)
    {
        $this->paid_at        = date('Y-m-d H:i:s', strtotime($time));
        $this->jalali_paid_at = Zaman::gToj($time, 'yyyyMMddHHmmss', 'en');

        if ($save) {
            return $this->save();
        }

        return $this;
    }


    public function getPayableAmount(): int
    {
        return $this->getAmount();
    }

    public function setRefunded(bool $save = true): bool
    {
        $this->reversed     = true;
        $this->accomplished = false;

        if ($save) {
            return $this->save();
        }

        return $this;
    }

    public function setAccomplished(bool $save = true): bool
    {

        $this->accomplished = true;

        if ($save) {
            return $this->save();
        }

        return $this;
    }

    public function setCallBackParameters(array $parameters, bool $save = true): bool
    {
        $this->extra_params = json_encode($parameters, JSON_UNESCAPED_UNICODE);

        if ($save) {
            return $this->save();
        } else {
            return $this;
        }
    }

    public function setGatewayToken(string $token, bool $save = true): bool
    {

    }

    public function checkForRefund(): bool
    {
        return $this->reverse != true;
    }


    public function setExtra(string $key, $value, bool $save = true): bool
    {
        $value = (array)$value;

        $extra = json_decode($this->extra_params, true);

        if (isset($extra[ $key ])) {
            $oldKey           = $key . '_' . time();
            $extra[ $oldKey ] = $extra[ $key ];
        }

        $extra[ $key ]      = $value;
        $this->extra_params = json_encode($extra, JSON_UNESCAPED_UNICODE);

        if ($save) {
            return $this->save();
        } else {
            return $this;
        }
    }

}
