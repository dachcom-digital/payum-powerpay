<?php

namespace DachcomDigital\Payum\Powerpay;

use DachcomDigital\Payum\Powerpay\Action\Api\ActivateAction;
use DachcomDigital\Payum\Powerpay\Action\Api\CancelAction;
use DachcomDigital\Payum\Powerpay\Action\Api\ConfirmAction;
use DachcomDigital\Payum\Powerpay\Action\Api\CreditAmountAction;
use DachcomDigital\Payum\Powerpay\Action\Api\ReserveAmountAction;
use DachcomDigital\Payum\Powerpay\Action\Api\TransactionTransformerAction;
use DachcomDigital\Payum\Powerpay\Action\CaptureAction;
use DachcomDigital\Payum\Powerpay\Action\ConvertPaymentAction;
use DachcomDigital\Payum\Powerpay\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PowerpayGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name'  => 'powerpay',
            'payum.factory_title' => 'Powerpay',

            'payum.action.capture'         => new CaptureAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),

            'payum.action.api.activate'       => new ActivateAction(),
            'payum.action.api.confirm'        => new ConfirmAction(),
            'payum.action.api.cancel'         => new CancelAction(),
            'payum.action.api.reserve_amount' => new ReserveAmountAction(),
            'payum.action.api.credit_amount'  => new CreditAmountAction(),

            'payum.action.api.transaction_transformer' => new TransactionTransformerAction(),

        ]);

        if (!empty($config['payum.api'])) {
            return;
        }

        $config['payum.default_options'] = [
            'environment'   => Api::TEST,
            'paymentMethod' => '',
            'sandbox'       => true,
        ];

        $config->defaults($config['payum.default_options']);
        $config['payum.required_options'] = [
            'username',
            'password',
            'merchantId',
            'filialId',
            'terminalId',
            'confirmationMethod'
        ];

        $config['payum.api'] = static function (ArrayObject $config) {

            $config->validateNotEmpty($config['payum.required_options']);

            return new Api(
                [
                    'sandbox'            => $config['environment'] === Api::TEST,
                    'username'           => $config['username'],
                    'password'           => $config['password'],
                    'merchantId'         => $config['merchantId'],
                    'filialId'           => $config['filialId'],
                    'terminalId'         => $config['terminalId'],
                    'confirmationMethod' => $config['confirmationMethod']
                ],
                $config['payum.http_client'],
                $config['httplug.message_factory']
            );
        };

    }
}
