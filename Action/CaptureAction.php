<?php

namespace DachcomDigital\Payum\Powerpay\Action;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Request\Api\Activate;
use DachcomDigital\Payum\Powerpay\Request\Api\Confirm;
use DachcomDigital\Payum\Powerpay\Request\Api\ReserveAmount;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;
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
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (!$details->offsetExists('card_number')) {
            $this->gateway->execute(new ReserveAmount($details));
        }

        if ($details->offsetExists('card_number')
            && $details['response_code'] === StatusAction::CHECK_CREDIT_OK
            && $details['credit_refusal_reason'] === StatusAction::REFUSAL_REASON_NONE
        ) {
            $this->gateway->execute(new Activate($details));
        }

        if ($details->offsetExists('card_number')
            && $details['response_code'] === StatusAction::APPROVED
            && $this->api->getConfirmationMethod() === 'instant'
        ) {
            $confirm = new Confirm($request->getToken());
            $confirm->setModel($details);
            $this->gateway->execute($confirm);
        }
    }

    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
