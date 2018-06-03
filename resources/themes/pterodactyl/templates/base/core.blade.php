@extends('templates/wrapper')

@section('container')
    <router-view></router-view>
@endsection

@section('below-container')
    <div class="flex-grow"></div>
    <div class="w-full m-auto mt-0 container">
        <p class="text-right text-grey-dark text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
