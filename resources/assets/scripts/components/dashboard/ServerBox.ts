import Vue from 'vue';
import { get } from 'lodash';
import { differenceInSeconds } from 'date-fns';
import {AxiosError, AxiosResponse} from "axios";

type DataStructure = {
    backgroundedAt: Date,
    documentVisible: boolean,
    resources: null | { [s: string]: any },
    cpu: number,
    memory: number,
    status: string,
    link: { name: string, params: { id: string } },
    dataGetTimeout: undefined | number,
}

export default Vue.component('server-box', {
    props: {
        server: { type: Object, required: true },
    },

    data: function (): DataStructure {
        return {
            backgroundedAt: new Date(),
            documentVisible: true,
            resources: null,
            cpu: 0,
            memory: 0,
            status: '',
            link: { name: 'server', params: { id: this.server.identifier }},
            dataGetTimeout: undefined,
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
                window.clearTimeout(this.dataGetTimeout);
                return;
            }

            if (differenceInSeconds(new Date(), this.backgroundedAt) >= 30) {
                this.getResourceUse();
            }

            this.dataGetTimeout = window.setInterval(() => {
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
        this.dataGetTimeout = window.setInterval(() => {
            this.getResourceUse();
        }, 10000);
    },

    /**
     * Clear the timer and event listeners when we destroy the component.
     */
    beforeDestroy: function () {
        window.clearInterval(this.$data.dataGetTimeout);
        document.removeEventListener('visibilitychange', this._visibilityChange.bind(this), false);
    },

    methods: {
        /**
         * Query the resource API to determine what this server's state and resource usage is.
         */
        getResourceUse: function () {
            window.axios.get(this.route('api.client.servers.resources', { server: this.server.identifier }))
                .then((response: AxiosResponse) => {
                    if (!(response.data instanceof Object)) {
                        throw new Error('Received an invalid response object back from status endpoint.');
                    }

                    this.resources = response.data.attributes;
                    this.status = this.getServerStatus();
                    this.memory = parseInt(parseFloat(get(this.resources, 'memory.current', '0')).toFixed(0));
                    this.cpu = this._calculateCpu(
                        parseFloat(get(this.resources, 'cpu.current', '0')),
                        parseFloat(this.server.limits.cpu)
                    );
                })
                .catch((err: AxiosError) => console.warn('Error fetching server resource usage', { ...err }));
        },

        /**
         * Set the CSS to use for displaying the server's current status.
         */
        getServerStatus: function () {
            if (!this.resources || !this.resources.installed || this.resources.suspended) {
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
         * @private
         */
        _calculateCpu: function (current: number, max: number) {
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
    },

    template: `
        <div class="server-card-container">
            <div class="server-card animated-fade-in hover:shadow-md">
                <div class="content h-32 relative">
                    <router-link :to="link">
                        <h2 class="text-xl flex flex-row items-center mb-2">
                            <div class="identifier-icon select-none" :class="{
                            'bg-grey-500': status === '',
                            'bg-red-500': status === 'offline',
                            'bg-green-500': status === 'online'
                        }">
                                {{ server.name[0] }}
                            </div>
                            {{ server.name }}
                        </h2>
                    </router-link>
                    <div class="text-neutral-800 font-normal text-sm">
                        <p v-if="server.description.length" class="pb-1">{{ server.description }}</p>
    
                        <div class="absolute pin-b pin-l p-4 w-full">
                            <span class="font-semibold text-indigo">{{ server.node }}</span>
                            <span class="float-right text-neutral-600 font-light">{{ server.allocation.ip }}:{{ server.allocation.port }}</span>
                        </div>
                    </div>
                </div>
                <div class="footer p-4 text-sm">
                    <div class="inline-block pr-2">
                        <div class="pillbox bg-green-500"><span class="select-none">MEM:</span> {{ memory }} Mb</div>
                    </div>
                    <div class="inline-block">
                        <div class="pillbox bg-primary-500"><span class="select-none">CPU:</span> {{ cpu }} %</div>
                    </div>
                </div>
            </div>
        </div>
    `
});
