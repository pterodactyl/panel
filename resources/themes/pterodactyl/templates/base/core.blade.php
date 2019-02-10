@extends('templates/wrapper')

@section('container')
    <router-view></router-view>
@endsection

@section('below-container')
    <div class="flex-grow"></div>
    <div class="w-full m-auto mt-0 mb-6 container">
        <p class="text-center sm:text-right text-neutral-300 text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
