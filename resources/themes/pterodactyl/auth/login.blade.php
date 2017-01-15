<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ Settings::get('company', 'Pterodactyl') }} - Login</title>
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
                @if (count($errors) > 0)
                    <div class="callout callout-danger">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        {{ trans('auth.auth_error') }}<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @foreach (Alert::getMessages() as $type => $messages)
                    @foreach ($messages as $message)
                        <div class="callout callout-{{ $type }} alert-dismissable" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            {!! $message !!}
                        </div>
                    @endforeach
                @endforeach
                <p class="login-box-msg">{{ trans('auth.authentication_required') }}</p>
                <form action="{{ route('auth.login') }}" method="POST">
                    <div class="form-group has-feedback">
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="{{ trans('strings.email') }}">
                        <span class="fa fa-envelope form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback">
                        <input type="password" name="password" class="form-control" placeholder="{{ trans('strings.password') }}">
                        <span class="fa fa-lock form-control-feedback"></span>
                    </div>
                    <div class="row">
                        <div class="col-xs-8">
                            <div class="form-group has-feedback">
                                <input type="checkbox" name="remember_me" id="remember_me" /> <label for="remember_me" class="weight-300">{{ trans('auth.remember_me') }}</label>
                            </div>
                        </div>
                        <div class="col-xs-4">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-primary btn-block btn-flat">{{ trans('auth.sign_in') }}</button>
                        </div>
                    </div>
                </form>
                <a href="{{ route('auth.password') }}">{{ trans('auth.forgot_password') }}</a><br>
            </div>
        </div>
        {!! Theme::js('vendor/jquery/jquery.min.js') !!}
        {!! Theme::js('vendor/bootstrap/bootstrap.min.js') !!}
    </body>
</html>
