@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Notifications</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{route('notifications.index')}}">Notifications</a></li>
                        <li class="breadcrumb-item"><a class="text-muted" href="#">Show</a></li>
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
                    <div class="card">
                        <div class="card-header ">
                            <div class="d-flex justify-content-between">
                                <div>{{ $notification->data['title'] }}</div>
                                <div class="text-muted"><small><i class="fas fa-paper-plane mr-2"></i>{{ $notification->created_at->diffForHumans() }}</small></div>
                            </div>
                        </div>

                        <div class="card-body">
                           {!! $notification->data['content'] !!}
                        </div>
                    </div>
                </div>
            </div>
            <!-- END CUSTOM CONTENT -->

        </div>
    </section>
    <!-- END CONTENT -->

@endsection
