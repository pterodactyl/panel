@component('mail::message')
Hello {{$ticket->user->name}},

This is a notification that we have received your support request, and your ticket number is **#{{$ticket->ticket_id}}**.

We will be responding to this ticket as soon as possible. If this is a Setup request, please understand that these requests take longer than regular support timeframes. Please be aware that Setups may take up to 48 hours to be completed. 

Thank you so much for being so understanding. 

<br>
{{__('Thanks')}},<br>
{{ config('app.name') }}
@endcomponent
