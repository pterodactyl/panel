import Vue from 'vue';
import { replace } from 'feather-icons';

export default Vue.component('icon', {
    props: {
        name: {type: String, default: 'circle'},
    },
    mounted: function () {
        replace();
    },
    template: `
        <i :data-feather="name"></i>
    `,
});
