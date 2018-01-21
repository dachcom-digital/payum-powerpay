<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Request\Api\PopulatePowerpayFromDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class PopulatePowerpayFromDetailsAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

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
     * @param PopulatePowerpayFromDetails $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return $request instanceof PopulatePowerpayFromDetails;
    }
}