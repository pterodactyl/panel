@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Servers') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('servers.index') }}">{{ __('Servers') }}</a>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('servers.create') }}">{{ __('Create') }}</a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section x-data="serverApp()" class="content">
        <div class="container-xxl">
            <!-- FORM -->
            <form action="{{ route('servers.store') }}" x-on:submit="submitClicked = true" method="post"
                class="row justify-content-center">
                @csrf
                <div class="col-xl-6 col-lg-8 col-md-8 col-sm-10">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title"><i class="fas fa-cogs mr-2"></i>{{ __('Server configuration') }}
                            </div>
                        </div>
                        @if (!config('SETTINGS::SYSTEM:CREATION_OF_NEW_SERVERS'))
                            <div class="alert alert-warning p-2 m-2">
                                The creation of new servers has been disabled for regular users, enable it again
                                <a href="{{ route('admin.settings.system') }}">{{ __('here') }}</a>.
                            </div>
                        @endif
                        @if ($productCount === 0 || $nodeCount === 0 || count($nests) === 0 || count($eggs) === 0)
                            <div class="alert alert-danger p-2 m-2">
                                <h5><i class="icon fas fa-exclamation-circle"></i>{{ __('Error!') }}</h5>
                                <p class="pl-4">
                                    @if (Auth::user()->role == 'admin')
                                        {{ __('Make sure to link your products to nodes and eggs.') }} <br>
                                        {{ __('There has to be at least 1 valid product for server creation') }}
                                        <a href="{{ route('admin.overview.sync') }}">{{ __('Sync now') }}</a>
                                    @endif

                                </p>
                                <ul>
                                    @if ($productCount === 0)
                                        <li> {{ __('No products available!') }}</li>
                                    @endif

                                    @if ($nodeCount === 0)
                                        <li>{{ __('No nodes have been linked!') }}</li>
                                    @endif

                                    @if (count($nests) === 0)
                                        <li>{{ __('No nests available!') }}</li>
                                    @endif

                                    @if (count($eggs) === 0)
                                        <li>{{ __('No eggs have been linked!') }}</li>
                                    @endif
                                </ul>
                            </div>
                        @endif


                        <div x-show="loading" class="overlay dark">
                            <i class="fas fa-2x fa-sync-alt"></i>
                        </div>

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="list-group pl-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="name">{{ __('Name') }}</label>
                                <input x-model="name" id="name" name="name" type="text" required="required"
                                    class="form-control @error('name') is-invalid @enderror">
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nest">{{ __('Software / Games') }}</label>
                                        <select class="custom-select" required name="nest" id="nest"
                                            x-model="selectedNest" @change="setEggs();">
                                            <option selected disabled hidden value="null">
                                                {{ count($nests) > 0 ? __('Please select software ...') : __('---') }}
                                            </option>
                                            @foreach ($nests as $nest)
                                                <option value="{{ $nest->id }}">{{ $nest->name }}</option>
                                            @endforeach
                                        </select>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="egg">{{ __('Specification ') }}</label>
                                        <div>
                                            <select id="egg" required name="egg" :disabled="eggs.length == 0"
                                                x-model="selectedEgg" @change="fetchLocations();" required="required"
                                                class="custom-select">
                                                <option x-text="getEggInputText()" selected disabled hidden value="null">
                                                </option>
                                                <template x-for="egg in eggs" :key="egg.id">
                                                    <option x-text="egg.name" :value="egg.id"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="node">{{ __('Node') }}</label>
                                <select name="node" required id="node" x-model="selectedNode"
                                    :disabled="!fetchedLocations" @change="fetchProducts();" class="custom-select">
                                    <option x-text="getNodeInputText()" disabled selected hidden value="null">
                                    </option>

                                    <template x-for="location in locations" :key="location.id">
                                        <optgroup :label="location.name">

                                            <template x-for="node in location.nodes" :key="node.id">
                                                <option x-text="node.name" :value="node.id">

                                                </option>
                                            </template>
                                        </optgroup>
                                    </template>

                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-100"></div>
                <div class="col" x-show="selectedNode != null">
                    <div class="row mt-4 justify-content-center">
                        <template x-for="product in products" :key="product.id">
                            <div class="card  col-xl-3 col-lg-3 col-md-4 col-sm-10 mr-2 ml-2 ">
                                <div class="card-body d-flex  flex-column">
                                    <h4 class="card-title" x-text="product.name"></h4>
                                    <div class="mt-2">
                                        <div>
                                            <p class="card-text text-muted mb-1">{{ __('Resource Data:') }}</p>
                                            <ul class="pl-0">
                                                <li class="d-flex justify-content-between">
                                                    <span class="d-inline-block"><i class="fas fa-microchip"></i>
                                                        {{ __('CPU') }}</span>
                                                    <span class=" d-inline-block"
                                                        x-text="product.cpu + ' {{ __('vCores') }}'"></span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="d-inline-block"><i class="fas fa-memory"></i>
                                                        {{ __('Memory') }}</span>
                                                    <span class=" d-inline-block"
                                                        x-text="product.memory + ' {{ __('MB') }}'"></span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <div>
                                                        <i class="fas fa-hdd"></i>
                                                        <span class="d-inline-block">
                                                            {{ __('Disk') }}
                                                        </span>
                                                    </div>
                                                    <span class="d-inline-block"
                                                        x-text="product.disk + ' {{ __('MB') }}'"></span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="d-inline-block"><i class="fas fa-save"></i>
                                                        {{ __('Backups') }}</span>
                                                    <span class=" d-inline-block" x-text="product.backups"></span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="d-inline-block"><i class="fas fa-database"></i>
                                                        {{ __('MySQL') }}
                                                        {{ __('Databases') }}</span>
                                                    <span class="d-inline-block" x-text="product.databases"></span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="d-inline-block"><i class="fas fa-network-wired"></i>
                                                        {{ __('Allocations') }}
                                                        ({{ __('ports') }})</span>
                                                    <span class="d-inline-block" x-text="product.allocations"></span>
                                                </li>
                                                <li class="d-flex justify-content-between">
                                                    <span class="d-inline-block"><i class="fas fa-clock"></i>
                                                        {{ __('Billing Period') }}</span>

                                                    <span class="d-inline-block" x-text="product.billing_period"></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="mt-2 mb-2">
                                            <span class="card-text text-muted">{{ __('Description') }}</span>
                                            <p class="card-text" style="white-space:pre-wrap"
                                                x-text="product.description"></p>
                                        </div>
                                    </div>
                                    <div class="mt-auto border rounded border-secondary">
                                        <div class="d-flex justify-content-between p-2">
                                            <span class="d-inline-block mr-4"
                                                x-text="'{{ __('Price') }}' + ' (' + product.billing_period + ')'">
                                            </span>
                                            <span class="d-inline-block"
                                                x-text="product.price + ' {{ CREDITS_DISPLAY_NAME }}'"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <input type="hidden" name="product" x-model="selectedProduct">
                                    </div>
                                    <div>
                                        <button type="submit" x-model="selectedProduct" name="product"
                                            :disabled="product.minimum_credits > user.credits || product.doesNotFit == true ||
                                                product.price > user.credits || submitClicked"
                                            :class="product.minimum_credits > user.credits || product.doesNotFit == true ||
                                                product.price > user.credits || submitClicked ? 'disabled' : ''"
                                            class="btn btn-primary btn-block mt-2" @click="setProduct(product.id);"
                                            x-text=" product.doesNotFit == true ? '{{ __('Server cant fit on this Node') }}' : (product.minimum_credits > user.credits || product.price > user.credits ? '{{ __('Not enough') }} {{ CREDITS_DISPLAY_NAME }}!' : '{{ __('Create server') }}')">
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </form>
            <!-- END FORM -->

        </div>
    </section>
    <!-- END CONTENT -->


    <script>
        function serverApp() {
            return {
                //loading
                loading: false,
                fetchedLocations: false,
                fetchedProducts: false,

                //input fields
                name: null,
                selectedNest: null,
                selectedEgg: null,
                selectedNode: null,
                selectedProduct: null,

                //selected objects based on input
                selectedNestObject: {},
                selectedEggObject: {},
                selectedNodeObject: {},
                selectedProductObject: {},

                //values
                user: {!! $user !!},
                nests: {!! $nests !!},
                eggsSave: {!! $eggs !!}, //store back-end eggs
                eggs: [],
                locations: [],
                products: [],

                submitClicked: false,


                /**
                 * @description set available eggs based on the selected nest
                 * @note called whenever a nest is selected
                 * @see selectedNest
                 */
                async setEggs() {
                    this.fetchedLocations = false;
                    this.fetchedProducts = false;
                    this.locations = [];
                    this.products = [];
                    this.selectedEgg = 'null';
                    this.selectedNode = 'null';
                    this.selectedProduct = 'null';

                    this.eggs = this.eggsSave.filter(egg => egg.nest_id == this.selectedNest)

                    //automatically select the first entry if there is only 1
                    if (this.eggs.length === 1) {
                        this.selectedEgg = this.eggs[0].id;
                        await this.fetchLocations();
                        return;
                    }

                    this.updateSelectedObjects()
                },

                setProduct(productId) {
                    if (!productId) return

                    this.selectedProduct = productId;
                    this.updateSelectedObjects();

                },

                /**
                 * @description fetch all available locations based on the selected egg
                 * @note called whenever a server configuration is selected
                 * @see selectedEg
                 */
                async fetchLocations() {
                    this.loading = true;
                    this.fetchedLocations = false;
                    this.fetchedProducts = false;
                    this.locations = [];
                    this.products = [];
                    this.selectedNode = 'null';
                    this.selectedProduct = 'null';

                    let response = await axios.get(`{{ route('products.locations.egg') }}/${this.selectedEgg}`)
                        .catch(console.error)

                    this.fetchedLocations = true;
                    this.locations = response.data

                    //automatically select the first entry if there is only 1
                    if (this.locations.length === 1 && this.locations[0]?.nodes?.length === 1) {
                        this.selectedNode = this.locations[0]?.nodes[0]?.id;
                        await this.fetchProducts();
                        return;
                    }

                    this.loading = false;
                    this.updateSelectedObjects()
                },

                /**
                 * @description fetch all available products based on the selected node
                 * @note called whenever a node is selected
                 * @see selectedNode
                 */
                async fetchProducts() {
                    this.loading = true;
                    this.fetchedProducts = false;
                    this.products = [];
                    this.selectedProduct = 'null';

                    let response = await axios.get(
                            `{{ route('products.products.node') }}/${this.selectedEgg}/${this.selectedNode}`)
                        .catch(console.error)

                    this.fetchedProducts = true;

                    // TODO: Sortable by user chosen property (cpu, ram, disk...)
                    this.products = response.data.sort((p1, p2) => parseInt(p1.price, 10) > parseInt(p2.price, 10) &&
                        1 || -1)

                    //divide cpu by 100 for each product
                    this.products.forEach(product => {
                        product.cpu = product.cpu / 100;
                    })

                    //format price to have no decimals if it is a whole number
                    this.products.forEach(product => {
                        if (product.price % 1 === 0) {
                            product.price = Math.round(product.price);
                        }
                    })


                    this.loading = false;
                    this.updateSelectedObjects()
                },


                /**
                 * @description map selected id's to selected objects
                 * @note being used in the server info box
                 */
                updateSelectedObjects() {
                    this.selectedNestObject = this.nests.find(nest => nest.id == this.selectedNest) ?? {}
                    this.selectedEggObject = this.eggs.find(egg => egg.id == this.selectedEgg) ?? {}

                    this.selectedNodeObject = {};
                    this.locations.forEach(location => {
                        if (!this.selectedNodeObject?.id) {
                            this.selectedNodeObject = location.nodes.find(node => node.id == this.selectedNode) ??
                                {};
                        }
                    })

                    this.selectedProductObject = this.products.find(product => product.id == this.selectedProduct) ?? {}
                    console.log(this.selectedProduct, this.selectedProductObject, this.products)
                },

                /**
                 * @description check if all options are selected
                 * @return {boolean}
                 */
                isFormValid() {
                    if (Object.keys(this.selectedNestObject).length === 0) return false;
                    if (Object.keys(this.selectedEggObject).length === 0) return false;
                    if (Object.keys(this.selectedNodeObject).length === 0) return false;
                    if (Object.keys(this.selectedProductObject).length === 0) return false;
                    return !!this.name;
                },

                getNodeInputText() {
                    if (this.fetchedLocations) {
                        if (this.locations.length > 0) {
                            return '{{ __('Please select a node ...') }}';
                        }
                        return '{{ __('No nodes found matching current configuration') }}'
                    }
                    return '{{ __('---') }}';
                },

                getProductInputText() {
                    if (this.fetchedProducts) {
                        if (this.products.length > 0) {
                            return '{{ __('Please select a resource ...') }}';
                        }
                        return '{{ __('No resources found matching current configuration') }}'
                    }
                    return '{{ __('---') }}';
                },

                getEggInputText() {
                    if (this.selectedNest) {
                        return '{{ __('Please select a configuration ...') }}';
                    }
                    return '{{ __('---') }}';
                },

                getProductOptionText(product) {
                    let text = product.name + ' (' + product.description + ')';

                    if (product.minimum_credits > this.user.credits) {
                        return '{{ __('Not enough credits!') }} | ' + text;
                    }

                    return text;
                }
            }
        }
    </script>
@endsection
