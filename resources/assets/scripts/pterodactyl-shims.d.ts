import Vue from "vue";
import {Store} from "vuex";
import {FlashInterface} from "./mixins/flash";

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
        $flash: FlashInterface
    }
}
