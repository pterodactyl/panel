@extends('templates/wrapper')

@section('container')
    <router-view></router-view>
    <div class="w-full m-auto mt-0 container">
        <p class="text-right text-grey-dark text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
