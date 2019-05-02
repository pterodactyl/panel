<template>
    <div v-if="notifications.length > 0" :class="this.container">
        <transition-group tag="div" name="fade">
            <div v-for="(item, index) in notifications" :key="item.title">
                <MessageBox
                        :class="[item.class, {'mb-2': index < notifications.length - 1}]"
                        :title="item.title"
                        :message="item.message"
                />
            </div>
        </transition-group>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import MessageBox from './MessageBox.vue';

    type DataStructure = {
        notifications: Array<{
            message: string,
            severity: string,
            title: string,
            class: string,
        }>,
    }

    export default Vue.extend({
        name: 'Flash',
        components: {
            MessageBox
        },
        props: {
            container: {type: String, default: ''},
            timeout: {type: Number, default: 0},
            types: {
                type: Object,
                default: function () {
                    return {
                        base: 'alert',
                        success: 'alert success',
                        info: 'alert info',
                        warning: 'alert warning',
                        error: 'alert error',
                    }
                }
            }
        },

        data: function (): DataStructure {
            return {
                notifications: [],
            };
        },

        /**
         * Listen for flash events.
         */
        created: function () {
            const self = this;
            window.events.$on('flash', function (data: any) {
                self.flash(data.message, data.title, data.severity);
            });

            window.events.$on('clear-flashes', function () {
                self.clear();
            });
        },

        methods: {
            /**
             * Flash a message to the screen when a flash event is emitted over
             * the global event stream.
             */
            flash: function (message: string, title: string, severity: string) {
                this.notifications.push({
                    message, severity, title, class: this.$props.types[severity] || this.$props.types.base,
                });

                if (this.$props.timeout > 0) {
                    setTimeout(this.hide, this.$props.timeout);
                }
            },

            /**
             * Clear all of the flash messages from the screen.
             */
            clear: function () {
                this.notifications = [];
                window.events.$emit('flashes-cleared');
            },

            /**
             * Hide a notification after a given amount of time.
             */
            hide: function (item?: number) {
                // @ts-ignore
                let key = this.notifications.indexOf(item || this.notifications[0]);
                this.notifications.splice(key, 1);
            },
        },
    });
</script>
