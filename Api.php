<?php

namespace DachcomDigital\Payum\Powerpay;

use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;
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
        ]);

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws PowerpayException
     */
    public function generateReserveRequest(ArrayObject $details)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><cardNumberRequest protocol="getVirtualCardNumber" version="2.9"></cardNumberRequest>');

        $xml->addChild('externalReference', $details['externalReference']);
        $xml->addChild('orderIpAddress', $details['clientIp']);
        $xml->addChild('gender', $details['address']['gender']);
        $xml->addChild('firstName', $details['address']['firstName']);
        $xml->addChild('lastName', $details['address']['lastName']);
        $xml->addChild('street', $details['address']['street']);
        $xml->addChild('city', $details['address']['city']);
        $xml->addChild('zip', $details['address']['zip']);
        $xml->addChild('country', $details['address']['country']);
        $xml->addChild('language', $details['language']);
        $xml->addChild('email', $details['address']['email']);
        $xml->addChild('birthdate', $details['birthdate']);
        $xml->addChild('merchantId', $this->options['merchantId']);
        $xml->addChild('filialId', $this->options['filialId']);
        $xml->addChild('terminalId', $this->options['terminalId']);
        $xml->addChild('amount', $details['amount']);
        $xml->addChild('currencyCode', $details['currencyCode']);

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
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><FinancialRequest protocol="PaymentServer_V2_9" msgnum="11111"></FinancialRequest>');
        $xml->addChild('CardNumber', $details['cardNumber']);
        $xml->addChild('RequestDate', date('YmdHis'));
        $xml->addChild('TransactionType', $details['transactionType']);
        $xml->addChild('Currency', $details['currencyCode']);
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
            $xml = simplexml_load_string($xmlResponse);
            $response = [];
            foreach ($xml as $k => $node) {
                $response[$k] = (string)$node;
            }
        } catch (\Exception $e) {
            throw new LogicException("Response content is not valid xml: \n\n{$xmlResponse}");
        }

        return $response;
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
}
