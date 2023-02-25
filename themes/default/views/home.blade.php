@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Dashboard') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a class="text-muted" href="">{{ __('Dashboard') }}</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    @if(!file_exists(base_path()."/install.lock") && Auth::User()->role == "admin")
        <div class="callout callout-danger">
            <h4>{{ __('The installer is not locked!') }}</h4>
            <p>{{ __('please create a file called "install.lock" in your dashboard Root directory. Otherwise no settings will be loaded!') }}</p>
            <a href="/install?step=7"><button class="btn btn-outline-danger">{{__('or click here')}}</button></a>

        </div>
    @endif

    @if(config("SETTINGS::SYSTEM:ALERT_ENABLED") && !empty(config("SETTINGS::SYSTEM:ALERT_MESSAGE")))
        <div class="alert mt-4 alert-{{config("SETTINGS::SYSTEM:ALERT_TYPE")}}" role="alert">
            {!! config("SETTINGS::SYSTEM:ALERT_MESSAGE") !!}
        </div>
    @endif
    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-server"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">{{ __('Servers') }}</span>
                            <span class="info-box-number">{{ Auth::user()->servers()->count() }}</span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-coins"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">{{ CREDITS_DISPLAY_NAME }}</span>
                            <span class="info-box-number">{{ Auth::user()->Credits() }}</span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>
                <!-- /.col -->

                <!-- fix for small devices only -->
                <div class="clearfix hidden-md-up"></div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-chart-line"></i></span>

                        <div class="info-box-content">
                            <span class="info-box-text">{{ CREDITS_DISPLAY_NAME }} {{ __('Usage') }}</span>
                            <span class="info-box-number">{{ number_format($usage, 2, '.', '') }}
                                <sup>{{ __('per month') }}</sup></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                    <!-- /.info-box -->
                </div>

                <!-- /.col -->
                @if ($credits > 0.01 and $usage > 0)
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon {{ $bg }} elevation-1">
                                <i class="fas fa-hourglass-half"></i></span>
                            <div class="info-box-content">
                                <span
                                    class="info-box-text">{{ __('Out of Credits in', ['credits' => CREDITS_DISPLAY_NAME]) }}
                                </span>
                                <span class="info-box-number">{{ $boxText }}<sup>{{ $unit }}</sup></span>
                            </div>
                        </div>
                        <!-- /.info-box -->
                @endif
            </div>
            <!-- /.col -->

        </div>




        <div class="row">
            <div class="col-md-6">
                @if(config("SETTINGS::SYSTEM:MOTD_ENABLED") == "true")
                    <div class="card card-default">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-home mr-2"></i>
                                {{ config('app.name', 'MOTD') }} - MOTD
                            </h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                           {!! config('SETTINGS::SYSTEM:MOTD_MESSAGE', '') !!}
                        </div>
                        <!-- /.card-body -->
                    </div>
                @endif

                <!-- /.card -->
                @if(config("SETTINGS::SYSTEM:USEFULLINKS_ENABLED") == "true")
                <div class="card card-default">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-link mr-2"></i>
                            {{ __('Useful Links') }}
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        @foreach ($useful_links_dashboard as $useful_link)
                            <div class="alert alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert"
                                    aria-hidden="true">×</button>
                                <h5>
                                    <a class="alert-link text-decoration-none" target="__blank"
                                        href="{{ $useful_link->link }}">
                                        <i class="{{ $useful_link->icon }} mr-2"></i>{{ $useful_link->title }}
                                    </a>
                                </h5>
                                {!! $useful_link->description !!}
                            </div>
                        @endforeach
                    </div>
                    <!-- /.card-body -->
                </div>
                @endif
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <div class="col-md-6">
                <div class="card card-default">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-2"></i>
                            {{ __('Activity Logs') }}
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body py-0 pb-2">
                        <ul class="list-group list-group-flush">
                            @foreach (Auth::user()->actions()->take(8)->orderBy('created_at', 'desc')->get() as $log)
                                <li class="list-group-item d-flex justify-content-between text-muted">
                                    <span>
                                        @if(str_starts_with($log->description,"created"))
                                            <small><i class="fas text-success fa-plus mr-2"></i></small>
                                        @elseif(str_starts_with($log->description,"redeemed"))
                                            <small><i class="fas text-success fa-money-check-alt mr-2"></i></small>
                                        @elseif(str_starts_with($log->description,"deleted"))
                                            <small><i class="fas text-danger fa-times mr-2"></i></small>
                                        @elseif(str_starts_with($log->description,"gained"))
                                            <small><i class="fas text-success fa-money-bill mr-2"></i></small>
                                        @elseif(str_starts_with($log->description,"updated"))
                                            <small><i class="fas text-info fa-pen mr-2"></i></small>
                                        @endif
                                        {{ explode('\\', $log->subject_type)[2] }}
                                        {{ ucfirst($log->description) }}
                                    </span>
                                    <small>
                                        {{ $log->created_at->diffForHumans() }}
                                    </small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
                @if((config('SETTINGS::REFERRAL::ENABLED') ==true))<!--PartnerDiscount::getDiscount()--->
                    <div class="card card-default">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-handshake mr-2"></i>
                                {{ __('Partner program') }}
                            </h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body py-0 pb-2">
                            @if((config('SETTINGS::REFERRAL::ALLOWED') == "client" && Auth::user()->role != "member") || config('SETTINGS::REFERRAL::ALLOWED') == "everyone")
                                <div class="row">
                                    <div class="mt-3 col-md-8">
                                        <span class="badge badge-success" style="font-size: 14px">
                                            <i class="fa fa-user-check mr-2"></i>
                                            {{__("Your referral URL")}}:
                                            <span onmouseover="hoverIn()" onmouseout="hoverOut()" onclick="onClickCopy()" id="RefLink" style="cursor: pointer;">
                                                {{__('Click to copy')}}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="mt-3 col-md-4">
                                        <span class="badge badge-info" style="font-size: 14px">{{__("Number of referred users:")}} {{$numberOfReferrals}}</span>
                                    </div>
                                </div>
                                @if($partnerDiscount)
                                    <hr style="width: 100%; height:1px; border-width:0; background-color:#6c757d; margin-bottom: 0px">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>{{__('Your discount')}}</th>
                                            <th>{{__('Discount for your new users')}}</th>
                                            <th>{{__('Reward per registered user')}}</th>
                                            <th>{{__('New user payment commision')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{$partnerDiscount->partner_discount}}%</td>
                                                <td>{{$partnerDiscount->registered_user_discount}}%</td>
                                                <td>{{config('SETTINGS::REFERRAL::REWARD')}} {{config('SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME')}}</td>
                                                <td>{{($partnerDiscount->referral_system_commission==-1)?config('SETTINGS::REFERRAL:PERCENTAGE'):($partnerDiscount->referral_system_commission)}}%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <hr style="width: 100%; height:1px; border-width:0; background-color:#6c757d; margin-top: 0px">
                                @else
                                    <hr style="width: 100%; height:1px; border-width:0; background-color:#6c757d; margin-bottom: 0px">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>{{__('Reward per registered user')}}</th>
                                            <th>{{__('New user payment commision')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{config('SETTINGS::REFERRAL::REWARD')}} {{config('SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME')}}</td>
                                                <td>{{config('SETTINGS::REFERRAL:PERCENTAGE')}}%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <hr style="width: 100%; height:1px; border-width:0; background-color:#6c757d; margin-top: 0px">
                                @endif
                            @else
                                <span class="badge badge-warning"><i
                                        class="fa fa-user-check mr-2"></i>
                                    {{__("Make a purchase to reveal your referral-URL")}}</span>
                            @endif
                        </div>
                        <!-- /.card-body -->
                    </div>
                @endif
                <!-- /.card -->
            </div>
            <!-- /.col -->

        </div>
        <!-- END CUSTOM CONTENT -->
        </div>
    </section>
    <!-- END CONTENT -->
    <script>
        var originalText = document.getElementById('RefLink').innerText;
        var link = "<?php echo ((route("register")) . '?ref=' . (Auth::user()->referral_code));?>";
        var timeoutID;
        function hoverIn() {
            document.getElementById('RefLink').innerText = link;
            timeoutID = setTimeout(function() {
                document.getElementById('RefLink').innerText = originalText;
            }, 2000);
        }
        function hoverOut() {
            document.getElementById('RefLink').innerText = originalText;
            clearTimeout(timeoutID);
        }
        function onClickCopy() {
            if(navigator.clipboard) {
                navigator.clipboard.writeText(link).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("URL copied to clipboard")}}',
                        position: 'top-middle',
                        showConfirmButton: false,
                        background: '#343a40',
                        toast: false,
                        timer: 1000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })
                })
            } else {
                console.log('Browser Not compatible')
            }
        }
    </script>
@endsection
