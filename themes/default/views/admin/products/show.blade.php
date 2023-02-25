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
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{__('Products')}}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('admin.products.show', $product->id) }}">{{__('Show')}}</a>
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

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title"><i class="fas fa-sliders-h mr-2"></i>{{__('Product')}}</h5>
                    <div class="ml-auto">
                        <a data-content="Edit" data-trigger="hover" data-toggle="tooltip"
                            href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-info mr-1"><i
                                class="fas fa-pen"></i></a>
                        <form class="d-inline" onsubmit="return submitResult();" method="post"
                            action="{{ route('admin.products.destroy', $product->id) }}">
                            {{ csrf_field() }}
                            {{ method_field('DELETE') }}
                            <button data-content="Delete" data-trigger="hover" data-toggle="tooltip"
                                class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('ID')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->id }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Name')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->name }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Price')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        <i class="fas fa-coins mr-1"></i>{{ $product->price }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Minimum')}} {{ CREDITS_DISPLAY_NAME }}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        @if ($product->minimum_credits == -1)
                                            <i class="fas fa-coins mr-1"></i>{{ $minimum_credits }}
                                        @else
                                            <i class="fas fa-coins mr-1"></i>{{ $product->minimum_credits }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Memory')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->memory }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('CPU')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->cpu }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Swap')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->swap }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Disk')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->disk }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('IO')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->io }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Databases')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->databases }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Allocations')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->allocations }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Created at')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->created_at ? $product->created_at->diffForHumans() : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Description')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span class="d-inline-block text-truncate">
                                        {{ $product->description }}
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Updated at')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $product->updated_at ? $product->updated_at->diffForHumans() : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-server mr-2"></i>{{__('Servers')}}</h5>
                </div>
                <div class="card-body table-responsive">

                    @include('admin.servers.table' , ['filter' => '?product=' . $product->id])

                </div>
            </div>


        </div>
        <!-- END CUSTOM CONTENT -->
        </div>
    </section>
    <!-- END CONTENT -->



@endsection
