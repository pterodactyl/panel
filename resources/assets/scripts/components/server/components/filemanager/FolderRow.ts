import Vue from 'vue';
import { formatDate } from "@/helpers";
import Icon from "@/components/core/Icon";

export default Vue.component('folder-row', {
    components: { Icon },

    props: {
        directory: {type: Object, required: true},
    },

    data: function () {
        return {
            currentDirectory: this.$route.params.path || '/',
        };
    },

    methods: {
        /**
         * Return a formatted directory path that is used to switch to a nested directory.
         */
        getClickablePath (directory: string): string {
            return `${this.currentDirectory.replace(/\/$/, '')}/${directory}`;
        },

        formatDate: formatDate,
    },

    template: `
        <div>
            <router-link class="row clickable"
                         :to="{ name: 'server-files', params: { path: getClickablePath(directory.name).replace(/^\\//, '') }}"
            >
                <div class="flex-none icon">
                    <icon name="folder"/>
                </div>
                <div class="flex-1">{{directory.name}}</div>
                <div class="flex-1 text-right text-grey-dark"></div>
                <div class="flex-1 text-right text-grey-dark">{{formatDate(directory.modified)}}</div>
                <div class="flex-none w-1/6"></div>
            </router-link>
        </div>
    `
});
