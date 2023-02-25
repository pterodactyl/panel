@component('mail::message')
A response has been added to your ticket. Please see below for our response!

### Details
Ticket ID : {{ $ticket->ticket_id }} <br>
Subject: {{ $ticket->title }} <br>
Status: {{ $ticket->status }} <br>

___
```
{{ $newmessage }}
```
___
<br>
<br>
{{__('Thanks')}},<br>
{{ config('app.name') }}
@endcomponent