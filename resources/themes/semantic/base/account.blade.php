@extends('layouts.master')
@section('title', 'Your Account')

@section('content')
    <div class="ui grid">
        <div class="two column row">
            <div class="column">
                <div class="ui fluid card">
                    <div class="content">
                        <div class="header">{{ trans('base.account.update_pass') }}</div>
                    </div>
                    <div class="content">
                        <div class="ui small feed">
                            <div class="content">
                                <form class="ui form" action="/account/password" method="post">
                                    <div class="field">
                                        <label>{{ trans('strings.current_password') }}</label>
                                        <input type="password" name="current_password">
                                    </div>
                                    <div class="field">
                                        <label>{{ trans('base.account.new_password') }}</label>
                                        <input type="password" name="new_password">
                                        <small>{{ trans('base.password_req') }}</small>
                                    </div>
                                    <div class="field">
                                        <label>{{ trans('base.account.new_password') }} {{ trans('strings.again') }}</label>
                                        <input type="password" name="new_password_confirmation">
                                    </div>
                                    {!! csrf_field() !!}
                                    <button class="ui fluid button" type="submit">{{ trans('base.account.update_pass') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="ui fluid card">
                    <div class="content">
                        <div class="header">{{ trans('base.account.update_email') }}</div>
                    </div>
                    <div class="content">
                        <div class="ui small feed">
                            <div class="content">
                                <form class="ui form" action="/account/email" method="post">
                                    <div class="field">
                                        <label>{{ trans('base.account.new_email') }}</label>
                                        <input type="text" name="new_email">
                                    </div>
                                    <div class="field">
                                        <label>{{ trans('strings.current_password') }}</label>
                                        <input type="password" name="current_password">
                                    </div>
                                    {!! csrf_field() !!}
                                    <button class="ui fluid button" type="submit">{{ trans('base.account.update_email') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection