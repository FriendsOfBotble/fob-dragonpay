<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway;

use Closure;
use FriendsOfBotble\Dragonpay\Contracts\DigestInterface;
use FriendsOfBotble\Dragonpay\Contracts\PaymentGatewayInterface;
use FriendsOfBotble\Dragonpay\Foundation\Adapter\SoapClientAdapter;
use FriendsOfBotble\Dragonpay\Foundation\Exceptions\InvalidPostbackInvokerException;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action\ActionInterface;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\PaymentChannels;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Token;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Handler\PostbackHandlerInterface;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Options\Processor;
use InvalidArgumentException;

class Dragonpay implements PaymentGatewayInterface
{
    public const INVALID_PAYMENT_GATEWAY_ID = 101;

    public const INCORRECT_SECRET_KEY = 102;

    public const INVALID_REFERENCE_NUMBER = 103;

    public const UNAUTHORIZED_ACCESS = 104;

    public const INVALID_TOKEN = 105;

    public const CURRENCY_NOT_SUPPORTED = 106;

    public const TRANSACTION_CANCELLED = 107;

    public const INSUFFICIENT_FUNDS = 108;

    public const TRANSACTION_LIMIT_EXCEEDED = 109;

    public const ERROR_IN_OPERATION = 110;

    public const INVALID_PARAMETERS = 111;

    public const INVALID_MERCHANT_ID = 201;

    public const INVALID_MERCHANT_PASSWORD = 202;

    protected array $errorCodes = [
        self::INVALID_PAYMENT_GATEWAY_ID => 'Invalid payment gateway id',
        self::INCORRECT_SECRET_KEY => 'Incorrect secret key',
        self::INVALID_REFERENCE_NUMBER => 'Invalid reference number',
        self::UNAUTHORIZED_ACCESS => 'Unauthorized access',
        self::INVALID_TOKEN => 'Invalid token',
        self::CURRENCY_NOT_SUPPORTED => 'Currency not supported',
        self::TRANSACTION_CANCELLED => 'Transaction cancelled',
        self::INSUFFICIENT_FUNDS => 'Insufficient funds',
        self::TRANSACTION_LIMIT_EXCEEDED => 'Transaction limit exceeded',
        self::ERROR_IN_OPERATION => 'Error in operation',
        self::INVALID_PARAMETERS => 'Invalid parameters',
        self::INVALID_MERCHANT_ID => 'Invalid merchant id',
        self::INVALID_MERCHANT_PASSWORD => 'Invalid merchant password',
    ];

    public const SUCCESS = 'S';

    public const FAILED = 'F';

    public const PENDING = 'P';

    public const UNKNOWN = 'U';

    public const REFUND = 'R';

    public const CHARGEBACK = 'K';

    public const VOID = 'V';

    public const AUTHORIZED = 'A';

    public const STATUS = [
        self::SUCCESS => 'Success',
        self::FAILED => 'Failure',
        self::PENDING => 'Pending',
        self::UNKNOWN => 'Unknown',
        self::REFUND => 'Refund',
        self::CHARGEBACK => 'Chargeback',
        self::VOID => 'Void',
        self::AUTHORIZED => 'Authorized',
    ];

    public const ONLINE_BANK = 1;

    public const OTC_BANK = 2;

    public const OTC_NON_BANK = 4;

    public const PAYPAL = 32;

    public const CREDIT_CARD = 64;

    public const GCASH = 128;

    public const INTL_OTC = 256;

    public const WS_ENDPOINT = '/DragonPayWebService/MerchantService.asmx';

    public const WS_BILLING_INFO = 'send_billing_info_url';

    public const WS_SANDBOX_URL = 'sanbox_ws_url';

    public const WS_PRODUCTION_URL = 'production_ws_url';

    public const ALL_PROCESSORS = -1000;

    public const SANDBOX = 'sandbox';

    public const PRODUCTION = 'production';

    protected array $baseUrl = [
        self::SANDBOX => 'https://test.dragonpay.ph/Pay.aspx',
        self::PRODUCTION => 'https://gw.dragonpay.ph/Pay.aspx',
    ];

    protected array $wsBaseUrl = [
        self::WS_BILLING_INFO => 'https://gw.dragonpay.ph' . self::WS_ENDPOINT,
        self::WS_SANDBOX_URL => 'https://test.dragonpay.ph' . self::WS_ENDPOINT,
        self::WS_PRODUCTION_URL => 'https://gw.dragonpay.ph' . self::WS_ENDPOINT,
    ];

    protected string $digest;

    public RequestBag $request;

    public Parameters $parameters;

    public ?Token $token = null;

    protected bool $isSandbox = true;

    protected ?string $paymentChannel = null;

    protected string $debugMessage;

    public PaymentChannels $channels;

    protected array $merchantAccount;

    public function __construct(
        array $merchantAccount,
        bool $sandbox = true,
        DigestInterface $digestor = null
    ) {
        $this->request = new RequestBag();
        $this->channels = new PaymentChannels();
        $this->parameters = new Parameters($this, $digestor);
        $this->parameters->add($merchantAccount);
        $this->setMerchantAccount($merchantAccount);
        $this->isSandbox = $sandbox;
    }

    public function __debugInfo(): array
    {
        return [
            'is_sandbox' => $this->isSandbox,
            'payment_url' => $this->getUrl(),
            'web_service_url' => $this->getWebserviceUrl(),
            'request' => $this->request,
            'channels' => $this->channels,
            'parameters' => $this->parameters,
        ];
    }

    protected function setMerchantAccount(array $merchantAccount): static
    {
        $this->merchantAccount = $merchantAccount;

        return $this;
    }

    public function getMerchantAccount(): array
    {
        return $this->merchantAccount;
    }

    public function setParameters(array $parameters): static
    {
        return $this->setRequestParameters($parameters);
    }

    public function setRequestParameters(array $parameters): static
    {
        $this->parameters->setRequestParameters($parameters);

        return $this;
    }

    public function withProcid(string $procid): self
    {
        Processor::allowedProcId($procid);

        $this->parameters->add(['procid' => $procid]);

        return $this;
    }

    public function getToken(array $parameters, SoapClientAdapter $soapAdapter = null): Token
    {
        $parameters = $this->parameters->prepareRequestTokenParameters($parameters);

        $webservice_url = $this->getWebserviceUrl();

        if (is_null($soapAdapter)) {
            $soapAdapter = new SoapClientAdapter();
            $soapAdapter = $soapAdapter->initialize($webservice_url);
        }

        $token = $soapAdapter->GetTxnToken($parameters);

        $code = $token->GetTxnTokenResult;

        if (array_key_exists($code, $this->errorCodes)) {
            $this->throwException($code);
        }

        $this->token = new Token($code);

        return $this->token;
    }

    protected function throwException(int $code): void
    {
        $this->setDebugMessage($this->errorCodes[$code]);
        $exception = "FriendsOfBotble\Dragonpay\Foundation\Exceptions\\" . $this->getExceptionClass($code);

        throw new $exception($this->seeError());
    }

    protected function getExceptionClass(int $code): string
    {
        $exceptions = [
            self::INVALID_PAYMENT_GATEWAY_ID => 'InvalidPaymentGatewayIdException',
            self::INCORRECT_SECRET_KEY => 'IncorrectSecretKeyException',
            self::INVALID_REFERENCE_NUMBER => 'InvalidReferenceNumberException',
            self::UNAUTHORIZED_ACCESS => 'UnauthorizedAccessException',
            self::INVALID_TOKEN => 'InvalidTokenException',
            self::CURRENCY_NOT_SUPPORTED => 'CurrencyNotSupportedException',
            self::TRANSACTION_CANCELLED => 'TransactionCancelledException',
            self::INSUFFICIENT_FUNDS => 'InsufficientFundsException',
            self::TRANSACTION_LIMIT_EXCEEDED => 'TransactionLimitExceededException',
            self::ERROR_IN_OPERATION => 'ErrorInOperationException',
            self::INVALID_PARAMETERS => 'InvalidParametersException',
            self::INVALID_MERCHANT_ID => 'InvalidMerchantIdException',
            self::INVALID_MERCHANT_PASSWORD => 'InvalidMerchantPasswordException',
        ];

        return $exceptions[$code];
    }

    public function useCreditCard(array $parameters, BillingInfoVerifier $verifier = null, SoapClientAdapter $soap = null): static
    {
        $this->setParameters($parameters);

        $this->parameters->setBillingInfoParameters($parameters);

        $this->filterPaymentChannel(Dragonpay::CREDIT_CARD);

        $url = $this->getBillingInfoUrl();

        if (is_null($verifier)) {
            $verifier = new BillingInfoVerifier();
        }
        if (is_null($soap)) {
            $soap = new SoapClientAdapter();
        }

        $verifier->setParameterObject($this->parameters);

        $verifier->send($soap, $url);

        return $this;
    }

    public function setBillingInfoUrl(string $url): static
    {
        $url = rtrim((rtrim($url, '/')), '?');

        $this->wsBaseUrl[self::WS_BILLING_INFO] = $url;

        return $this;
    }

    public function getBillingInfoUrl(): string
    {
        return $this->wsBaseUrl[self::WS_BILLING_INFO];
    }

    public function filterPaymentChannel(int $channel): static
    {
        $this->paymentChannel = $channel;

        return $this;
    }

    public function getPaymentChannel(): ?string
    {
        return $this->paymentChannel;
    }

    public function setDebugMessage(string $message): void
    {
        $this->debugMessage = $message;
    }

    public function seeError(): string
    {
        return $this->debugMessage;
    }

    public function setWebServiceUrl(string $url): static
    {
        if ($this->getPaymentMode() === 'sandbox') {
            $this->wsBaseUrl[self::WS_SANDBOX_URL] = $url;
        } else {
            $this->wsBaseUrl[self::WS_PRODUCTION_URL] = $url;
        }

        return $this;
    }

    public function getWebserviceUrl(): string
    {
        return $this->getPaymentMode() === 'sandbox' ? $this->wsBaseUrl[self::WS_SANDBOX_URL] : $this->wsBaseUrl[self::WS_PRODUCTION_URL];
    }

    public function getUrl(): string
    {
        return $this->isSandbox ? $this->baseUrl[self::SANDBOX] : $this->baseUrl[self::PRODUCTION];
    }

    public function setPaymentUrl(string $url): static
    {
        if ($this->getPaymentMode() === 'sandbox') {
            $this->baseUrl[self::SANDBOX] = $url;
        } else {
            $this->baseUrl[self::PRODUCTION] = $url;
        }

        return $this;
    }

    public function getPaymentMode(): string
    {
        return $this->isSandbox ? 'sandbox' : 'production';
    }

    public function away(bool $test = false): string
    {
        if ($test) {
            return sprintf('%s?%s', $this->getUrl(), $this->parameters->query());
        }

        header("Location: {$this->getUrl()}?{$this->parameters->query()}", true, 302);
        exit();
    }

    public function handlePostback(PostbackHandlerInterface|Closure $callback, array $postData): mixed
    {
        if (isset($postData['status'])) {
            $description = $this->getStatusDescription($postData['status']);
            $data = $postData;
            $data['description'] = $description;

            if ($callback instanceof Closure) {
                return call_user_func_array($callback, [$data]);
            }

            if ($callback instanceof PostbackHandlerInterface) {
                return call_user_func_array([$callback, 'handle'], [$data]);
            }
        }

        throw new InvalidPostbackInvokerException();
    }

    protected function getStatusDescription(string $status): string
    {
        if (isset(self::STATUS[$status])) {
            return self::STATUS[$status];
        }

        throw new InvalidPostbackInvokerException();
    }

    public function action(ActionInterface $action): mixed
    {
        return $action->doAction($this);
    }

    public function getBaseUrlOf(string $modeType): string
    {
        if (! in_array(strtolower($modeType), ['sandbox', 'production'])) {
            throw new InvalidArgumentException(sprintf('Modetype is %s is not supported. Please select between {sandbox} or {production} mode only.', $modeType));
        }

        return $this->baseUrl[$modeType];
    }
}
