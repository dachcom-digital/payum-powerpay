<?php

namespace DachcomDigital\Payum\Powerpay\Request\Api;

use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Generic;

class ReserveAmount extends Generic
{
    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * @param PaymentInterface $payment
     */
    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return mixed
     */
    public function getPayment()
    {
        return $this->payment;
    }
}