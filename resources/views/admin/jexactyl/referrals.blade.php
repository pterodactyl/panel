@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'referrals'])

@section('title')
    Referral System
@endsection

@section('content-header')
    <h1>Referral System<small>Allow users to refer others to the Panel to earn resources.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
        <form action="{{ route('admin.jexactyl.referrals') }}" method="POST">
                <div class="box
                    @if($enabled == 'true')
                        box-success
                    @else
                        box-danger
                    @endif
                ">
                    <div class="box-header with-border">
                        <i class="fa fa-user-plus"></i> <h3 class="box-title">Referrals <small>Allow users to refer others to the Panel.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Referral System</label>
                                <div>
                                    <select name="enabled" class="form-control">
                                        <option @if ($enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users can refer others.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Reward per referral</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" id="reward" name="reward" class="form-control" value="{{ $reward }}" />
                                        <span class="input-group-addon">credits</span>
                                    </div>
                                    <p class="text-muted"><small>The amount of credits to give users when they refer someone, and when someone uses a referral code.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {!! csrf_field() !!}
                <button type="submit" name="_method" value="PATCH" class="btn btn-default pull-right">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
