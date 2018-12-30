import {ComponentOptions} from "vue";
import {Vue} from "vue/types/vue";

export interface FlashInterface {
    flash(message: string, title: string, severity: string): void;

    clear(): void,

    success(message: string): void,

    info(message: string): void,

    warning(message: string): void,

    error(message: string): void,
}

class Flash implements FlashInterface {
    flash(message: string, title: string, severity: string = 'info'): void {
        severity = severity || 'info';
        if (['danger', 'fatal', 'error'].includes(severity)) {
            severity = 'error';
        }

        // @ts-ignore
        window.events.$emit('flash', {message, title, severity});
    }

    clear(): void {
        // @ts-ignore
        window.events.$emit('clear-flashes');
    }

    success(message: string): void {
        this.flash(message, 'Success', 'success');
    }

    info(message: string): void {
        this.flash(message, 'Info', 'info');
    }

    warning(message: string): void {
        this.flash(message, 'Warning', 'warning');
    }

    error(message: string): void {
        this.flash(message, 'Error', 'error');
    }
}

export const FlashMixin: ComponentOptions<Vue> = {
    methods: {
        '$flash': function () {
            return new Flash();
        }
    },
};
