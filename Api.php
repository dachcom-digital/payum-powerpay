<?php

namespace DachcomDigital\Payum\Powerpay;

use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use Http\Message\MessageFactory;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;

class Api
{
    protected HttpClientInterface $client;
    protected MessageFactory $messageFactory;

    public const TEST = 'test';
    public const PRODUCTION = 'production';

    protected array $options = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'username',
            'password',
            'merchantId',
            'filialId',
            'terminalId',
            'confirmationMethod'
        ]);

        if (!is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options->toUnsafeArray();
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    public function getConfirmationMethod(): string
    {
        return $this->options['confirmationMethod'];
    }

    /**
     * @throws PowerpayException
     */
    public function generatePaymentModelRequest(ArrayObject $details): array
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><paymentModelsRequest></paymentModelsRequest>');

        $xml->addChild('amount', $details['amount']);
        $xml->addChild('merchantId', $this->options['merchantId']);
        $xml->addChild('filialId', $this->options['filialId']);
        $xml->addChild('terminalId', $this->options['terminalId']);

        try {
            $request = $this->doPaymentModelRequest(['xml' => $xml->asXML()]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @throws PowerpayException
     */
    public function generateReserveRequest(array $transformedTransaction): array
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><cardNumberRequest></cardNumberRequest>');

        $protocol = 'getVirtualCardNumber';
        $version = '2.9';

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('version', $version);

        $mapping = [
            'externalReference' => 'id',
            'amount'            => 'amount',
            'currencyCode'      => 'currency',
            'language'          => 'language',
            'orderIpAddress'    => 'clientIp',
            'gender'            => 'gender',
            'firstName'         => 'firstName',
            'lastName'          => 'lastName',
            'street'            => 'street',
            'city'              => 'city',
            'zip'               => 'zip',
            'country'           => 'country',
            'email'             => 'email',
            'birthdate'         => 'birthdate',
            'phoneNumber'       => 'phoneNumber',
        ];

        foreach ($mapping as $targetRef => $sourceRef) {

            if (!array_key_exists($sourceRef, $transformedTransaction)) {
                continue;
            }

            $data = $transformedTransaction[$sourceRef];

            if ($data === null) {
                continue;
            }

            $xml->addChild($targetRef, $data);

        }

        // from options
        $xml->addChild('merchantId', $this->options['merchantId']);
        $xml->addChild('filialId', $this->options['filialId']);
        $xml->addChild('terminalId', $this->options['terminalId']);

        try {
            $request = $this->doRequest(['xml' => $xml->asXML()]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @throws PowerpayException
     */
    public function generateActivationRequest(ArrayObject $details): array
    {
        $msgNum = uniqid('', false);
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><FinancialRequest></FinancialRequest>');

        $transformedTransaction = $details['transformed_transaction'];

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);

        $xml->addChild('CardNumber', $details['card_number']);
        $xml->addChild('RequestDate', date('YmdHis'));

        $xml->addChild('TransactionType', $details['transaction_type']);

        if (isset($details['payment_model'])) {
            $xml->addChild('PaymentModel', $details['payment_model']);
        }

        $xml->addChild('Currency', $transformedTransaction['currency']);
        $xml->addChild('Amount', $transformedTransaction['amount']);

        $xml->addChild('MerchantId', $this->options['merchantId']);
        $xml->addChild('FilialId', $this->options['filialId']);
        $xml->addChild('TerminalId', $this->options['terminalId']);

        try {
            $request = $this->doRequest(['xml' => $xml->asXML()]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @throws PowerpayException
     */
    public function generateConfirmRequest(ArrayObject $details): array
    {
        $msgNum = uniqid('', false);
        $genDate = date('YmdHis');
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Confirmation></Confirmation>');

        $transformedTransaction = $details['transformed_transaction'];

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);
        $xml->addAttribute('gendate', $genDate);

        $conversion = $xml->addChild('Conversation');

        $financialRequest = $conversion->addChild('FinancialRequest');
        $financialRequest->addAttribute('protocol', $protocol);
        $financialRequest->addAttribute('msgnum', $msgNum);

        $financialRequest->addChild('RequestDate', $genDate);
        $financialRequest->addChild('TransactionType', $details['transaction_type']);
        $financialRequest->addChild('Currency', $transformedTransaction['currency']);
        $financialRequest->addChild('Amount', $transformedTransaction['amount']);

        $financialRequest->addChild('MerchantId', $this->options['merchantId']);
        $financialRequest->addChild('FilialId', $this->options['filialId']);
        $financialRequest->addChild('TerminalId', $this->options['terminalId']);

        $response = $conversion->addChild('Response');
        $response->addAttribute('msgnum', $msgNum);

        $response->addChild('ResponseCode', $details['response_code']);
        $response->addChild('ResponseDate', $details['response_date']);
        $response->addChild('AuthorizationCode', $details['authorization_code']);
        $response->addChild('Balance', $details['balance']);
        $response->addChild('CardNumber', $details['card_number']);
        $response->addChild('ExpirationDate', $details['expiration_date']);
        $response->addChild('Currency', $transformedTransaction['currency']);

        try {
            $request = $this->doRequest(['xml' => $xml->asXML()]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @throws PowerpayException
     */
    public function generateCancelRequest(ArrayObject $details): array
    {
        $msgNum = uniqid('', false);
        $genDate = date('YmdHis');
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Confirmation></Confirmation>');

        $transformedTransaction = $details['transformed_transaction'];

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);
        $xml->addAttribute('gendate', $genDate);

        $conversion = $xml->addChild('Conversation');

        $financialRequest = $conversion->addChild('FinancialRequest');
        $financialRequest->addAttribute('protocol', $protocol);
        $financialRequest->addAttribute('msgnum', $msgNum);

        $financialRequest->addChild('RequestDate', $genDate);
        $financialRequest->addChild('TransactionType', $details['transaction_type']);
        $financialRequest->addChild('Currency', $transformedTransaction['currency']);
        $financialRequest->addChild('Amount', 0);

        $financialRequest->addChild('MerchantId', $this->options['merchantId']);
        $financialRequest->addChild('FilialId', $this->options['filialId']);
        $financialRequest->addChild('TerminalId', $this->options['terminalId']);

        $response = $conversion->addChild('Response');
        $response->addAttribute('msgnum', $msgNum);

        $response->addChild('ResponseCode', $details['response_code']);
        $response->addChild('ResponseDate', $details['response_date']);
        $response->addChild('AuthorizationCode', $details['authorization_code']);
        $response->addChild('Balance', 0);
        $response->addChild('CardNumber', $details['card_number']);
        $response->addChild('ExpirationDate', $details['expiration_date']);
        $response->addChild('Currency', $transformedTransaction['currency']);

        try {
            $request = $this->doRequest(['xml' => $xml->asXML()]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @throws PowerpayException
     */
    public function generateCreditRequest(ArrayObject $details): array
    {
        $msgNum = uniqid('', false);
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><FinancialRequest></FinancialRequest>');

        $transformedTransaction = $details['transformed_transaction'];

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);

        $xml->addChild('CardNumber', $details['card_number']);
        $xml->addChild('RequestDate', date('YmdHis'));

        $xml->addChild('TransactionType', $details['transaction_type']);

        $xml->addChild('Currency', $transformedTransaction['currency']);
        $xml->addChild('Amount', $transformedTransaction['amount']);
        $xml->addChild('ExternalReference', $details['payment_number']);

        $xml->addChild('MerchantId', $this->options['merchantId']);
        $xml->addChild('FilialId', $this->options['filialId']);
        $xml->addChild('TerminalId', $this->options['terminalId']);

        try {
            $request = $this->doRequest(['xml' => $xml->asXML()]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    private function doRequest(array $fields): array
    {
        return $this->request([
            'Content-Type' => 'application/x-www-form-urlencoded'
        ], $this->getApiEndpoint(), $fields);
    }

    private function doPaymentModelRequest(array $fields): array
    {
        return $this->request([
            'Content-Type' => 'text/xml',
            'Accept'       => 'application/xml',
        ], $this->getPaymentModelRequestApiEndpoint(), $fields);
    }

    private function request(array $additionalHeaders, string $endpoint, array $fields)
    {
        $headers = array_merge([
            'Authorization' => 'Basic ' . base64_encode($this->options['username'] . ':' . $this->options['password']),
        ], $additionalHeaders);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->messageFactory->createRequest('POST', $endpoint, $headers, http_build_query($fields));

        $response = $this->client->send($request);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $xmlResponse = $response->getBody()->getContents();

        try {
            $responseData = json_decode(json_encode((array) simplexml_load_string($xmlResponse), JSON_THROW_ON_ERROR), 1, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new LogicException("Response content is not valid xml: \n\n{$xmlResponse}");
        }

        return $responseData;
    }

    private function getApiEndpoint(): string
    {
        if ($this->options['sandbox'] === false) {
            return 'https://gateway.mfgroup.ch';
        }

        return 'https://testgateway.mfgroup.ch';
    }

    private function getPaymentModelRequestApiEndpoint(): string
    {
        if ($this->options['sandbox'] === false) {
            return 'https://gmtech.mfgroup.ch/payment-model';
        }

        return 'https://staging-gmtech.mfgroup.ch/payment-model';
    }
}
