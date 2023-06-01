<?php

namespace DachcomDigital\Payum\Powerpay\Request\Api;

use Payum\Core\Request\Generic;

class Confirm extends Generic
{
    protected array $result = [];

    public function setResult($result): void
    {
        $this->result = $result;
    }

    public function getResult(): array
    {
        return $this->result;
    }

}