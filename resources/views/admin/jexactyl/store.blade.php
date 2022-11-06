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
                        <i class="fa fa-shopping-cart"></i> <h3 class="box-title">Jexactyl Storefront <small>Configure whether certain options for the store are enabled.</small></h3>
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
                                <label class="control-label" for="store:currency">Name of currency</label>
                                <select name="store:currency" id="store:currency" class="form-control">
                                    @foreach ($currencies as $currency)
                                        <option @if ($selected_currency === $currency['code']) selected @endif value="{{ $currency['code'] }}">{{ $currency['name'] }}</option>
                                    @endforeach
                                </select>
                                <p class="text-muted"><small>The name of the currency used for Jexactyl.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box box-info">
                    <div class="box-header with-border">
                        <i class="fa fa-money"></i> <h3 class="box-title">Idle Earning <small>Configure settings for passive credit earning.</small></h3>
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
                        <i class="fa fa-dollar"></i> <h3 class="box-title">Resource Pricing <small>Set specific pricing for resources.</small></h3>
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
                <div class="box box-info">
                    <div class="box-header with-border">
                        <i class="fa fa-area-chart"></i> <h3 class="box-title">Resource Limits <small>Set limits for how many of each resource a server can be deployed with.</small></h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">CPU limit</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="store:limit:cpu" value="{{ $limit_cpu }}" />
                                        <span class="input-group-addon">%</span>
                                    </div>
                                    <p class="text-muted"><small>The maximum amount of CPU a server can be deployed with. </small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">RAM limit</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="store:limit:memory" value="{{ $limit_memory }}" />
                                        <span class="input-group-addon">MB</span>
                                    </div>
                                    <p class="text-muted"><small>The maximum amount of RAM a server can be deployed with. </small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Disk limit</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="store:limit:disk" value="{{ $limit_disk }}" />
                                        <span class="input-group-addon">MB</span>
                                    </div>
                                    <p class="text-muted"><small>The maximum amount of disk a server can be deployed with. </small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Network Allocation limit</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="store:limit:port" value="{{ $limit_port }}" />
                                        <span class="input-group-addon">ports</span>
                                    </div>
                                    <p class="text-muted"><small>The maximum amount of ports (allocations) a server can be deployed with. </small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Backup limit</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="store:limit:backup" value="{{ $limit_backup }}" />
                                        <span class="input-group-addon">backups</span>
                                    </div>
                                    <p class="text-muted"><small>The maximum amount of backups a server can be deployed with. </small></p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Database limit</label>
                                <div>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="store:limit:database" value="{{ $limit_database }}" />
                                        <span class="input-group-addon">databases</span>
                                    </div>
                                    <p class="text-muted"><small>The maximum amount of databases a server can be deployed with. </small></p>
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
