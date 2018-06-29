<?php

namespace DachcomDigital\Payum\Powerpay;

use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
use DachcomDigital\Payum\Powerpay\Request\Api\Transformer\CustomerTransformer;
use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    const TEST = 'test';

    const PRODUCTION = 'production';

    // parameters that will be included in the SHA-OUT Hash
    protected $signatureParams = [

    ];

    protected $options = [
        'environment' => self::TEST
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
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

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return string
     */
    public function getConfirmationMethod()
    {
        return $this->options['confirmationMethod'];
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws PowerpayException
     */
    public function generatePaymentModelRequest(ArrayObject $details)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><paymentModelsRequest></paymentModelsRequest>');

        $xml->addChild('amount', $details['amount']);
        $xml->addChild('merchantId', $this->options['merchantId']);
        $xml->addChild('filialId', $this->options['filialId']);
        $xml->addChild('terminalId', $this->options['terminalId']);

        try {
            $request = $this->doPaymentModelRequest([
                'xml' => $xml->asXML()
            ]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;

    }

    /**
     * @param ArrayObject         $details
     * @param CustomerTransformer $customerTransformer
     * @return array
     * @throws PowerpayException
     */
    public function generateReserveRequest(ArrayObject $details, CustomerTransformer $customerTransformer)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><cardNumberRequest></cardNumberRequest>');

        $protocol = 'getVirtualCardNumber';
        $version = '2.9';

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('version', $version);

        // from details
        $xml->addChild('externalReference', $details['payment_number']);
        $xml->addChild('orderIpAddress', $details['client_ip']);

        // from customer transformer
        $xml->addChild('gender', $customerTransformer->getGender());
        $xml->addChild('firstName', $customerTransformer->getFirstName());
        $xml->addChild('lastName', $customerTransformer->getLastName());
        $xml->addChild('street', $customerTransformer->getStreet());
        $xml->addChild('city', $customerTransformer->getCity());
        $xml->addChild('zip', $customerTransformer->getZip());
        $xml->addChild('country', $customerTransformer->getCountry());
        $xml->addChild('language', $customerTransformer->getLanguage());
        $xml->addChild('email', $customerTransformer->getEmail());
        $xml->addChild('birthdate', $customerTransformer->getBirthDate());

        // from options
        $xml->addChild('merchantId', $this->options['merchantId']);
        $xml->addChild('filialId', $this->options['filialId']);
        $xml->addChild('terminalId', $this->options['terminalId']);

        // from details
        $xml->addChild('amount', $details['amount']);
        $xml->addChild('currencyCode', $details['currency_code']);

        try {
            $request = $this->doRequest([
                'xml' => $xml->asXML()
            ]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws PowerpayException
     */
    public function generateActivationRequest(ArrayObject $details)
    {
        $msgNum = uniqid();
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><FinancialRequest></FinancialRequest>');

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);

        $xml->addChild('CardNumber', $details['card_number']);
        $xml->addChild('RequestDate', date('YmdHis'));

        $xml->addChild('TransactionType', $details['transaction_type']);

        if (isset($details['payment_model'])) {
            $xml->addChild('PaymentModel', $details['payment_model']);
        }

        $xml->addChild('Currency', $details['currency_code']);
        $xml->addChild('Amount', $details['amount']);

        $xml->addChild('MerchantId', $this->options['merchantId']);
        $xml->addChild('FilialId', $this->options['filialId']);
        $xml->addChild('TerminalId', $this->options['terminalId']);

        try {
            $request = $this->doRequest([
                'xml' => $xml->asXML()
            ]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;

    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws PowerpayException
     */
    public function generateConfirmRequest(ArrayObject $details)
    {
        $msgNum = uniqid();
        $genDate = date('YmdHis');
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Confirmation></Confirmation>');

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);
        $xml->addAttribute('gendate', $genDate);

        $conversion = $xml->addChild('Conversation');

        $financialRequest = $conversion->addChild('FinancialRequest');
        $financialRequest->addAttribute('protocol', $protocol);
        $financialRequest->addAttribute('msgnum', $msgNum);

        $financialRequest->addChild('RequestDate', $genDate);
        $financialRequest->addChild('TransactionType', $details['transaction_type']);
        $financialRequest->addChild('Currency', $details['currency']);
        $financialRequest->addChild('Amount', $details['amount']);

        $financialRequest->addChild('MerchantId', $this->options['merchantId']);
        $financialRequest->addChild('FilialId', $this->options['filialId']);
        $financialRequest->addChild('TerminalId', $this->options['terminalId']);

        $response = $conversion->addChild('Response');
        $response->addAttribute('msgnum', $msgNum);

        $response->addChild('ResponseCode', $details['response_code']);
        $response->addChild('ResponseDate', $details['response_date']);
        $response->addChild('AuthorizationCode', $details['authorization_code']);
        $response->addChild('Currency', $details['currency']);
        $response->addChild('Balance', $details['balance']);
        $response->addChild('CardNumber', $details['card_number']);
        $response->addChild('ExpirationDate', $details['expiration_date']);

        try {
            $request = $this->doRequest([
                'xml' => $xml->asXML()
            ]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws PowerpayException
     */
    public function generateCancelRequest(ArrayObject $details)
    {
        $msgNum = uniqid();
        $genDate = date('YmdHis');
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Confirmation></Confirmation>');

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);
        $xml->addAttribute('gendate', $genDate);

        $conversion = $xml->addChild('Conversation');

        $financialRequest = $conversion->addChild('FinancialRequest');
        $financialRequest->addAttribute('protocol', $protocol);
        $financialRequest->addAttribute('msgnum', $msgNum);

        $financialRequest->addChild('RequestDate', $genDate);
        $financialRequest->addChild('TransactionType', $details['transaction_type']);
        $financialRequest->addChild('Currency', $details['currency']);
        $financialRequest->addChild('Amount', 0);

        $financialRequest->addChild('MerchantId', $this->options['merchantId']);
        $financialRequest->addChild('FilialId', $this->options['filialId']);
        $financialRequest->addChild('TerminalId', $this->options['terminalId']);

        $response = $conversion->addChild('Response');
        $response->addAttribute('msgnum', $msgNum);

        $response->addChild('ResponseCode', $details['response_code']);
        $response->addChild('ResponseDate', $details['response_date']);
        $response->addChild('AuthorizationCode', $details['authorization_code']);
        $response->addChild('Currency', $details['currency']);
        $response->addChild('Balance', 0);
        $response->addChild('CardNumber', $details['card_number']);
        $response->addChild('ExpirationDate', $details['expiration_date']);

        try {
            $request = $this->doRequest([
                'xml' => $xml->asXML()
            ]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws PowerpayException
     */
    public function generateCreditRequest(ArrayObject $details)
    {
        $msgNum = uniqid();
        $protocol = 'PaymentServer_V2_9';

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><FinancialRequest></FinancialRequest>');

        $xml->addAttribute('protocol', $protocol);
        $xml->addAttribute('msgnum', $msgNum);

        $xml->addChild('CardNumber', $details['card_number']);
        $xml->addChild('RequestDate', date('YmdHis'));

        $xml->addChild('TransactionType', $details['transaction_type']);

        $xml->addChild('Currency', $details['currency_code']);
        $xml->addChild('Amount', $details['amount']);
        $xml->addChild('ExternalReference', $details['payment_number']);

        $xml->addChild('MerchantId', $this->options['merchantId']);
        $xml->addChild('FilialId', $this->options['filialId']);
        $xml->addChild('TerminalId', $this->options['terminalId']);

        try {
            $request = $this->doRequest([
                'xml' => $xml->asXML()
            ]);
        } catch (\Exception $e) {
            throw new PowerpayException($e->getMessage());
        }

        return $request;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function doRequest(array $fields)
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->options['username'] . ':' . $this->options['password']),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->messageFactory->createRequest('POST', $this->getApiEndpoint(), $headers, http_build_query($fields));

        $response = $this->client->send($request);
        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $xmlResponse = $response->getBody()->getContents();

        try {
            $responseData = json_decode(json_encode((array)simplexml_load_string($xmlResponse)), 1);
        } catch (\Exception $e) {
            throw new LogicException("Response content is not valid xml: \n\n{$xmlResponse}");
        }

        return $responseData;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function doPaymentModelRequest(array $fields)
    {
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->options['username'] . ':' . $this->options['password']),
            'Content-Type'  => 'text/xml',
            'Accept'        => 'application/xml',
        ];

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->messageFactory->createRequest('POST', $this->getPaymentModelRequestApiEndpoint(), $headers, http_build_query($fields));

        $response = $this->client->send($request);
        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $xmlResponse = $response->getBody()->getContents();

        try {
            $responseData = json_decode(json_encode((array)simplexml_load_string($xmlResponse)), 1);
        } catch (\Exception $e) {
            throw new LogicException("Response content is not valid xml: \n\n{$xmlResponse}");
        }

        return $responseData;
    }

    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        if ($this->options['sandbox'] === false) {
            return 'https://gateway.mfgroup.ch';
        }

        return 'https://testgateway.mfgroup.ch';
    }

    /**
     * @return string
     */
    public function getPaymentModelRequestApiEndpoint()
    {
        if ($this->options['sandbox'] === false) {
            return 'https://gmtech.mfgroup.ch/payment-model';
        }

        return 'https://staging-gmtech.mfgroup.ch/payment-model';
    }
}
