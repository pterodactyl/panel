export const flash = {
    methods: {
        /**
         * Flash a message to the event stream in the browser.
         */
        flash: function (message: string, title: string, severity: string = 'info'): void {
            severity = severity || 'info';
            if (['danger', 'fatal', 'error'].includes(severity)) {
                severity = 'error';
            }

            // @ts-ignore
            window.events.$emit('flash', { message, title, severity });
        },

        /**
         * Clear all of the flash messages from the screen.
         */
        clearFlashes: function (): void {
            // @ts-ignore
            window.events.$emit('clear-flashes');
        },

        /**
         * Helper function to flash a normal success message to the user.
         */
        success: function (message: string): void {
            this.flash(message, 'Success', 'success');
        },

        /**
         * Helper function to flash a normal info message to the user.
         */
        info: function (message: string): void {
            this.flash(message, 'Info', 'info');
        },

        /**
         * Helper function to flash a normal warning message to the user.
         */
        warning: function (message: string): void {
            this.flash(message, 'Warning', 'warning');
        },

        /**
         * Helper function to flash a normal error message to the user.
         */
        error: function (message: string): void {
            this.flash(message, 'Error', 'danger');
        },
    }
};
