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
                                                       href="{{ route('ticket.index') }}">{{ __('Ticket') }}</a>
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
            <form action="{{route('ticket.new.store')}}" method="POST" class="ticket-form">
                @csrf
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-money-check-alt mr-2"></i>{{__('Open a new ticket')}}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group col-sm-12 {{ $errors->has('title') ? ' has-error' : '' }}">
                                    <label for="title" class="control-label">Title</label>
                                    <input id="title" type="text" class="form-control" name="title" value="{{ old('title') }}">
                                    @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                @if ($servers->count() >= 1)
                                <div class="form-group col-sm-12 {{ $errors->has('server') ? ' has-error' : '' }}">
                                    <label for="server" class="control-label">{{__("Server")}}</label>
                                    <select id="server" type="server" class="form-control" name="server">
                                        <option value="">{{__("Select Servers")}}</option>
                                        @foreach ($servers as $server)
                                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                                        @endforeach
                                    </select>

                                    @if ($errors->has('category'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('ticketcategory') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                @endif
                                <div class="form-group col-sm-12 {{ $errors->has('ticketcategory') ? ' has-error' : '' }}">
                                    <label for="ticketcategory" class="control-label">{{__("Category")}}</label>
                                    <select id="ticketcategory" type="ticketcategory" class="form-control" required name="ticketcategory">
                                        <option value="" disabled selected>{{__("Select Category")}}</option>
                                        @foreach ($ticketcategories as $ticketcategory)
                                        <option value="{{ $ticketcategory->id }}">{{ $ticketcategory->name }}</option>
                                        @endforeach
                                    </select>

                                    @if ($errors->has('category'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('ticketcategory') }}</strong>
                                    </span>
                                    @endif
                                </div>
                                <div class="form-group col-sm-12 {{ $errors->has('priority') ? ' has-error' : '' }}">
                                    <label for="priority" class="control-label">Priority</label>
                                    <select id="priority" type="" class="form-control" name="priority">
                                        <option value="" disabled selected>{{__("Select Priority")}}</option>
                                        <option value="Low">{{__("Low")}}</option>
                                        <option value="Medium">{{__("Medium")}}</option>
                                        <option value="High">{{__("High")}}</option>
                                    </select>
                                    @if ($errors->has('priority'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('priority') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary ticket-once">
                                    {{__('Open Ticket')}}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-money-check-alt mr-2"></i>{{__('Ticket details')}}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group col-sm-12 {{ $errors->has('message') ? ' has-error' : '' }}">
                                    <label for="message" class="control-label">Message</label>
                                    <textarea rows="8" id="message" class="form-control" name="message">{{old("message")}}</textarea>
                                    @if ($errors->has('message'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('message') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <!-- END CONTENT -->
    <script type="text/javascript">
     $(".ticket-form").submit(function (e) {

         $(".ticket-once").attr("disabled", true);
         return true;
     })

    </script>
@endsection

