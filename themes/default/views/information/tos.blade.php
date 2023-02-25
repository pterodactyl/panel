@extends('layouts.app')

@section('content')

    <body class="dark-mode">
    <div class="container privacy-card">
        <div class="row">
            <div class="col-md-10" style="height: 20px;"></div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">{{ __('Terms of Service') }}</div>
                    <div class="card-body">
                        @include('information.tos-content')
                    </div>
                </div>
            </div>
        </div>
    </body>
@endsection
