<template>
    <div class="content-box mb-6 hover:border-neutral-200">
        <div class="flex items-center text-neutral-800">
            <Icon name="database" class="flex-none text-green-500"></icon>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-neutral-500 pb-1 select-none">Database Name</p>
                <p>{{database.name}}</p>
            </div>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-neutral-500 pb-1 select-none">Username</p>
                <p>{{database.username}}</p>
            </div>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-neutral-500 pb-1 select-none">Password</p>
                <p>
                    <code class="text-sm cursor-pointer" v-on:click="revealPassword">
                            <span class="select-none" v-if="!database.showPassword">
                                <Icon name="lock" class="h-3"/> &bull;&bull;&bull;&bull;&bull;&bull;
                            </span>
                        <span v-else>{{database.password}}</span>
                    </code>
                </p>
            </div>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-neutral-500 pb-1 select-none">Server</p>
                <p><code class="text-sm">{{database.host.address}}:{{database.host.port}}</code></p>
            </div>
            <div class="flex-none px-4">
                <button class="btn btn-xs btn-secondary btn-red" v-on:click="showDeleteModal = true">
                    <Icon name="trash-2" class="w-3 h-3 mx-1"/>
                </button>
            </div>
        </div>
        <DeleteDatabaseModal
                :database="database"
                :show="showDeleteModal"
                v-on:close="showDeleteModal = false"
        />
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "@/components/core/Icon.vue";
    import {ServerDatabase} from "@/api/server/types";
    import DeleteDatabaseModal from "@/components/server/components/database/DeleteDatabaseModal.vue";

    export default Vue.extend({
        name: 'DatabaseRow',
        components: {DeleteDatabaseModal, Icon},
        props: {
            database: {
                type: Object as () => ServerDatabase,
                required: true,
            }
        },

        data: function () {
            return {
                showDeleteModal: false,
            };
        },

        methods: {
            revealPassword: function () {
                this.database.showPassword = !this.database.showPassword;
            },
        },
    })
</script>
