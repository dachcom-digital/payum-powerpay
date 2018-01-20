<?php

namespace DachcomDigital\Payum\Powerpay\Exception;

class PowerpayException extends \Exception
{
    public function __toString()
    {
        return $this->getMessage() . ' (#' . $this->code . ')';
    }
}