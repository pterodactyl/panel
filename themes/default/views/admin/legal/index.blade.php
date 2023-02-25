@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Legal')}}</h1>

                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{route('admin.legal.index')}}">{{__('Legal')}}</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">
            <form method="POST" enctype="multipart/form-data" class="mb-3"
                  action="{{ route('admin.legal.update') }}">
                @csrf
                @method('PATCH')

            <div class="row">

                <div class="col-md-6">
                    {{-- TOS --}}
                    <div class="row mb-2">
                        <div class="col text-center">
                            <h1>{{__("Terms of Service")}}</h1>
                        </div>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <textarea x-model="tos" id="tos" name="tos"
                                  class="form-control @error('tos') is-invalid @enderror">
                        {{ $tos }}
                        </textarea>
                        @error('motd-message')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- PRIVACY --}}
                    <div class="row mb-2">
                        <div class="col text-center">
                            <h1>{{__("Privacy Policy")}}</h1>
                        </div>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <textarea x-model="privacy" id="privacy" name="privacy"
                                  class="form-control @error('privacy') is-invalid @enderror">
                        {{ $privacy }}
                        </textarea>
                        @error('privacy')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6">
                    {{-- Imprint --}}
                    <div class="row mb-2">
                        <div class="col text-center">
                            <h1>{{__("Imprint")}}</h1>
                        </div>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <textarea x-model="imprint" id="imprint" name="imprint"
                                  class="form-control @error('imprint') is-invalid @enderror">
                        {{ $imprint }}
                        </textarea>
                        @error('imprint')
                        <div class="text-danger">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>
                <div class="row">
                    <button class="btn btn-primary ml-3 mt-3">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
        <!-- END CUSTOM CONTENT -->

    </section>
    <!-- END CONTENT -->

    <script>tinymce.init({selector:'textarea',promotion: false,skin: "oxide-dark",
            content_css: "dark",branding: false,  height: 500,
            plugins: ['image','link'],});
    </script>



@endsection
