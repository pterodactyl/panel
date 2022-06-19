@extends('layouts.admin')
@include('partials/admin.jexactyl.nav', ['activeTab' => 'store'])

@section('title')
    Jexactyl Settings
@endsection

@section('content-header')
    <h1>Jexactyl Store<small>Configure the Jexactyl storefront.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Jexactyl</li>
    </ol>
@endsection

@section('content')
    @yield('jexactyl::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.jexactyl.store') }}" method="POST">
                <div class="box
                    @if($enabled == 'true')
                        box-success
                    @else
                        box-danger
                    @endif
                ">
                    <div class="box-header with-border">
                        <h3 class="box-title">Jexactyl Storefront <small>Configure whether certain options for the store are enabled.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Storefront Enabled</label>
                                <div>
                                    <select name="store:enabled" class="form-control">
                                        <option @if ($enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users can access the store UI.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">PayPal Enabled</label>
                                <div>
                                    <select name="store:paypal:enabled" class="form-control">
                                        <option @if ($paypal_enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($paypal_enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users can buy credits with PayPal.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Stripe Enabled</label>
                                <div>
                                    <select name="store:stripe:enabled" class="form-control">
                                        <option @if ($stripe_enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($stripe_enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users can buy credits with Stripe.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Name of currency</label>
                                <div>
                                    <input type="text" class="form-control" name="store:currency" value="{{ $currency }}" />
                                    <p class="text-muted"><small>The name of the currency used for Jexactyl. (Default: JCR)</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Idle Earning <small>Configure settings for passive credit earning.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Enabled</label>
                                <div>
                                    <select name="earn:enabled" class="form-control">
                                        <option @if ($earn_enabled == 'false') selected @endif value="false">Disabled</option>
                                        <option @if ($earn_enabled == 'true') selected @endif value="true">Enabled</option>
                                    </select>
                                    <p class="text-muted"><small>Determines whether users can earn credits passively.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Amount of credits per minute</label>
                                <div>
                                    <input type="text" class="form-control" name="earn:amount" value="{{ $earn_amount }}" />
                                    <p class="text-muted"><small>The amount of credits a user should be given per minute of AFK.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Resource Pricing <small>Set specific pricing for resources.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 50% CPU</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:cpu" value="{{ $cpu }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 50% CPU.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 1GB RAM</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:memory" value="{{ $memory }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 1GB of RAM.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 1GB Disk</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:disk" value="{{ $disk }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 1GB of disk.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 1 Server Slot</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:slot" value="{{ $slot }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 1 server slot.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 1 Network Allocation</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:port" value="{{ $port }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 1 port.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 1 Server Backup</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:backup" value="{{ $backup }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 1 backup.</small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Cost per 1 Server Database</label>
                                <div>
                                    <input type="text" class="form-control" name="store:cost:database" value="{{ $database }}" />
                                    <p class="text-muted"><small>Used to calculate the total cost for 1 database.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
