<template>
    <div v-if="notifications.length > 0">
        <transition-group tag="div" class="mb-2" name="fade">
            <div :class="item.class" class="lg:inline-flex mb-2" role="alert" :key="index" v-for="(item, index) in notifications">
                <span class="title" v-html="item.title" v-if="item.title.length > 0"></span>
                <span class="message" v-html="item.message"></span>
            </div>
        </transition-group>
    </div>
</template>

<script>
    export default {
        name: 'flash',
        props: {
            timeout: {
                type: Number,
                default: 0,
            },
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

        data: function () {
            return {
                notifications: [],
            };
        },

        /**
         * Listen for flash events.
         */
        created: function () {
            const self = this;
            window.events.$on('flash', function (data) {
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
             *
             * @param {string} message
             * @param {string} title
             * @param {string} severity
             */
            flash: function (message, title, severity) {
                this.notifications.push({
                    message, severity, title, class: this.types[severity] || this.types.base,
                });

                if (this.timeout > 0) {
                    setTimeout(this.hide, this.timeout);
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
             *
             * @param {int} item
             */
            hide: function (item = this.notifications[0]) {
                let key = this.notifications.indexOf(item);
                this.notifications.splice(key, 1);
            },
        }
    };
</script>
