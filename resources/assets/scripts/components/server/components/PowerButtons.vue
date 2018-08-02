<template>
    <div>
        <div v-if="connected">
            <transition name="slide-fade" mode="out-in">
                <button class="btn btn-green uppercase text-xs px-4 py-2"
                        v-if="status === statuses.STATUS_OFF"
                        v-on:click.prevent="sendPowerAction('start')"
                >Start</button>
                <div v-else>
                    <button class="btn btn-red uppercase text-xs px-4 py-2" v-on:click.prevent="sendPowerAction('stop')">Stop</button>
                    <button class="btn btn-secondary uppercase text-xs px-4 py-2" v-on:click.prevent="sendPowerAction('restart')">Restart</button>
                    <button class="btn btn-secondary uppercase text-xs px-4 py-2" v-on:click.prevent="sendPowerAction('kill')">Kill</button>
                </div>
            </transition>
        </div>
        <div v-else>
            <div class="text-center">
                <div class="spinner"></div>
                <div class="pt-2 text-xs text-grey-light">Connecting to node</div>
            </div>
        </div>
    </div>
</template>

<script>
    import Status from './../../../helpers/statuses';
    import { mapState } from 'vuex';

    export default {
        name: 'power-buttons',

        computed: {
            ...mapState('socket', ['connected', 'status']),
        },

        data: function () {
            return {
                statuses: Status,
            };
        },

        methods: {
            sendPowerAction: function (action) {
                this.$socket.emit('set status', action)
            },
        },
    };
</script>

<style scoped>
    .slide-fade-enter-active {
        transition: all 250ms ease;
    }
    .slide-fade-leave-active {
        transition: all 250ms cubic-bezier(1.0, 0.5, 0.8, 1.0);
    }
    .slide-fade-enter, .slide-fade-leave-to {
        transform: translateX(10px);
        opacity: 0;
    }
</style>
