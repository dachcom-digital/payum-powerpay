<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Request\Api\TransactionTransformer;
use DachcomDigital\Payum\Powerpay\Transaction\Transaction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\GetHttpRequest;

class TransactionTransformerAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param TransactionTransformer $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());


        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();

        $clientIp = null;
        if ($details['client_ip'] === null) {
            $this->gateway->execute($httpRequest = new GetHttpRequest());
            $clientIp = $httpRequest->clientIp;
        }

        $transaction = new Transaction();

        $transaction->setId($payment->getNumber());
        $transaction->setCurrency($payment->getCurrencyCode());
        $transaction->setAmount($payment->getTotalAmount());
        $transaction->setClientIp($clientIp);

        $request->setTransaction($transaction);
    }

    public function supports($request)
    {
        return
            $request instanceof TransactionTransformer &&
            $request->getFirstModel() instanceof PaymentInterface;
    }
}