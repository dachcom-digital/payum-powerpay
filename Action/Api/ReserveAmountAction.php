<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\ReserveAmount;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class ReserveAmountAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use PowerpayAwareTrait;
    use ApiAwareTrait {
        setApi as _setApi;
    }

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function setApi($api): void
    {
        $this->_setApi($api);
    }

    /**
     * @param ReserveAmount $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());

        $detailsStructured = $details->toUnsafeArray();
        if (!array_key_exists('transformed_transaction', $detailsStructured)) {
            $details['error'] = 'No transformed transaction found, cannot proceed';

            return;
        }

        try {

            $result = $this->api->generateReserveRequest($detailsStructured['transformed_transaction']);

            $details['available_credit'] = $result['availableCredit'] ?? false;
            $details['maximal_credit'] = $result['maximalCredit'] ?? false;
            $details['card_number'] = $result['cardNumber'] ?? false;
            $details['payment_models'] = $result['paymentModels'] ?? false;
            $details['response_code'] = $result['responseCode'] ?? false;
            $details['credit_refusal_reason'] = $result['creditRefusalReason'] ?? false;

        } catch (PowerpayException $e) {
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof ReserveAmount &&
            $request->getModel() instanceof \ArrayAccess;
    }
}