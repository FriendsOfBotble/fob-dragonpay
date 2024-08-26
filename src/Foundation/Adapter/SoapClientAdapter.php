<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Adapter;

use function class_exists;

use Exception;
use FriendsOfBotble\Dragonpay\Foundation\Exceptions\SendBillingInfoException;

use SoapClient;

class SoapClientAdapter
{
    protected array $billingInfoParameters = [];

    public function setParameters(array $parameters): self
    {
        $this->billingInfoParameters = $parameters;

        return $this;
    }

    public function execute(string $url, array $parameters): bool
    {
        if (! class_exists(SoapClient::class)) {
            throw new Exception('SoapClient class not found. Please install it.');
        }

        $wsdl = new SoapClient($url, $parameters);
        $result = $wsdl->SendBillingInfo($this->billingInfoParameters)->SendBillingInfoResult;

        if ($result != 0) {
            throw new SendBillingInfoException('Dragonpay responded an error code ' . $result . ' when sending billing info. Please check your parameter or contact Dragonpay directly.');
        }

        return $result == 0;
    }

    public function initialize(string $resourceUrl): SoapClient
    {
        if (! class_exists(SoapClient::class)) {
            throw new Exception('SoapClient class not found. Please install it.');
        }

        $soapClient = new SoapClient($resourceUrl . '?wsdl', [
            'location' => $resourceUrl,
            'trace' => 1,
        ]);

        return $soapClient;
    }
}
