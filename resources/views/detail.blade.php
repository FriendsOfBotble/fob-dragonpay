<p>
    <span>{{ trans('plugins/payment::payment.payment_id') }}:</span>
    <strong>{{ $payment['id'] }}</strong>
</p>

<p>
    <span>{{ trans('plugins/payment::payment.amount') }}:</span>
    <strong>{{ format_price($payment['amount']) }}</strong>
</p>

@if (($payment['refunds']))
    <h6 class="alert-heading">{{ trans('plugins/payment::payment.refunds.title') . ' (' . $payment['amount'] . ')'}}</h6>
    <hr class="m-0 mb-4">
    @foreach ($payment['refunds'] as $item)
        <div class="alert alert-warning" role="alert">
            <p>{{ trans('plugins/payment::payment.refunds.id') }}: {{ $item['refund_id'] }}</p>
            <p>{{ trans('plugins/payment::payment.amount') }}: {{ $item['amount'] / 1000 }} </p>
            <p>{{ __('Refund reason') }}: {{ $item['reason'] }}</p>
            <p>{{ trans('plugins/payment::payment.refunds.status') }}: {{ strtoupper($item['status']) }}</p>
            <p>{{ trans('plugins/payment::payment.refunds.create_time') }}: {{ \Carbon\Carbon::now()->parse($item['created_at']) }}</p>
        </div>
        <br />
    @endforeach
@endif

@include('plugins/payment::partials.view-payment-source')
