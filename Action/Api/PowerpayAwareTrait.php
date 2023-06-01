<?php

namespace DachcomDigital\Payum\Powerpay\Action\Api;

use DachcomDigital\Payum\Powerpay\Exception\PowerpayException;

trait PowerpayAwareTrait
{
    protected function populateDetailsWithError(\ArrayAccess $details, PowerpayException $e, object $request): void
    {
        $details['error_request'] = get_class($request);
        $details['error_file'] = $e->getFile();
        $details['error_line'] = $e->getLine();
        $details['error_code'] = (int)$e->getCode();
        $details['error_message'] = utf8_encode($e->getMessage());
    }
}
