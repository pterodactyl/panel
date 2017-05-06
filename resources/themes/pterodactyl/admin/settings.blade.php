{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- Permission is hereby granted, free of charge, to any person obtaining a copy --}}
{{-- of this software and associated documentation files (the "Software"), to deal --}}
{{-- in the Software without restriction, including without limitation the rights --}}
{{-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell --}}
{{-- copies of the Software, and to permit persons to whom the Software is --}}
{{-- furnished to do so, subject to the following conditions: --}}

{{-- The above copyright notice and this permission notice shall be included in all --}}
{{-- copies or substantial portions of the Software. --}}

{{-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR --}}
{{-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, --}}
{{-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE --}}
{{-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER --}}
{{-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, --}}
{{-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE --}}
{{-- SOFTWARE. --}}
@extends('layouts.admin')

@section('title')
    Settings
@endsection

@section('content-header')
    <h1>Panel Settings<small>Configure Pterodactyl to your liking.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Panel Settings</h3>
            </div>
            <form action="{{ route('admin.settings') }}" method="POST">
                <div class="box-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="control-label">Company Name:</label>
                            <div>
                                <input type="text" class="form-control" name="company" value="{{ old('company', Settings::get('company')) }}" />
                                <p class="text-muted"><small>This is the name that is used throughout the panel and in emails sent to clients.</small></p>
                            </div>
                        </div>
                        {{-- <div class="form-group col-md-6">
                            <label class="control-label">Default Language:</label>
                            <div>
                                <select name="default_language" class="form-control">
                                    <option value="de" @if(Settings::get('default_language') === 'de')selected @endif>Deutsch</option>
                                    <option value="en" @if(Settings::get('default_language', 'en') === 'en')selected @endif>English</option>
                                    <option value="es" @if(Settings::get('default_language') === 'es')selected @endif>Espa&ntilde;ol</option>
                                    <option value="fr" @if(Settings::get('default_language') === 'fr')selected @endif>Fran&ccedil;ais</option>
                                    <option value="it" @if(Settings::get('default_language') === 'it')selected @endif>Italiano</option>
                                    <option value="pl" @if(Settings::get('default_language') === 'pl')selected @endif>Polski</option>
                                    <option value="pt" @if(Settings::get('default_language') === 'pt')selected @endif>Portugu&ecirc;s</option>
                                    <option value="ru" @if(Settings::get('default_language') === 'ru')selected @endif>&#1088;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;</option>
                                    <option value="se" @if(Settings::get('default_language') === 'se')selected @endif>Svenska</option>
                                    <option value="zh" @if(Settings::get('default_language') === 'zh')selected @endif>&#20013;&#22269;&#30340;的</option>
                                </select>
                                <p class="text-muted"><small>This is the default language that all clients will use unless they manually change it.</small></p>
                            </div>
                        </div> --}}
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">In order to modify your SMTP settings for sending mail you will need to run <code>php artisan pterodactyl:mail</code> in this project's root folder.</div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-sm btn-primary" value="Modify Settings">
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
