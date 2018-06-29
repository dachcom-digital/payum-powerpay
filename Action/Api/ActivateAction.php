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

        $details->validateNotEmpty(['card_number']);

        //set transaction type
        $details['transaction_type'] = 'debit';
        //$details['payment_model'] = ''; // not implemented

        try {
            $result = $this->api->generateActivationRequest($details);

            $details['response_code'] = $result['ResponseCode'];
            $details['response_date'] = $result['ResponseDate'];
            $details['authorization_code'] = $result['AuthorizationCode'];
            $details['currency'] = $result['Currency'];
            $details['balance'] = $result['Balance'];
            $details['card_number'] = $result['CardNumber'];
            $details['expiration_date'] = $result['ExpirationDate'];

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