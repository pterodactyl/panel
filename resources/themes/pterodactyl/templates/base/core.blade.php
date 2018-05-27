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
    <router-view></router-view>
    <div class="w-full m-auto mt-0">
        <p class="text-right text-grey-dark text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
