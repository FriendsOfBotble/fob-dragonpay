<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway;

use FriendsOfBotble\Dragonpay\Contracts\DigestInterface;
use FriendsOfBotble\Dragonpay\Encryption\Sha1Encryption;
use FriendsOfBotble\Dragonpay\Foundation\Exceptions\InvalidArrayParameterException;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Token;

class Parameters
{
    public const REQUEST_PARAM_MERCHANT_ID = 'merchantid';

    public const REQUEST_PARAM_TXNID = 'txnid';

    public const REQUEST_PARAM_AMOUNT = 'amount';

    public const REQUEST_PARAM_CCY = 'ccy';

    public const REQUEST_PARAM_DESCRIPTION = 'description';

    public const REQUEST_PARAM_EMAIL = 'email';

    public const REQUEST_PARAM_PASSWORD = 'password';

    public const REQUEST_PARAM_DIGEST = 'digest';

    public const REQUEST_PARAM_PARAM1 = 'param1';

    public const REQUEST_PARAM_PARAM2 = 'param2';

    public const PAYMENT_MODE = 'mode';

    public const REQUEST_TOKEN_PARAM_MERCHANT_ID = 'merchantId';

    public const REQUEST_TOKEN_PARAM_PASSWORD = 'password';

    public const REQUEST_TOKEN_PARAM_MERCHANT_TXNID = 'merchantTxnId';

    public const REQUEST_TOKEN_PARAM_AMOUNT = 'amount';

    public const REQUEST_TOKEN_PARAM_CCY = 'ccy';

    public const REQUEST_TOKEN_PARAM_DESCRIPTION = 'description';

    public const REQUEST_TOKEN_PARAM_EMAIL = 'email';

    public const REQUEST_TOKEN_PARAM_PARAM1 = 'param1';

    public const REQUEST_TOKEN_PARAM_PARAM2 = 'param2';

    public const BILLINGINFO_MERCHANT_ID = 'merchantId';

    public const BILLINGINFO_MERCHANT_TXNID = 'merchantTxnId';

    public const BILLINGINFO_FIRSTNAME = 'firstName';

    public const BILLINGINFO_LASTNAME = 'lastName';

    public const BILLINGINFO_ADDRESS1 = 'address1';

    public const BILLINGINFO_ADDRESS2 = 'address2';

    public const BILLINGINFO_CITY = 'city';

    public const BILLINGINFO_STATE = 'state';

    public const BILLINGINFO_COUNTRY = 'country';

    public const BILLINGINFO_ZIPCODE = 'zipCode';

    public const BILLINGINFO_TELNO = 'telNo';

    public const BILLINGINFO_EMAIL = 'email';

    public static array $requiredSendBillingInfoParameters = [
        self::BILLINGINFO_MERCHANT_ID,
        self::BILLINGINFO_MERCHANT_TXNID,
        self::BILLINGINFO_FIRSTNAME,
        self::BILLINGINFO_LASTNAME,
        self::BILLINGINFO_ADDRESS1,
        self::BILLINGINFO_ADDRESS2,
        self::BILLINGINFO_CITY,
        self::BILLINGINFO_STATE,
        self::BILLINGINFO_COUNTRY,
        self::BILLINGINFO_ZIPCODE,
        self::BILLINGINFO_TELNO,
        self::BILLINGINFO_EMAIL,
    ];

    protected array $parameters = [];

    protected array $billingInfoParameters = [];

    protected DigestInterface $digestor;

    public function __construct(protected Dragonpay $dragonpay, DigestInterface $digestor = null)
    {
        $this->digestor = is_null($digestor) ? new Sha1Encryption() : $digestor;
    }

    public function setRequestParameters(array $parameters): array
    {
        $parameters = array_merge($this->parameters, array_filter($parameters));

        if (
            ! array_key_exists('merchantid', $parameters)
            && ! array_key_exists('txnid', $parameters)
            && ! array_key_exists('amount', $parameters)
            && ! array_key_exists('ccy', $parameters)
            && ! array_key_exists('description', $parameters)
            && ! array_key_exists('email', $parameters)
        ) {
            throw InvalidArrayParameterException::invalidArrayKey();
        }

        $_parameters[Parameters::REQUEST_PARAM_MERCHANT_ID] = $parameters[Parameters::REQUEST_PARAM_MERCHANT_ID];
        $_parameters[Parameters::REQUEST_PARAM_TXNID] = $parameters[Parameters::REQUEST_PARAM_TXNID];
        $_parameters[Parameters::REQUEST_PARAM_AMOUNT] = number_format($parameters[Parameters::REQUEST_PARAM_AMOUNT], 2, '.', '');
        $_parameters[Parameters::REQUEST_PARAM_CCY] = $parameters[Parameters::REQUEST_PARAM_CCY];
        $_parameters[Parameters::REQUEST_PARAM_DESCRIPTION] = $parameters[Parameters::REQUEST_PARAM_DESCRIPTION];
        $_parameters[Parameters::REQUEST_PARAM_EMAIL] = $parameters[Parameters::REQUEST_PARAM_EMAIL];
        $_parameters['password'] = $parameters['password'];

        $_parameters = array_filter($_parameters);

        $_parameters['digest'] = $this->digestor->make($_parameters);

        unset($parameters['password'], $_parameters['password']);
        $_parameters[Parameters::REQUEST_PARAM_PARAM1] = $parameters[Parameters::REQUEST_PARAM_PARAM1] ?? '';
        $_parameters[Parameters::REQUEST_PARAM_PARAM2] = $parameters[Parameters::REQUEST_PARAM_PARAM2] ?? '';

        return $this->parameters = array_filter($_parameters);
    }

    public function prepareRequestTokenParameters(array $parameters): array
    {
        $parameters = array_merge($this->parameters, $parameters);

        $_parameters[Parameters::REQUEST_TOKEN_PARAM_MERCHANT_ID] = $parameters[Parameters::REQUEST_PARAM_MERCHANT_ID];
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_PASSWORD] = $parameters[Parameters::REQUEST_PARAM_PASSWORD];
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_MERCHANT_TXNID] = $parameters[Parameters::REQUEST_PARAM_TXNID];
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_AMOUNT] = number_format($parameters[Parameters::REQUEST_PARAM_AMOUNT], 2, '.', '');
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_CCY] = $parameters[Parameters::REQUEST_PARAM_CCY];
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_DESCRIPTION] = $parameters[Parameters::REQUEST_PARAM_DESCRIPTION];
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_EMAIL] = $parameters[Parameters::REQUEST_PARAM_EMAIL];
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_PARAM1] = $parameters[Parameters::REQUEST_PARAM_PARAM1] ?? '';
        $_parameters[Parameters::REQUEST_TOKEN_PARAM_PARAM2] = $parameters[Parameters::REQUEST_PARAM_PARAM2] ?? '';

        $_parameters = array_filter($_parameters);

        return $this->parameters = $_parameters;
    }

    public function setBillingInfoParameters(array $parameters): array
    {
        if (
            ! array_key_exists('merchantid', $parameters)
            && ! array_key_exists('txnid', $parameters)
            && ! array_key_exists('firstName', $parameters)
            && ! array_key_exists('lastName', $parameters)
            && ! array_key_exists('address1', $parameters)
            && ! array_key_exists('address2', $parameters)
            && ! array_key_exists('city', $parameters)
            && ! array_key_exists('state', $parameters)
            && ! array_key_exists('country', $parameters)
            && ! array_key_exists('telNo', $parameters)
            && ! array_key_exists('email', $parameters)
        ) {
            throw InvalidArrayParameterException::sendBillingInfoParameters();
        }

        $_parameters[self::BILLINGINFO_MERCHANT_ID] = $parameters[self::REQUEST_PARAM_MERCHANT_ID];
        $_parameters[self::BILLINGINFO_MERCHANT_TXNID] = $parameters[self::REQUEST_PARAM_TXNID];
        $_parameters[self::BILLINGINFO_FIRSTNAME] = $parameters[self::BILLINGINFO_FIRSTNAME];
        $_parameters[self::BILLINGINFO_LASTNAME] = $parameters[self::BILLINGINFO_LASTNAME];
        $_parameters[self::BILLINGINFO_ADDRESS1] = $parameters[self::BILLINGINFO_ADDRESS1];
        $_parameters[self::BILLINGINFO_ADDRESS2] = $parameters[self::BILLINGINFO_ADDRESS2];
        $_parameters[self::BILLINGINFO_CITY] = $parameters[self::BILLINGINFO_CITY];
        $_parameters[self::BILLINGINFO_STATE] = $parameters[self::BILLINGINFO_STATE];
        $_parameters[self::BILLINGINFO_COUNTRY] = $parameters[self::BILLINGINFO_COUNTRY];
        $_parameters[self::BILLINGINFO_ZIPCODE] = $parameters[self::BILLINGINFO_ZIPCODE] ?? '';
        $_parameters[self::BILLINGINFO_TELNO] = $parameters[self::BILLINGINFO_TELNO];
        $_parameters[self::BILLINGINFO_EMAIL] = $parameters[self::BILLINGINFO_EMAIL];

        return $this->billingInfoParameters = array_filter($_parameters);
    }

    public function add(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);

        return $this;
    }

    public function get(): array
    {
        $parameters = $this->parameters;

        if (! is_null($this->dragonpay->getPaymentChannel())) {
            $parameters['mode'] = $this->dragonpay->getPaymentChannel();
        }

        if ($this->dragonpay->token instanceof Token) {
            if (isset($parameters['mode'])) {
                $parameters = ['tokenid' => $this->dragonpay->token->getToken(), 'mode' => $parameters['mode']];
            } elseif (isset($parameters['procid'])) {
                $parameters = ['tokenid' => $this->dragonpay->token->getToken(), 'procid' => $parameters['procid']];
            } else {
                $parameters = ['tokenid' => $this->dragonpay->token->getToken()];
            }
        }

        return $parameters;
    }

    public function billingInfo(): array
    {
        return $this->billingInfoParameters;
    }

    public function query(): string
    {
        return http_build_query($this->get(), '', '&');
    }
}
