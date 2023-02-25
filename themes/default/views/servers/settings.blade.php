@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('Server Settings')}}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('servers.index') }}">{{__('Server')}}</a></li>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('servers.show', $server->id) }}">{{__('Settings')}}</a>
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
            <div class="row pt-3">
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                  <div class="card">
                    <div class="card-body p-3">
                      <div class="row">
                        <div class="col-8">
                          <div class="numbers">
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">SERVER NAME</p>
                            <h5 class="font-weight-bolder" id="domain_text">
                              <span class="text-success text-sm font-weight-bolder">{{ $server->name }}</span>
                            </h5>
                          </div>
                        </div>
                        <div class="col-4 text-end">
                          <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                            <i class='bx bx-fingerprint' style="color: white;"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                  <div class="card">
                    <div class="card-body p-3">
                      <div class="row">
                        <div class="col-8">
                          <div class="numbers">
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">CPU</p>
                            <h5 class="font-weight-bolder">
                              <span class="text-success text-sm font-weight-bolder">@if($server->product->cpu == 0)Unlimited @else {{$server->product->cpu}} % @endif</span>
                            </h5>
                          </div>
                        </div>
                        <div class="col-4 text-end">
                          <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                            <i class='bx bxs-chip' style="color: white;"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                  <div class="card">
                    <div class="card-body p-3">
                      <div class="row">
                        <div class="col-8">
                          <div class="numbers">
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Memory</p>
                            <h5 class="font-weight-bolder">
                              <span class="text-success text-sm font-weight-bolder">@if($server->product->memory == 0)Unlimited @else {{$server->product->memory}}MB @endif</span>
                            </h5>
                          </div>
                        </div>
                        <div class="col-4 text-end">
                          <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                            <i class='bx bxs-memory-card' style="color: white;"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                  <div class="card">
                    <div class="card-body p-3">
                      <div class="row">
                        <div class="col-8">
                          <div class="numbers">
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">STORAGE</p>
                            <h5 class="font-weight-bolder">
                              <span class="text-success text-sm font-weight-bolder">@if($server->product->disk == 0)Unlimited @else {{$server->product->disk}}MB @endif</span>
                            </h5>
                          </div>
                        </div>
                        <div class="col-4 text-end">
                          <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                            <i class='bx bxs-hdd' style="color: white;"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title float-right"><i title="Created at" class="fas fa-calendar-alt mr-2"></i><span>{{ $server->created_at->isoFormat('LL') }}</span></h5>
                    <h5 class="card-title"><i class="fas fa-sliders-h mr-2"></i>{{__('Server Information')}}</h5>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Server ID')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $server->id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Pterodactyl ID')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $server->identifier }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Hourly Price')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                      {{ number_format($server->product->getHourlyPrice(), 2, '.', '') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Monthly Price')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                      {{ $server->product->getHourlyPrice() * 24 * 30 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Location')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $server->location }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Node')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $server->node }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('Backups')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $server->product->backups }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-lg-4">
                                    <label>{{__('MySQL Database')}}</label>
                                </div>
                                <div class="col-lg-8">
                                    <span style="max-width: 250px;" class="d-inline-block text-truncate">
                                        {{ $server->product->databases }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-footer">
                    <div class="col-md-12 text-center">
                        <!-- Upgrade Button trigger modal -->
                        @if(config("SETTINGS::SYSTEM:ENABLE_UPGRADE"))
                            <button type="button" data-toggle="modal" data-target="#UpgradeModal{{ $server->id }}" target="__blank"
                                class="btn btn-info btn-md">
                                <i class="fas fa-upload mr-2"></i>
                                <span>{{ __('Upgrade / Downgrade') }}</span>
                            </button>




                        <!-- Upgrade Modal -->
                        <div style="width: 100%; margin-block-start: 100px;" class="modal fade" id="UpgradeModal{{ $server->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header card-header">
                                        <h5 class="modal-title">{{__("Upgrade/Downgrade Server")}}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body card-body">
                                        <strong>{{__("Current Product")}}: </strong> {{ $server->product->name }}
                                        <br>
                                        <br>

                                    <form action="{{ route('servers.upgrade', ['server' => $server->id]) }}" method="POST" class="upgrade-form">
                                      @csrf
                                          <select name="product_upgrade" id="product_upgrade" class="form-input2 form-control">
                                            <option value="">{{__("Select the product")}}</option>
                                              @foreach($products as $product)
                                                  @if(in_array($server->egg, $product->eggs) && $product->id != $server->product->id && $product->disabled == false)
                                                    <option value="{{ $product->id }}" @if($product->doesNotFit)disabled @endif>{{ $product->name }} [ {{ CREDITS_DISPLAY_NAME }} {{ $product->price }} @if($product->doesNotFit)] {{__('Server can\'t fit on this node')}} @else @if($product->minimum_credits!=-1) /
                                                        {{__("Required")}}: {{$product->minimum_credits}} {{ CREDITS_DISPLAY_NAME }}@endif ] @endif</option>
                                                  @endif
                                              @endforeach
                                          </select>
                                          
                                          <br> <strong>{{__("Caution") }}:</strong> {{__("Upgrading/Downgrading your server will reset your billing cycle to now. Your overpayed Credits will be refunded. The price for the new billing cycle will be withdrawed")}}. <br>
                                          <br> {{__("Server will be automatically restarted once upgraded")}}
                                    </div>
                                    <div class="modal-footer card-body">
                                        <button type="submit" class="btn btn-primary upgrade-once" style="width: 100%"><strong>{{__("Change Product")}}</strong></button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                        <!-- Delete Button trigger modal -->
                        <button type="button" data-toggle="modal" data-target="#DeleteModal" target="__blank"
                            class="btn btn-danger btn-md">
                            <i class="fas fa-trash mr-2"></i>
                            <span>{{ __('Delete') }}</span>
                        </button>
                        <!-- Delete Modal -->
                        <div class="modal fade" id="DeleteModal" tabindex="-1" role="dialog" aria-labelledby="DeleteModalLabel" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="DeleteModalLabel">{{__("Delete Server")}}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                  <span aria-hidden="true">&times;</span>
                                </button>
                              </div>
                              <div class="modal-body">
                                {{__("This is an irreversible action, all files of this server will be removed!")}}
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <form class="d-inline" method="post" action="{{ route('servers.destroy', ['server' => $server->id]) }}">
                                  @csrf
                                  @method('DELETE')
                                  <button data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-danger mr-1">{{__("Delete")}}</button>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
            </div>



        </div>
        <!-- END CUSTOM CONTENT -->
        </div>
    </section>
    <!-- END CONTENT -->
    <script type="text/javascript">
      $(".upgrade-form").submit(function (e) {

          $(".upgrade-once").attr("disabled", true);
          return true;
      })

     </script>

@endsection
