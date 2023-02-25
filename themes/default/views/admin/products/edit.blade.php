@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Products')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('home')}}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a href="{{route('admin.products.index')}}">{{__('Products')}}</a>
                        </li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                                       href="{{route('admin.products.edit' , $product->id)}}">{{__('Edit')}}</a>
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
            <form action="{{route('admin.products.update' , $product->id)}}" method="POST">
                @csrf
                @method('PATCH')

                <div class="row">
                    <div class="col-lg-6">

                        @if($product->servers()->count() > 0)
                            <div class="callout callout-danger">
                                <h4>{{__('Editing the resource options will not automatically update the servers on
                                    pterodactyls side!')}}'</h4>
                                <p class="text-muted">{{__('Automatically updating resource options on pterodactyl side is on
                                    my todo list :)')}}</p>
                            </div>
                        @endif

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">{{__('Product Details')}}</h5>
                            </div>
                            <div class="card-body">

                                <div class="d-flex flex-row-reverse">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" @if($product->disabled) checked @endif name="disabled"
                                               class="custom-control-input custom-control-input-danger" id="switch1">
                                        <label class="custom-control-label" for="switch1">{{__('Disabled')}} <i
                                                data-toggle="popover" data-trigger="hover"
                                                data-content="{{__('Will hide this option from being selected')}}"
                                                class="fas fa-info-circle"></i></label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6">

                                        <div class="form-group">
                                            <label for="name">{{__('Name')}}</label>
                                            <input value="{{ $product->name }}" id="name" name="name" type="text"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   required="required">
                                            @error('name')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="price">{{__('Price in')}} {{CREDITS_DISPLAY_NAME}}</label>
                                            <input value="{{$product->price}}" id="price" name="price"
                                                   type="number"
                                                   step="0.0001"
                                                   class="form-control @error('price') is-invalid @enderror"
                                                   required="required">
                                            @error('price')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="memory">{{__('Memory')}}</label>
                                            <input value="{{ $product->memory }}" id="memory" name="memory"
                                                   type="number"
                                                   class="form-control @error('memory') is-invalid @enderror"
                                                   required="required">
                                            @error('memory')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="cpu">{{__('Cpu')}}</label>
                                            <input value="{{ $product->cpu }}" id="cpu" name="cpu" type="number"
                                                   class="form-control @error('cpu') is-invalid @enderror"
                                                   required="required">
                                            @error('cpu')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="swap">{{__('Swap')}}</label>
                                            <input value="{{ $product->swap }}" id="swap" name="swap" type="number"
                                                   class="form-control @error('swap') is-invalid @enderror"
                                                   required="required">
                                            @error('swap')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="allocations">{{__('Allocations')}}</label>
                                            <input value="{{ $product->allocations }}" id="allocations"
                                                   name="allocations" type="number"
                                                   class="form-control @error('allocations') is-invalid @enderror"
                                                   required="required">
                                            @error('allocations')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="description">{{__('Description')}} <i data-toggle="popover"
                                                                                              data-trigger="hover"
                                                                                              data-content="{{__('This is what the users sees')}}"
                                                                                              class="fas fa-info-circle"></i></label>
                                            <textarea id="description" name="description"
                                                      type="text"
                                                      class="form-control @error('description') is-invalid @enderror"
                                                      required="required">{{$product->description}}</textarea>
                                            @error('description')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="disk">{{__('Disk')}}</label>
                                            <input value="{{ $product->disk }}" id="disk" name="disk" type="number"
                                                   class="form-control @error('disk') is-invalid @enderror"
                                                   required="required">
                                            @error('disk')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="billing_period">{{__('Billing Period')}} <i
                                                    data-toggle="popover" data-trigger="hover"
                                                    data-content="{{__('Period when the user will be charged for the given price')}}"
                                                    class="fas fa-info-circle"></i></label>

                                            <select id="billing_period" style="width:100%" class="custom-select" name="billing_period" required
                                                autocomplete="off" @error('billing_period') is-invalid @enderror>
                                                    <option value="hourly" @if ($product->billing_period == 'hourly') selected
                                                    @endif>
                                                        {{__('Hourly')}}
                                                    </option>
                                                    <option value="daily" @if ($product->billing_period  == 'daily') selected
                                                    @endif>
                                                        {{__('Daily')}}
                                                    </option>
                                                    <option value="weekly" @if ($product->billing_period  == 'weekly') selected
                                                    @endif>
                                                        {{__('Weekly')}}
                                                    </option>
                                                     <option value="monthly" @if ($product->billing_period  == 'monthly') selected
                                                     @endif>
                                                        {{__('Monthly')}}
                                                    </option>
                                                    <option value="quarterly" @if ($product->billing_period  == 'quarterly') selected
                                                    @endif>
                                                        {{__('Quarterly')}}
                                                    </option>
                                                    <option value="half-annually" @if ($product->billing_period  == 'half-annually') selected
                                                    @endif>
                                                        {{__('Half Annually')}}
                                                    </option>
                                                    <option value="annually" @if ($product->billing_period  == 'annually') selected
                                                    @endif>
                                                        {{__('Annually')}}
                                                    </option>
                                            </select>
                                            @error('billing_period')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label for="minimum_credits">{{__('Minimum')}} {{ CREDITS_DISPLAY_NAME }} <i
                                                    data-toggle="popover" data-trigger="hover"
                                                    data-content="{{__('Setting to -1 will use the value from configuration.')}}"
                                                    class="fas fa-info-circle"></i></label>
                                            <input value="{{ $product->minimum_credits }}" id="minimum_credits"
                                                   name="minimum_credits" type="number"
                                                   class="form-control @error('minimum_credits') is-invalid @enderror"
                                                   required="required">
                                            @error('minimum_credits')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="io">{{__('IO')}}</label>
                                            <input value="{{ $product->io }}" id="io" name="io" type="number"
                                                   class="form-control @error('io') is-invalid @enderror"
                                                   required="required">
                                            @error('io')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="databases">{{__('Databases')}}</label>
                                            <input value="{{ $product->databases }}" id="databases" name="databases"
                                                   type="number"
                                                   class="form-control @error('databases') is-invalid @enderror"
                                                   required="required">
                                            @error('databases')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="backups">{{__('Backups')}}</label>
                                            <input value="{{ $product->backups }}" id="backups" name="backups"
                                                   type="number"
                                                   class="form-control @error('backups') is-invalid @enderror"
                                                   required="required">
                                            @error('backups')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group text-right">
                                    <button type="submit" class="btn btn-primary">
                                        {{__('Submit')}}
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">{{__('Product Linking')}}
                                    <i data-toggle="popover"
                                       data-trigger="hover"
                                       data-content="{{__('Link your products to nodes and eggs to create dynamic pricing for each option')}}"
                                       class="fas fa-info-circle"></i></h5>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <label for="nodes">{{__('Nodes')}}</label>
                                    <select id="nodes" style="width:100%"
                                            class="custom-select @error('nodes') is-invalid @enderror" name="nodes[]"
                                            multiple="multiple" autocomplete="off">
                                        @foreach($locations as $location)
                                            <optgroup label="{{$location->name}}">
                                                @foreach($location->nodes as $node)
                                                    <option @if($product->nodes->contains('id' , $node->id)) selected
                                                            @endif value="{{$node->id}}">{{$node->name}}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('nodes')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                    <div class="text-muted">
                                        {{__('This product will only be available for these nodes')}}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="eggs">Eggs</label>
                                    <select id="eggs" style="width:100%"
                                            class="custom-select @error('eggs') is-invalid @enderror" name="eggs[]"
                                            multiple="multiple" autocomplete="off">
                                        @foreach($nests as $nest)
                                            <optgroup label="{{$nest->name}}">
                                                @foreach($nest->eggs as $egg)
                                                    <option @if($product->eggs->contains('id' , $egg->id)) selected
                                                            @endif value="{{$egg->id}}">{{$egg->name}}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('eggs')
                                    <div class="text-danger">
                                        {{$message}}
                                    </div>
                                    @enderror
                                    <div class="text-muted">
                                        {{__('This product will only be available for these eggs')}}
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </section>
    <!-- END CONTENT -->

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            $('.custom-select').select2();
        })
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('[data-toggle="popover"]').popover();
        });
    </script>

@endsection
