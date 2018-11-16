<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Api;
use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\Confirm;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class ConfirmAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * @param Confirm $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty(['card_number']);

        $details['payment_confirmed'] = false;

        try {
            $result = $this->api->generateConfirmRequest($details);

            $resultData = [];
            $resultData['response_date'] = $result['ResponseDate'];
            $resultData['skipped'] = false;

            //there was an error.
            if (isset($result['ResponseCode'])) {
                $resultData['response_code'] = $result['ResponseCode'];
            } else {
                if (isset($result['Skipped'])) {
                    $resultData['skipped'] = true;
                    $resultData['skipped_reason'] = $result['Skipped']['Reason'];
                } else {
                    $details['payment_confirmed'] = true;
                    $resultData['card_statistics_name'] = $result['CardStatistics']['@attributes']['name'];
                    $resultData['card_statistics_type'] = $result['CardStatistics']['@attributes']['type'];
                    $resultData['card_statistics_currency'] = $result['CardStatistics']['@attributes']['currency'];
                    $resultData['card_statistics_total_number'] = $result['CardStatistics']['Total']['@attributes']['number'];
                    $resultData['card_statistics_total_amount'] = $result['CardStatistics']['Total']['@attributes']['amount'];
                    $resultData['card_statistics_purchase_number'] = $result['CardStatistics']['Purchase']['@attributes']['number'];
                    $resultData['card_statistics_purchase_amount'] = $result['CardStatistics']['Purchase']['@attributes']['amount'];
                    $resultData['card_statistics_credit_number'] = $result['CardStatistics']['Credit']['@attributes']['number'];
                    $resultData['card_statistics_credit_amount'] = $result['CardStatistics']['Credit']['@attributes']['amount'];
                    $resultData['card_statistics_reversal_number'] = $result['CardStatistics']['Reversal']['@attributes']['number'];
                    $resultData['card_statistics_reversal_amount'] = $result['CardStatistics']['Reversal']['@attributes']['amount'];
                }
            }

            $request->setResult($resultData);

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
            $request instanceof Confirm &&
            $request->getModel() instanceof \ArrayAccess;
    }
}