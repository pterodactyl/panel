{{--
    Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
--}}
@extends('layouts.master')

@section('title', '503: Server Temporarily Unavaliable')

@section('content')
<div class="col-md-12">
    <div class="panel panel-danger">
        <div class="panel-heading">
            <h3 class="panel-title">HTTP 503: Temporarily Unavaliable</h3>
        </div>
        <div class="panel-body">
            <p style="margin-bottom:0;">The requested server is still completing the install process. Please check back in a few minutes, you should recieve an email as soon as this process is completed.</p>
            <br /><br />
            <div class="progress progress-striped active">
                <div class="progress-bar progress-bar-danger" style="width: 75%"></div>
            </div>
        </div>
    </div>
    <p style="text-align:center;"><a href="{{ URL::previous() }}">Take me back</a> or <a href="/">go home</a>.</p>
</div>
@endsection
