<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\Activate;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class ActivateAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * @param Activate $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $details['transactionType'] = 'debit';

        $details->validateNotEmpty(['cardNumber']);

        try {
            $result = $this->api->generateActivationRequest($details);

            $details['responseCode'] = $result['ResponseCode'];
            $details['responseDate'] = $result['ResponseDate'];
            $details['authorizationCode'] = $result['AuthorizationCode'];
            $details['currency'] = $result['Currency'];
            $details['balance'] = $result['Balance'];
            $details['cardNumber'] = $result['CardNumber'];
            $details['expirationDate'] = $result['ExpirationDate'];

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
            $request instanceof Activate &&
            $request->getModel() instanceof \ArrayAccess;
    }
}