@extends('templates/wrapper')

@section('above-container')
    <div class="nav">
        <div class="logo">
            Pterodactyl
        </div>
        <div class="menu">
            <ul>
                <li>
                    <a href="#">
                        <span>Your Servers</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <span>Admin</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <span>dane</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('auth.logout') }}">
                        <span>L</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
@endsection

@section('container')
    <div class="w-full m-auto mt-8 animate fadein sm:block md:flex">
        @foreach($servers as $server)
            <div class="w-full mr-4 flex flex-col">
                <div class="border border-grey-light bg-white rounded p-4 justify-between leading-normal">
                    <div class="float-right">
                        <div class="indicator {{ ['online', 'offline'][rand(0, 1)] }}"></div>
                    </div>
                    <div class="mb-4">
                        {{--@if ($server->owner_id !== Auth::user()->id)--}}
                        {{--<p class="text-sm text-grey-dark flex items-center">--}}
                            {{--<svg class="fill-current text-grey w-3 h-3 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">--}}
                                {{--<path d="M4 8V6a6 6 0 1 1 12 0v2h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-8c0-1.1.9-2 2-2h1zm5 6.73V17h2v-2.27a2 2 0 1 0-2 0zM7 6v2h6V6a3 3 0 0 0-6 0z" ></path>--}}
                            {{--</svg>--}}
                            {{--Restricted Access--}}
                        {{--</p>--}}
                        {{--@endif--}}
                        <div class="text-black font-bold text-xl">{{ $server->name }}</div>
                        {{--<div class="flex text-center">--}}
                            {{--<div class="flex-1">68%</div>--}}
                            {{--<div class="flex-1">124 / 1024 Mb</div>--}}
                        {{--</div>--}}
                    </div>
                    <div class="flex items-center">
                        <div class="text-sm">
                            <p class="text-grey">{{ $server->node->name }}</p>
                            <p class="text-grey-dark">{{ $server->allocation->ip }}:{{ $server->allocation->port }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="w-full m-auto mt-4">
        <p class="text-right text-grey-dark text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
