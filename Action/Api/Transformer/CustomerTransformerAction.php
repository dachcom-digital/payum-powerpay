<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api\Transformer;

use DachcomDigital\Payum\Powerpay\Request\Api\Transformer\CustomerTransformer;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;

class CustomerTransformerAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param CustomerTransformer $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CustomerTransformer &&
            $request->getModel() instanceof \ArrayAccess;
    }
}