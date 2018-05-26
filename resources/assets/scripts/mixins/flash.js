export const flash = {
    methods: {
        /**
         * Flash a message to the event stream in the browser.
         *
         * @param {string} message
         * @param {string} title
         * @param {string} severity
         */
        flash: function (message, title, severity = 'info') {
            severity = severity || 'info';
            if (['danger', 'fatal', 'error'].includes(severity)) {
                severity = 'error';
            }

            window.events.$emit('flash', { message, title, severity });
        },

        /**
         * Clear all of the flash messages from the screen.
         */
        clearFlashes: function () {
            window.events.$emit('clear-flashes');
        },

        /**
         * Helper function to flash a normal success message to the user.
         *
         * @param {string} message
         */
        success: function (message) {
            this.flash(message, 'Success', 'success');
        },

        /**
         * Helper function to flash a normal info message to the user.
         *
         * @param {string} message
         */
        info: function (message) {
            this.flash(message, 'Info', 'info');
        },

        /**
         * Helper function to flash a normal warning message to the user.
         *
         * @param {string} message
         */
        warning: function (message) {
            this.flash(message, 'Warning', 'warning');
        },

        /**
         * Helper function to flash a normal error message to the user.
         *
         * @param {string} message
         */
        error: function (message) {
            this.flash(message, 'Error', 'danger');
        },
    }
};
