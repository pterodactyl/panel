import Vue from 'vue';
import { mapState } from 'vuex';
import Status from '../../../helpers/statuses';

export default Vue.component('power-buttons', {
    computed: {
        ...mapState('socket', ['connected', 'status']),
    },

    data: function () {
        return {
            statuses: Status,
        };
    },

    methods: {
        sendPowerAction: function (action: string) {
            // this.$socket().instance().emit('set status', action)
        },
    },

    template: `
        <div>
            <div v-if="connected">
                <transition name="slide-fade" mode="out-in">
                    <button class="btn btn-green uppercase text-xs px-4 py-2"
                            v-if="status === statuses.STATUS_OFF"
                            v-on:click.prevent="sendPowerAction('start')"
                    >Start</button>
                    <div v-else>
                        <button class="btn btn-red-500 uppercase text-xs px-4 py-2" v-on:click.prevent="sendPowerAction('stop')">Stop</button>
                        <button class="btn btn-secondary uppercase text-xs px-4 py-2" v-on:click.prevent="sendPowerAction('restart')">Restart</button>
                        <button class="btn btn-secondary uppercase text-xs px-4 py-2" v-on:click.prevent="sendPowerAction('kill')">Kill</button>
                    </div>
                </transition>
            </div>
            <div v-else>
                <div class="text-center">
                    <div class="spinner"></div>
                    <div class="pt-2 text-xs text-neutral-400">Connecting to node</div>
                </div>
            </div>
        </div>
    `
});
