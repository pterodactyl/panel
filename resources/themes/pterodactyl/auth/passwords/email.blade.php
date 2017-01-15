<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ Settings::get('company', 'Pterodactyl') }} - Forgot Password</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        {!! Theme::css('vendor/bootstrap/bootstrap.min.css') !!}
        {!! Theme::css('vendor/adminlte/admin.min.css') !!}
        {!! Theme::css('css/pterodactyl.css') !!}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">

        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="hold-transition login-page">
        <div class="login-box">
            <div class="login-logo">
                {{ Settings::get('company', 'Pterodactyl') }}
            </div>
            <div class="login-box-body">
                @if (session('status'))
                    <div class="callout callout-success">
                        {{ trans('auth.email_sent') }}
                    </div>
                @endif
                <p class="login-box-msg">{{ trans('auth.request_reset_text') }}</p>
                <form action="{{ route('auth.password') }}" method="POST">
                    <div class="form-group has-feedback">
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" autofocus placeholder="{{ trans('strings.email') }}">
                        <span class="fa fa-envelope form-control-feedback"></span>
                        @if ($errors->has('email'))
                            <span class="help-block text-red small">
                                {{ $errors->first('email') }}
                            </span>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-xs-4">
                            <a href="{{ route('auth.login') }}"><button type="button" class="btn btn-clear btn-block btn-flat">{{ trans('strings.login') }}</button></a>
                        </div>
                        <div class="col-xs-8">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-primary btn-block btn-flat">{{ trans('auth.request_reset') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {!! Theme::js('vendor/jquery/jquery.min.js') !!}
        {!! Theme::js('vendor/bootstrap/bootstrap.min.js') !!}
    </body>
</html>
