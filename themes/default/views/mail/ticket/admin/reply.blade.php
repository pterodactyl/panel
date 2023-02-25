@component('mail::message')
Ticket #{{$ticket->ticket_id}} has had a new reply posted by **{{$user->name}}**

### Details
Client: {{$user->name}} <br>
Subject: {{$ticket->title}} <br>
Category: {{ $ticket->ticketcategory->name }} <br>
Priority: {{ $ticket->priority }} <br>
Status: {{ $ticket->status }} <br>

___
```
{{ $newmessage }}
```
___
<br>
You can respond to this ticket by simply replying to this email or through the admin area at the url below.
<br>

{{ route('moderator.ticket.show', ['ticket_id' => $ticket->ticket_id]) }}

<br>
{{__('Thanks')}},<br>
{{ config('app.name') }}
@endcomponent
