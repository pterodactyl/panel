@extends('templates/wrapper', [
    'css' => ['body' => 'bg-neutral-900']
])

@section('container')
    <div class="w-full max-w-xs sm:max-w-sm m-auto mt-8">
        <div id="app"></div>
        <p class="text-center text-neutral-500 text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
