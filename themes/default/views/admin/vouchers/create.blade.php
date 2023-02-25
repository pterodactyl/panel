@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Vouchers')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a href="{{route('admin.vouchers.index')}}">{{__('Vouchers')}}</a>
                        </li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{route('admin.vouchers.create')}}">{{__('Create')}}</a>
                        </li>
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
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-money-check-alt mr-2"></i>{{__('Voucher details')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="{{route('admin.vouchers.store')}}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label for="memo">{{__('Memo')}} <i data-toggle="popover" data-trigger="hover"
                                                                        data-content="Only admins can see this"
                                                                        class="fas fa-info-circle"></i></label>
                                    <input value="{{old('memo')}}" placeholder="{{__('Summer break voucher')}}" id="memo"
                                           name="memo" type="text"
                                           class="form-control @error('memo') is-invalid @enderror">
                                    @error('memo')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="credits">* {{CREDITS_DISPLAY_NAME}}</label>
                                    <input value="{{old('credits')}}" placeholder="500" id="credits" name="credits"
                                           type="number" step="any" min="0" max="99999999"
                                           class="form-control @error('credits') is-invalid @enderror">
                                    @error('credits')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>


                                <div class="form-group">
                                    <label for="code">* {{__('Code')}}</label>
                                    <div class="input-group">
                                        <input value="{{old('code')}}" placeholder="SUMMER" id="code" name="code"
                                               type="text" class="form-control @error('code') is-invalid @enderror"
                                               required="required">
                                        <div class="input-group-append">
                                            <button class="btn btn-info" onclick="setRandomCode()" type="button">
                                            {{__('Random')}}
                                        </button>
                                    </div>
                                </div>
                                @error('code')
                                <div class="text-danger">
                                    {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="uses">* {{__('Uses')}} <i data-toggle="popover" data-trigger="hover"
                                                                          data-content="{{__('A voucher can only be used one time per user. Uses specifies the number of different users that can use this voucher.')}}"
                                                                          class="fas fa-info-circle"></i></label>
                                    <div class="input-group">
                                        <input value="{{old('uses') ?? 1}}" id="uses" min="1" max="2147483647"
                                               name="uses" type="number"
                                               class="form-control @error('uses') is-invalid @enderror"
                                               required="required">
                                        <div class="input-group-append">
                                            <button class="btn btn-info" onclick="setMaxUses()" type="button">{{__('Max')}}
                                            </button>
                                        </div>
                                    </div>
                                    @error('uses')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="expires_at">{{__('Expires at')}} <i data-toggle="popover"
                                                                                    data-trigger="hover"
                                                                                    data-content="Timezone: {{ Config::get('app.timezone') }}"
                                                                                    class="fas fa-info-circle"></i></label>
                                    <div class="input-group date" id="expires_at" data-target-input="nearest">
                                        <input value="{{old('expires_at')}}" name="expires_at"
                                               placeholder="dd-mm-yyyy hh:mm:ss" type="text"
                                               class="form-control @error('expires_at') is-invalid @enderror datetimepicker-input"
                                               data-target="#expires_at"/>
                                        <div class="input-group-append" data-target="#expires_at"
                                             data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                    @error('expires_at')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>

                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary">
                                        {{__('Submit')}}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <i class="fas"></i>

        </div>
    </section>
    <!-- END CONTENT -->


    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            $('#expires_at').datetimepicker({
                format: 'DD-MM-yyyy HH:mm:ss',
                icons: {
                    time: 'far fa-clock',
                    date: 'far fa-calendar',
                    up: 'fas fa-arrow-up',
                    down: 'fas fa-arrow-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'fas fa-calendar-check',
                    clear: 'far fa-trash-alt',
                    close: 'far fa-times-circle'
                }
            });
        })

        function setMaxUses() {
            let element = document.getElementById('uses')
            element.value = element.max;
            console.log(element.max)
        }


        function setRandomCode() {
            let element = document.getElementById('code')
            element.value = getRandomCode(36)
        }

        function getRandomCode(length) {
            let result = '';
            let characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-';
            let charactersLength = characters.length;
            for (let i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() *
                    charactersLength));
            }
            return result;
        }
    </script>


@endsection
