import Vue from 'vue';

export default Vue.component('csrf', {
    data: function () {
        return {
            X_CSRF_TOKEN: window.X_CSRF_TOKEN,
        };
    },

    template: `<input type="hidden" name="_token" v-bind:value="X_CSRF_TOKEN" />`,
});
