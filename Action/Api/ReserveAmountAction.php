<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\ReserveAmount;
use DachcomDigital\Payum\Powerpay\Request\Api\Transformer\CustomerTransformer;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class ReserveAmountAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use PowerpayAwareTrait;

    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }
        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param ReserveAmount $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());

        $transformCustomer = new CustomerTransformer($request->getPayment());
        $this->gateway->execute($transformCustomer);

        try {

            $result = $this->api->generateReserveRequest($details, $transformCustomer);

            $details['mf_reference'] = $result['mfReference'];
            $details['available_credit'] = $result['availableCredit'];
            $details['maximal_credit'] = $result['maximalCredit'];
            $details['credit_refusal_reason'] = $result['creditRefusalReason'];
            $details['card_number'] = $result['cardNumber'];
            $details['response_code'] = $result['responseCode'];

        } catch (PowerpayException $e) {
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof ReserveAmount &&
            $request->getModel() instanceof \ArrayAccess;
    }
}