@component('mail::message')
# {{__('Thank you for your purchase!')}}
{{__('Your payment has been confirmed; Your credit balance has been updated.')}}'<br>

# Details
___
### {{__('Payment ID')}}: **{{$payment->id}}**<br>
### {{__('Status')}}:     **{{$payment->status}}**<br>
### {{__('Price')}}:      **{{$payment->formatToCurrency($payment->total_price)}}**<br>
### {{__('Type')}}:       **{{$payment->type}}**<br>
### {{__('Amount')}}:     **{{$payment->amount}}**<br>
### {{__('Balance')}}:    **{{$payment->user->credits}}**<br>
### {{__('User ID')}}:    **{{$payment->user_id}}**<br>

<br>
{{__('Thanks')}},<br>
{{ config('app.name') }}
@endcomponent
