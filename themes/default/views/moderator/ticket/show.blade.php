@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Ticket') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{ route('moderator.ticket.index') }}">{{ __('Ticket') }}</a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><i class="fas fa-users mr-2"></i>#{{ $ticket->ticket_id }}</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="ticket-info">
                                @if(!empty($server))
                                <p><b>{{__("Server")}}:</b> <a href="{{ config("SETTINGS::SYSTEM:PTERODACTYL:URL") . '/admin/servers/view/' . $server->pterodactyl_id }}" target="__blank">{{ $server->name }}</a></p>
                                @endif
                                <p><b>{{__("Title")}}:</b> {{ $ticket->title }}</p>
                                <p><b>{{__("Category")}}:</b> {{ $ticketcategory->name }}</p>
                                <p><b>{{__("Status")}}:</b>
                                @switch($ticket->status)
                                        @case("Open")
                                            <span class="badge badge-success">{{__("Open")}}</span>
                                        @break
                                        @case("Reopened")
                                            <span class="badge badge-success">{{__("Reopened")}}</span>
                                            @break
                                        @case("Closed")
                                            <span class="badge badge-danger">{{__("Closed")}}</span>
                                        @break
                                        @case("Answered")
                                            <span class="badge badge-info">{{__("Answered")}}</span>
                                        @break
                                        @case("Client Reply")
                                           <span class="badge badge-warning">{{__("Client Reply")}}</span>
                                        @break
                                    @endswitch
                                </p>
                                    <p><b>Priority:</b>
                                        @switch($ticket->priority)
                                            @case("Low")
                                                <span class="badge badge-success">{{__("Low")}}</span>
                                                @break
                                            @case("Medium")
                                               <span class="badge badge-warning">{{__("Medium")}}</span>
                                                @break
                                            @case("High")
                                               <span class="badge badge-danger">{{__("High")}}</span>
                                                @break
                                        @endswitch
                                    </p>
                                    <p><b>{{__("Created on")}}:</b> {{ $ticket->created_at->diffForHumans() }}</p>
                                    @if($ticket->status=='Closed')
                                        <form class="d-inline" method="post"
                                              action="{{route('moderator.ticket.changeStatus', ['ticket_id' => $ticket->ticket_id ])}}">
                                            {{csrf_field()}}
                                            {{method_field("POST") }}
                                            <button data-content="{{__("Reopen")}}" data-toggle="popover"
                                                    data-trigger="hover" data-placement="top"
                                                    class="btn btn-sm text-white btn-success mr-1"><i
                                                    class="fas fa-redo"></i>{{__("Reopen")}}</button>
                                        </form>
                                    @else
                                        <form class="d-inline" method="post"
                                              action="{{route('moderator.ticket.changeStatus', ['ticket_id' => $ticket->ticket_id ])}}">
                                            {{csrf_field()}}
                                            {{method_field("POST") }}
                                            <button data-content="{{__("Close")}}" data-toggle="popover"
                                                    data-trigger="hover" data-placement="top"
                                                    class="btn btn-sm text-white btn-warning mr-1"><i
                                                    class="fas fa-times"></i>{{__("Close")}}</button>
                                        </form>
                                    @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><i class="fas fa-cloud mr-2"></i>{{__('Comment')}}</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title"><img
                                            src="https://www.gravatar.com/avatar/{{ md5(strtolower($ticket->user->email)) }}?s=25"
                                            class="user-image" alt="User Image">
                                        <a href="/admin/users/{{$ticket->user->id}}">{{ $ticket->user->name }}</a>
                                        @if($ticket->user->role === "member")
                                            <span class="badge badge-secondary"> Member </span>
                                        @elseif ($ticket->user->role === "client")
                                            <span class="badge badge-success"> Client </span>
                                        @elseif ($ticket->user->role === "moderator")
                                            <span class="badge badge-info"> Moderator </span>
                                        @elseif ($ticket->user->role === "admin")
                                            <span class="badge badge-danger"> Admin </span>
                                        @endif
                                    </h5>
                                        <span class="badge badge-primary">{{ $ticket->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div class="card-body" style="white-space:pre-wrap">{{ $ticket->message }}</div>
                            </div>
                            @foreach ($ticketcomments as $ticketcomment)
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title"><img
                                            src="https://www.gravatar.com/avatar/{{ md5(strtolower($ticketcomment->user->email)) }}?s=25"
                                            class="user-image" alt="User Image">
                                        <a href="/admin/users/{{$ticketcomment->user->id}}">{{ $ticketcomment->user->name }}</a>
                                        @if($ticketcomment->user->role === "member")
                                            <span class="badge badge-secondary"> Member </span>
                                        @elseif ($ticketcomment->user->role === "client")
                                            <span class="badge badge-success"> Client </span>
                                        @elseif ($ticketcomment->user->role === "moderator")
                                            <span class="badge badge-info"> Moderator </span>
                                        @elseif ($ticketcomment->user->role === "admin")
                                            <span class="badge badge-danger"> Admin </span>
                                        @endif
                                    </h5>
                                        <span class="badge badge-primary">{{ $ticketcomment->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div class="card-body" style="white-space:pre-wrap">{{ $ticketcomment->ticketcomment }}</div>
                            </div>
                            @endforeach
                            <div class="comment-form">
                                <form action="{{ route('moderator.ticket.reply')}}" method="POST" class="form">
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                                    <div class="form-group{{ $errors->has('ticketcomment') ? ' has-error' : '' }}">
                                        <textarea rows="10" id="ticketcomment" class="form-control" name="ticketcomment"></textarea>
                                        @if ($errors->has('ticketcomment'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('ticketcomment') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT -->
@endsection

