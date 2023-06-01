<?php

namespace DachcomDigital\Payum\Powerpay\Request\Api;

use DachcomDigital\Payum\Powerpay\Transaction\Transaction;
use Payum\Core\Request\Generic;

class TransactionTransformer extends Generic
{
    protected Transaction $transaction;

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function toArray(): array
    {
        return $this->transaction->toArray();
    }

}