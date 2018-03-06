<?php

namespace DachcomDigital\Payum\Powerpay\Request\Api;

use Payum\Core\Request\Generic;

class Confirm extends Generic
{
    /**
     * @var array
     */
    protected $result;

    /**
     * @param $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

}