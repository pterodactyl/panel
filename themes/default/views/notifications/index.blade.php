@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Notifications')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="{{route('notifications.index')}}">{{__('Notifications')}}</a>
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

            <!-- CUSTOM CONTENT -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <p>{{__('All notifications')}}</p>
                </div>
                    <a class="float-right">
                        <a href="{{route('notifications.readAll')}}"><button class="btn btn-info btn-xs">{{__('Mark all as read')}}</button></a>


                @foreach($notifications as $notification)
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header ">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a {{$notification->read() ? 'class=link-muted' : ''}} href="{{route('notifications.show' , $notification->id)}}"><i class="fas fa-envelope mr-2"></i>{{ $notification->data['title'] }}</a>
                                    </div>
                                    <div class="text-muted">
                                        <small>
                                            <i class="fas fa-paper-plane mr-2"></i>{{ $notification->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="col-md-8">
                    <div class="float-right">
                        {!!  $notifications->links() !!}
                    </div>
                </div>
            </div>

            <!-- END CUSTOM CONTENT -->


        </div>
    </section>
    <!-- END CONTENT -->

@endsection
