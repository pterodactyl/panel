@extends('layouts.app')

@section('content')

    <body class="hold-transition dark-mode login-page">
        <div class="login-box">
            <div class="card card-outline card-primary">
                <div class="card-header text-center">
                    <a href="{{ route('welcome') }}" class="h1"><b
                            class="mr-1">{{ config('app.name', 'Laravel') }}</b></a>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <p class="login-box-msg">
                        {{ __('You forgot your password? Here you can easily retrieve a new password.') }}</p>
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                placeholder="{{ __('Email') }}" name="email" value="{{ old('email') }}" required
                                autocomplete="email" autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>

                            @error('email')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        @if (config('SETTINGS::RECAPTCHA:ENABLED') == 'true')
                            <div class="input-group mb-3">
                                {!! htmlFormSnippet() !!}
                                @error('g-recaptcha-response')
                                <span class="text-danger" role="alert">
                                            <small><strong>{{ $message }}</strong></small>
                                        </span>
                                @enderror
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-12">
                                <button type="submit"
                                    class="btn btn-primary btn-block">{{ __('Request new password') }}</button>
                            </div>

                            <!-- /.col -->
                        </div>

                    </form>
                    <p class="mt-3 mb-1">
                        <a href="{{ route('login') }}">{{ __('Login') }}</a>
                    </p>
                </div>
                <!-- /.login-card-body -->
            </div>
        </div>
        <!-- /.login-box -->

        {{-- imprint and privacy policy --}}
        <div class="fixed-bottom ">
            <div class="container text-center">
                @if (config('SETTINGS::SYSTEM:SHOW_IMPRINT') == "true")
                    <a href="{{ route('imprint') }}"><strong>{{ __('Imprint') }}</strong></a> |
                @endif
                @if (config('SETTINGS::SYSTEM:SHOW_PRIVACY') == "true")
                    <a href="{{ route('privacy') }}"><strong>{{ __('Privacy') }}</strong></a>
                @endif
                @if (config('SETTINGS::SYSTEM:SHOW_TOS') == "true")
                    | <a target="_blank" href="{{ route('tos') }}"><strong>{{ __('Terms of Service') }}</strong></a>
                @endif
            </div>
        </div>
    </body>
@endsection
