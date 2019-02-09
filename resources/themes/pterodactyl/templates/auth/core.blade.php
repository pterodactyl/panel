@extends('templates/wrapper', [
    'css' => ['body' => 'bg-neutral-900']
])

@section('container')
    <div class="w-full max-w-xs sm:max-w-sm m-auto mt-8">
        <div class="text-center hidden sm:block">
            <img src="/assets/img/pterodactyl-flat.svg" class="max-w-xxs">
        </div>
        <router-view></router-view>
        <p class="text-center textneutral-500stext-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
