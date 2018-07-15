@extends('templates/wrapper')

{{--
=======
@section('above-container')
    <header class="bg-blue text-white text-xl rounded-b fixed pin-t w-full z-40 shadow-md">
        <div class="container h-16 mx-auto flex">
            <img class="h-12 mt-2 mr-3" src="/assets/img/pterodactyl-flat.svg">
            <div class="py-6">PTERODACTYL</div>
            <div class="flex-grow"></div>
            <nav class="nav text-lg">
                <router-link to="/"><font-awesome-icon class="mr-2" fixed-with icon="server"></font-awesome-icon>Servers</router-link>
                <a href="#"><font-awesome-icon class="mr-2" fixed-with icon="cogs"></font-awesome-icon>Admin</a>
                <a href="#"><font-awesome-icon class="mr-2" fixed-with icon="user"></font-awesome-icon>schrej</a>
                <a href="/auth/logout"><font-awesome-icon fixed-with icon="sign-out-alt"></font-awesome-icon></a>
            </nav>
        </div>
    </header>
    <div class="h-16 mb-6"></div>
    {{--<div class="nav">
        <div class="logo">
            Pterodactyl
        </div>
        <div class="menu">
            <ul>
                <li>
                    <router-link to="/">
                        <span>Your Servers</span>
                    </router-link>
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
--}}

@section('container')
    <router-view></router-view>
@endsection

@section('below-container')
    <div class="flex-grow"></div>
    <div class="w-full m-auto mt-0 container">
        <p class="text-center sm:text-right text-grey-dark text-xs">
            {!! trans('strings.copyright', ['year' => date('Y')]) !!}
        </p>
    </div>
@endsection
