<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\Cancel;
use DachcomDigital\Payum\Powerpay\Request\Api\CreditAmount;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class CreditAmountAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * @param Cancel $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty(['card_number', 'transformed_transaction']);

        //set transaction type
        $details['transaction_type'] = 'credit';

        try {

            $resultData = [];

            $result = $this->api->generateCreditRequest($details);

            $resultData['credit_response_code'] = $result['ResponseCode'];
            $resultData['credit_response_date'] = $result['ResponseDate'];
            $resultData['credit_authorization_code'] = $result['AuthorizationCode'];
            $resultData['credit_currency'] = $result['Currency'];
            $resultData['credit_balance'] = $result['Balance'];
            $resultData['credit_card_number'] = $result['CardNumber'];
            $resultData['credit_expiration_date'] = $result['ExpirationDate'];

            $request->setResult($resultData);

        } catch (PowerpayException $e) {
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    public function supports($request)
    {
        return
            $request instanceof CreditAmount &&
            $request->getModel() instanceof \ArrayAccess;
    }
}