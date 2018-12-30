import Vue from "vue";
import {Store} from "vuex";
import {FlashInterface} from "./mixins/flash";
import {AxiosInstance} from "axios";
import {Vue as VueType} from "vue/types/vue";

declare global {
    interface Window {
        X_CSRF_TOKEN: string,
        _: any,
        $: any,
        jQuery: any,
        axios: AxiosInstance,
        events: VueType,
    }
}

declare module 'vue/types/options' {
    interface ComponentOptions<V extends Vue> {
        $store?: Store<any>,
        $options?: {
            sockets?: {
                [s: string]: (data: any) => void,
            }
        },
    }
}

declare module 'vue/types/vue' {
    interface Vue {
        $store: Store<any>,
        $flash: FlashInterface,
        route: (name: string, params?: object, absolute?: boolean) => string,
    }
}
