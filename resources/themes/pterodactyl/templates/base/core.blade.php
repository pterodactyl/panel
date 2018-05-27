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
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
@endsection

@section('container')
    <div class="server-search animate fadein">
        <input type="text" placeholder="search for servers..."/>
    </div>
    <div class="w-full m-auto mt-4 animate fadein sm:flex flex-wrap content-start">
        @foreach($servers as $server)
            <div class="server-box">
                <div class="content">
                    <div class="float-right">
                        <div class="indicator {{ ['online', 'offline'][rand(0, 1)] }}"></div>
                    </div>
                    <div class="mb-4">
                        <div class="text-black font-bold text-xl">{{ $server->name }}</div>
                    </div>
                    <div class="mb-0 flex">
                        <div class="usage">
                            <div class="indicator-title">CPU</div>
                        </div>
                        <div class="usage">
                            <div class="indicator-title">Memory</div>
                        </div>
                    </div>
                    <div class="mb-4 flex text-center">
                        <div class="inline-block border border-grey-lighter border-l-0 p-4 flex-1">
                            <span class="font-bold text-xl">{{ rand(1, 200) }}</span>
                            <span class="font-light text-sm">%</span>
                        </div>
                        <div class="inline-block border border-grey-lighter border-l-0 border-r-0 p-4 flex-1">
                            <span class="font-bold text-xl">{{ rand(128, 2048) }}</span>
                            <span class="font-light text-sm">Mb</span>
                        </div>
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
    <div class="w-full m-auto mt-0">
        <p class="text-right text-grey-dark text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
