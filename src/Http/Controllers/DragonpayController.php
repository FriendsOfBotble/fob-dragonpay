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
        $data = $request->only(['txnid', 'refno', 'status', 'message', 'digest']);

        if (! Arr::has($data, ['txnid', 'refno', 'status'])) {
            abort(403);
        }

        // Dragonpay is retiring the SHA-1 digest, so it is only checked when it is still sent;
        // the status itself always comes from the API below.
        if (! $this->digestIsAcceptable($dragonpay, $data)) {
            abort(403);
        }

        // Never trust the posted status: ask Dragonpay for the transaction itself.
        $transaction = $dragonpay->action(new CheckTransactionStatus($data['txnid']));

        if (
            ! is_array($transaction)
            || Arr::get($transaction, 'TxnId') !== $data['txnid']
            || ! DragonpayPaymentService::hasValidTxnId($transaction)
        ) {
            abort(404);
        }

        $payment = Payment::query()
            ->where('charge_id', $data['txnid'])
            ->first();

        if (! $payment) {
            if (! $paymentService->afterMakePayment($data['txnid'])) {
                return response('result=FAIL_PROCESSING', 500, ['Content-Type' => 'text/plain']);
            }

            return response('result=OK', 200, ['Content-Type' => 'text/plain']);
        }

        if ($payment->status == PaymentStatusEnum::COMPLETED) {
            return response('result=OK', 200, ['Content-Type' => 'text/plain']);
        }

        if (
            Arr::has($transaction, 'Amount')
            && abs((float) $transaction['Amount'] - (float) $payment->amount) > 0.01
        ) {
            logger()->warning('Dragonpay postback amount mismatch.', [
                'txnid' => $data['txnid'],
                'expected' => $payment->amount,
                'received' => $transaction['Amount'],
            ]);

            abort(400);
        }

        $payment->update([
            'status' => $this->mapStatus(Arr::get($transaction, 'Status')),
        ]);

        return response('result=OK', 200, ['Content-Type' => 'text/plain']);
    }

    public function callback(Request $request, Dragonpay $dragonpay, DragonpayPaymentService $paymentService, BaseHttpResponse $response)
    {
        if (! $request->has('txnid')) {
            abort(404);
        }

        $data = $dragonpay->action(new CheckTransactionStatus($request->input('txnid')));

        if (! is_array($data) || ! DragonpayPaymentService::hasValidTxnId($data)) {
            abort(404);
        }

        $param1 = json_decode($data['Param1'], true);

        $status = $this->mapStatus($data['Status']);

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
        } elseif ($payment->status != PaymentStatusEnum::COMPLETED) {
            $payment->update([
                'status' => $status,
            ]);
        }

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL(Arr::get($param1, 'token')))
            ->setMessage(__('Checkout successfully!'));
    }

    protected function digestIsAcceptable(Dragonpay $dragonpay, array $data): bool
    {
        $digest = strtolower((string) Arr::get($data, 'digest'));
        $password = (string) Arr::get($dragonpay->getMerchantAccount(), 'password');

        if ($password === '' || ! preg_match('/^[0-9a-f]{40}$/', $digest)) {
            return true;
        }

        $expected = sha1(implode(':', [
            $data['txnid'],
            $data['refno'],
            $data['status'],
            Arr::get($data, 'message', ''),
            $password,
        ]));

        return hash_equals($expected, $digest);
    }

    protected function mapStatus(?string $status): string
    {
        return match ($status) {
            Dragonpay::SUCCESS => PaymentStatusEnum::COMPLETED,
            Dragonpay::FAILED => PaymentStatusEnum::FAILED,
            default => PaymentStatusEnum::PENDING,
        };
    }
}
