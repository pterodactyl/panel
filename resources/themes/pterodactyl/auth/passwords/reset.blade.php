<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ Settings::get('company', 'Pterodactyl') }} - Reset Password</title>
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
                <p class="login-box-msg">{{ trans('auth.reset_password_text') }}</p>
                <form action="{{ route('auth.reset.post') }}" method="POST">
                    <div class="form-group">
                        <label for="email" class="control-label">{{ trans('strings.email') }}</label>
                        <div>
                            <input type="text" class="form-control" name="email" id="email" value="{{ $email or old('email') }}" required autofocus placeholder="{{ trans('strings.email') }}" />
                            @if ($errors->has('email'))
                                <span class="help-block text-red small">
                                    {{ $errors->first('email') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="control-label">{{ trans('strings.password') }}</label>
                        <div>
                            <input type="password" class="form-control" name="password" id="password" required placeholder="{{ trans('strings.password') }}" />
                            @if ($errors->has('password'))
                                <span class="help-block text-red small">
                                    {{ $errors->first('password') }}
                                </span>
                            @endif
                            <p class="text-muted"><small>{{ trans('auth.password_requirements') }}</small></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="control-label">{{ trans('strings.confirm_password') }}</label>
                        <div>
                            <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" required placeholder="{{ trans('strings.confirm_password') }}" />
                            @if ($errors->has('password_confirmation'))
                                <span class="help-block text-red small">
                                    {{ $errors->first('password_confirmation') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            {!! csrf_field() !!}
                            <input type="hidden" name="token" value="{{ $token }}">
                            <button type="submit" class="btn btn-primary btn-block btn-flat">{{ trans('auth.reset_password') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {!! Theme::js('vendor/jquery/jquery.min.js') !!}
        {!! Theme::js('vendor/bootstrap/bootstrap.min.js') !!}
    </body>
</html>
