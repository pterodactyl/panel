<template>
    <div class="server-card animated-fade-in hover:shadow-md">
        <div class="content h-32 relative" :class="{
            'is-online': status === 'online',
            'is-offline': status === 'offline'
        }">
            <router-link :to="link">
                <h2 class="text-xl flex flex-row items-center mb-2">
                    <div class="identifier-icon select-none" :class="{
                        'bg-grey': status === '',
                        'bg-red': status === 'offline',
                        'bg-green': status === 'online'
                    }">
                        {{ server.name[0] }}
                    </div>
                    {{ server.name }}
                </h2>
            </router-link>
            <div class="text-grey-darker font-normal text-sm">
                <p v-if="server.description.length" class="pb-1">{{ server.description }}</p>

                <div class="absolute pin-b pin-l p-4 w-full">
                    <span class="font-semibold text-indigo">{{ server.node }}</span>
                    <span class="float-right text-grey-dark font-light">{{ server.allocation.ip }}:{{ server.allocation.port }}</span>
                </div>
            </div>
        </div>
        <div class="footer p-4 text-sm">
            <div class="inline-block pr-2">
                <div class="pillbox bg-green"><span class="select-none">MEM:</span> {{ memory }} Mb</div>
            </div>
            <div class="inline-block">
                <div class="pillbox bg-blue"><span class="select-none">CPU:</span> {{ cpu }} %</div>
            </div>
        </div>
    </div>
</template>

<script>
    import get from 'lodash/get';
    import differenceInSeconds from 'date-fns/difference_in_seconds';

    export default {
        name: 'server-box',
        props: {
            server: { type: Object, required: true },
        },

        dataGetTimeout: null,

        data: function () {
            return {
                backgroundedAt: new Date(),
                documentVisible: true,
                resources: undefined,
                cpu: 0,
                memory: 0,
                status: '',
                link: { name: 'server', params: { id: this.server.identifier }},
            };
        },

        watch: {
            /**
             * Watch the documentVisible item and perform actions when it is changed. If it becomes
             * true, we want to check how long ago the last poll was, if it was more than 30 seconds
             * we want to immediately trigger the resourceUse api call, otherwise we just want to restart
             * the time.
             *
             * If it is now false, we want to clear the timer that checks resource use, since we know
             * we won't be doing anything with them anyways. Might as well avoid extraneous resource
             * usage by the browser.
             */
            documentVisible: function (value) {
                if (!value) {
                    window.clearTimeout(this.$options.dataGetTimeout);
                    return;
                }

                if (differenceInSeconds(new Date(), this.backgroundedAt) >= 30) {
                    this.getResourceUse();
                }

                this.$options.dataGetTimeout = window.setInterval(() => {
                    this.getResourceUse();
                }, 10000);
            },
        },

        /**
         * Grab the initial resource usage for this specific server instance and add a listener
         * to monitor when this window is no longer visible. We don't want to needlessly poll the
         * API when we aren't looking at the page.
         */
        created: function () {
            this.getResourceUse();
            document.addEventListener('visibilitychange', this._visibilityChange.bind(this));
        },

        /**
         * Poll the API for changes every 10 seconds when the component is mounted.
         */
        mounted: function () {
            console.log(this.server);
            this.$options.dataGetTimeout = window.setInterval(() => {
                this.getResourceUse();
            }, 10000);
        },

        /**
         * Clear the timer and event listeners when we destroy the component.
         */
        beforeDestroy: function () {
            window.clearInterval(this.$options.dataGetTimeout);
            document.removeEventListener('visibilitychange', this._visibilityChange.bind(this), false);
        },

        methods: {
            /**
             * Query the resource API to determine what this server's state and resource usage is.
             */
            getResourceUse: function () {
                window.axios.get(this.route('api.client.servers.resources', { server: this.server.identifier }))
                    .then(response => {
                        if (!(response.data instanceof Object)) {
                            throw new Error('Received an invalid response object back from status endpoint.');
                        }


                        this.resources = response.data.attributes;
                        this.status = this.getServerStatus();

                        this.memory = Number(get(this.resources, 'memory.current', 0)).toFixed(0);
                        this.cpu = this._calculateCpu(
                            Number(get(this.resources, 'cpu.current', 0)),
                            Number(this.server.limits.cpu)
                        );
                    })
                    .catch(err => {
                        console.error({ err });
                    });
            },

            /**
             * Set the CSS to use for displaying the server's current status.
             */
            getServerStatus: function () {
                if (!(this.resources instanceof Object)) {
                    return '';
                }

                if (!this.resources.installed || this.resources.suspended) {
                    return '';
                }

                switch (this.resources.state) {
                    case 'off':
                        return 'offline';
                    case 'on':
                    case 'starting':
                    case 'stopping':
                        return 'online';
                    default:
                        return '';
                }
            },

            /**
             * Calculate the CPU usage for a given server relative to their set maximum.
             *
             * @param {Number} current
             * @param {Number} max
             * @return {Number}
             * @private
             */
            _calculateCpu: function (current, max) {
                if (max === 0) {
                    return parseFloat(current.toFixed(1));
                }

                return parseFloat((current / max * 100).toFixed(1));
            },

            /**
             * Handle document visibility changes.
             *
             * @private
             */
            _visibilityChange: function () {
                this.documentVisible = document.visibilityState === 'visible';

                if (!this.documentVisible) {
                    this.backgroundedAt = new Date();
                }
            },
        }
    };
</script>
