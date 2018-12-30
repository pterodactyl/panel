import {ComponentOptions} from "vue";
import {Vue} from "vue/types/vue";
import {TranslateResult} from "vue-i18n";

export interface FlashInterface {
    flash(message: string | TranslateResult, title: string, severity: string): void;

    clear(): void,

    success(message: string | TranslateResult): void,

    info(message: string | TranslateResult): void,

    warning(message: string | TranslateResult): void,

    error(message: string | TranslateResult): void,
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
    computed: {
        '$flash': function () {
            return new Flash();
        }
    },
};
