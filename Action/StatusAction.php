<?php

namespace DachcomDigital\Payum\Powerpay\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    /**
     * Response Codes (Card Number Request)
     */

    /*
     * The request was processed successfully
     */
    const CHECK_CREDIT_OK = 'OK';

    /*
     * The XML sent in the request is not compliant
     * with the rules defined in the specification
     */
    const CHECK_XML_INVALID = 'XML CANNOT BE VALIDATED';

    /*
     * The payment server was not able to
     * answer the request due to not working processes
     */
    const CHECK_INTERNAL_ERROR = 'INTERNAL ERROR';

    /**
     * creditRefusalReason Codes
     */

    /*
     * The customer was accepted and there is no reason
     * to refuse the customer.
     */
    const REFUSAL_REASON_NONE = 'None';

    /*
     * Customer has to be redirected to checkoutUrl
     * to complete additional checks (for profiles with Advance Checkout).
     */
    const REFUSAL_REASON_PENDING = 'Pending';

    /*
     * The customer was declined because
     * it could not be found in MF Group systems.
     */
    const REFUSAL_REASON_UNKNOWN_ADDRESS = 'Unknown address';

    /*
     * The customer was found in MF Group systems
     * but for some other reason could not be accepted.
     */
    const REFUSAL_REASON_OTHER = 'Other';

    /**
     * Response Codes (Financial Request [Debit] Request)
     */

    const APPROVED = '00';
    const UNKNOWN_CARD = '01';
    const UNKNOWN_MERCHANT = '03';
    const UNKNOWN_FILIAL = '04';
    const UNKNOWN_TERMINAL = '05';
    const FUNDS_TOO_LOW = '06';
    const FUNDS_TOO_HIGH = '07';
    const INVALID_AUTHORIZATION_CODE = '08';
    const BLOCKED_CARD = '11';
    const EXPIRED_CARD = '12';
    const VALIDATION_ERROR = '13';
    const INTERNAL_ERROR = '14';
    const FORBIDDEN_OPERATION = '16';

    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (isset($details['credit_response_code']) || isset($details['cancel_response_code'])) {
            return;
        }

        if (empty($details['response_code'])) {
            $request->markNew();
            return;
        }

        // card number request response codes are string
        if (!is_numeric($details['response_code'])) {

            switch ($details['response_code']) {
                case self::CHECK_CREDIT_OK:
                    switch ($details['credit_refusal_reason']) {
                        case self::REFUSAL_REASON_NONE:
                            $request->markAuthorized();
                        break;
                        case self::REFUSAL_REASON_PENDING:
                            $request->markAuthorized();
                        break;
                        case self::REFUSAL_REASON_UNKNOWN_ADDRESS:
                            $request->markFailed();
                        break;
                        case self::REFUSAL_REASON_OTHER:
                        $request->markFailed();
                        break;
                    }
                    break;
                case self::CHECK_XML_INVALID:
                case self::CHECK_INTERNAL_ERROR:
                    $request->markFailed();
                    break;
            }

            return;

        }

         // financial request response codes are numeric
        switch ($details['response_code']) {

            case self::APPROVED:
                $request->markCaptured();
                break;
            case self::UNKNOWN_CARD:
            case self::UNKNOWN_MERCHANT:
            case self::UNKNOWN_FILIAL:
            case self::UNKNOWN_TERMINAL:
            case self::FUNDS_TOO_LOW:
            case self::FUNDS_TOO_HIGH:
            case self::INVALID_AUTHORIZATION_CODE:
            case self::BLOCKED_CARD:
            case self::EXPIRED_CARD:
            case self::VALIDATION_ERROR:
            case self::INTERNAL_ERROR:
            case self::FORBIDDEN_OPERATION:
                $request->markFailed();
                break;
            default:
                $request->markUnknown();
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
