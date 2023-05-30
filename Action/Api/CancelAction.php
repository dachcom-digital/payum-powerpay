<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\Cancel;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class CancelAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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

        try {
            $result = $this->api->generateCancelRequest($details);

            $resultData = [];
            $resultData['cancel_response_date'] = $result['ResponseDate'];
            $resultData['cancel_skipped'] = false;

            //there was an error.
            if (isset($result['ResponseCode'])) {
                $resultData['cancel_response_code'] = $result['ResponseCode'];
            } elseif (isset($result['Skipped'])) {
                $resultData['cancel_skipped'] = true;
                $resultData['cancel_skipped_reason'] = $result['Skipped']['Reason'];
            } else {
                $resultData['cancel_card_statistics_total_number'] = $result['CardStatistics']['Total']['@attributes']['number'];
                $resultData['cancel_card_statistics_total_amount'] = $result['CardStatistics']['Total']['@attributes']['amount'];
                $resultData['cancel_card_statistics_purchase_number'] = $result['CardStatistics']['Purchase']['@attributes']['number'];
                $resultData['cancel_card_statistics_purchase_amount'] = $result['CardStatistics']['Purchase']['@attributes']['amount'];
                $resultData['cancel_card_statistics_credit_number'] = $result['CardStatistics']['Credit']['@attributes']['number'];
                $resultData['cancel_card_statistics_credit_amount'] = $result['CardStatistics']['Credit']['@attributes']['amount'];
                $resultData['cancel_card_statistics_reversal_number'] = $result['CardStatistics']['Reversal']['@attributes']['number'];
                $resultData['cancel_card_statistics_reversal_amount'] = $result['CardStatistics']['Reversal']['@attributes']['amount'];
            }

            $request->setResult($resultData);

        } catch (PowerpayException $e) {
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    public function supports($request)
    {
        return
            $request instanceof Cancel &&
            $request->getModel() instanceof \ArrayAccess;
    }
}