<template>
    <div class="server-box animate fadein">
        <router-link :to="{ name: 'server', params: { id: server.identifier }}" class="content">
            <div class="float-right">
                <div class="indicator" :class="status"></div>
            </div>
            <div class="mb-4">
                <div class="text-black font-bold text-xl">
                    {{ server.name }}
                </div>
            </div>
            <div class="mb-0 flex">
                <div class="usage">
                    <div class="indicator-title">{{ $t('dashboard.index.cpu_title') }}</div>
                </div>
                <div class="usage">
                    <div class="indicator-title">{{ $t('dashboard.index.memory_title') }}</div>
                </div>
            </div>
            <div class="mb-4 flex text-center">
                <div class="inline-block border border-grey-lighter border-l-0 p-4 flex-1">
                    <span class="font-bold text-xl">{{ cpu > 0 ? cpu : '&mdash;' }}</span>
                    <span class="font-light text-sm">%</span>
                </div>
                <div class="inline-block border border-grey-lighter border-l-0 border-r-0 p-4 flex-1">
                    <span class="font-bold text-xl">{{ memory > 0 ? memory : '&mdash;' }}</span>
                    <span class="font-light text-sm">MB</span>
                </div>
            </div>
            <div class="flex items-center">
                <div class="text-sm">
                    <p class="text-grey">{{ server.node }}</p>
                    <p class="text-grey-dark">{{ server.allocation.ip }}:{{ server.allocation.port }}</p>
                </div>
            </div>
        </router-link>
    </div>
</template>

<script>
    import get from 'lodash/get';

    export default {
        name: 'server-box',
        props: {
            server: { type: Object, required: true },
        },

        data: function () {
            return {
                resources: undefined,
                cpu: 0,
                memory: 0,
                status: '',
            };
        },

        created: function () {
            window.events.$on(`server:${this.server.uuid}::resources`, data => {
                this.resources = data;
                this.status = this.getServerStatus();

                this.memory = Number(get(data, 'memory.current', 0)).toFixed(0);
                this.cpu = this._calculateCpu(
                    Number(get(data, 'cpu.current', 0)),
                    Number(this.server.limits.cpu)
                );
            });
        },

        methods: {
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
            }
        }
    };
</script>
