@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="alert alert-danger p-2 m-2">
                <h5><i class="icon fas fa-exclamation-circle"></i> {{ __('ATTENTION!') }}</h5>
                {{ __('Only edit these settings if you know exactly what you are doing ') }}
                <br>
                {{ __('You usually do not need to change anything here') }}
            </div>
            <div class="row mb-2">
                <div class="col-sm-6">

                    <h1>{{ __('Edit Server') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.servers.index') }}">{{ __('Servers') }}</a>
                        </li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('admin.servers.edit', $server->id) }}">{{ __('Edit') }}</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('admin.servers.update', $server->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="form-group">
                                    <label for="identifier">{{ __('Server identifier') }}
                                        <i data-toggle="popover" data-trigger="hover"
                                            data-content="{{ __('Change the server identifier on controlpanel to match a pterodactyl server.') }}"
                                            class="fas fa-info-circle"></i>
                                    </label>
                                    <input value="{{ $server->identifier }}" id="identifier" name="identifier"
                                        type="text" class="form-control @error('identifier') is-invalid @enderror"
                                        required="required">
                                    @error('identifier')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="user_id">{{ __('Server owner') }}
                                        <i data-toggle="popover" data-trigger="hover"
                                            data-content="{{ __('Change the current server owner on controlpanel and pterodactyl.') }}"
                                            class="fas fa-info-circle"></i>
                                    </label>
                                    <select name="user_id" id="user_id" class="form-control">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                @if ($user->id == $server->user_id) selected @endif>{{ $user->name }}
                                                ({{ $user->email }})
                                                ({{ $user->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>

                        </div>
                    </div>
                </div>

            </div>
        </div>


    </section>
    <!-- END CONTENT -->
@endsection
