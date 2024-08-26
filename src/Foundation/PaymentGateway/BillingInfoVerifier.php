<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway;

use FriendsOfBotble\Dragonpay\Foundation\Adapter\SoapClientAdapter;

class BillingInfoVerifier
{
    protected Parameters $parameters;

    public function send(SoapClientAdapter $soap, $url): bool
    {
        return $soap
            ->setParameters($this->parameters->billingInfo())
            ->execute(
                "$url?wsdl",
                ['location' => $url, 'trace' => 1]
            );
    }

    public function setParameterObject(Parameters $parameter): self
    {
        $this->parameters = $parameter;

        return $this;
    }
}
