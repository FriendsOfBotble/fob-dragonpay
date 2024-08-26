<?php

namespace FriendsOfBotble\Dragonpay\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Supports\PaymentHelper;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action\CheckTransactionStatus;
use FriendsOfBotble\Dragonpay\Services\DragonpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DragonpayController extends BaseController
{
    public function postback(Request $request, Dragonpay $dragonpay, DragonpayPaymentService $paymentService)
    {
        $dragonpay->handlePostback(function (array $data) use ($paymentService) {
            if (! Arr::has($data, 'txnid')) {
                abort(404);
            }

            $payment = Payment::query()
                ->where('charge_id', $data['txnid'])
                ->first();

            if (! $payment) {
                $paymentService->afterMakePayment($data['txnid']);
            }

            $status = match ($data['status']) {
                Dragonpay::SUCCESS => PaymentStatusEnum::COMPLETED,
                Dragonpay::FAILED => PaymentStatusEnum::FAILED,
                default => PaymentStatusEnum::PENDING,
            };

            $payment->update([
                'status' => $status,
            ]);
        }, $request->all());
    }

    public function callback(Request $request, Dragonpay $dragonpay, DragonpayPaymentService $paymentService, BaseHttpResponse $response)
    {
        if (! $request->has('txnid')) {
            abort(404);
        }

        $data = $dragonpay->action(new CheckTransactionStatus($request->input('txnid')));

        if (! $data) {
            abort(404);
        }

        $param1 = json_decode($data['Param1'], true);

        $status = match ($data['Status']) {
            Dragonpay::SUCCESS => PaymentStatusEnum::COMPLETED,
            Dragonpay::FAILED => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };

        if ($status === PaymentStatusEnum::FAILED) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL(Arr::get($param1, 'token')))
                ->setMessage(__('Payment failed. Please try again.'));
        }

        $payment = Payment::query()
            ->where('charge_id', $data['TxnId'])
            ->first();

        if (! $payment) {
            $paymentService->afterMakePayment($data['TxnId']);
        } else {
            $payment->update([
                'status' => $status,
            ]);
        }

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL(Arr::get($param1, 'token')))
            ->setMessage(__('Checkout successfully!'));
    }
}
