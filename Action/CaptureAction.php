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
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

/**
 * @property Api $api
 */
class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

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
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $payment = $request->getFirstModel();

        if (false == $details['client_ip']) {
            $this->gateway->execute($httpRequest = new GetHttpRequest());
            $details['client_ip'] = $httpRequest->clientIp;
        }

        if (!isset($details['card_number']) || $details['card_number'] === false) {
            $reserveAmount = new ReserveAmount($details);
            $reserveAmount->setPayment($payment);
            $this->gateway->execute($reserveAmount);
        }

        if (isset($details['card_number']) && $details['card_number'] !== false
            && $details['response_code'] === StatusAction::CHECK_CREDIT_OK
            && $details['credit_refusal_reason'] === StatusAction::REFUSAL_REASON_NONE
        ) {
            $this->gateway->execute(new Activate($details));
        }

        if (isset($details['card_number']) && $details['card_number'] !== false
            && $details['response_code'] === StatusAction::APPROVED
            && $this->api->getConfirmationMethod() === 'instant'
        ) {
            $confirm = new Confirm($request->getToken());
            $confirm->setModel($details);
            $this->gateway->execute($confirm);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
