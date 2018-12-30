<template>
    <div class="content-box mb-6 hover:border-grey">
        <div class="flex items-center text-grey-darker">
            <database-icon class="flex-none text-green"></database-icon>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-grey pb-1 select-none">Database Name</p>
                <p>{{database.name}}</p>
            </div>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-grey pb-1 select-none">Username</p>
                <p>{{database.username}}</p>
            </div>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-grey pb-1 select-none">Password</p>
                <p>
                    <code class="text-sm cursor-pointer" v-on:click="revealPassword">
                        <span class="select-none" v-if="!database.showPassword">
                            <lock-icon class="h-3"/> &bull;&bull;&bull;&bull;&bull;&bull;
                        </span>
                        <span v-else>{{database.password}}</span>
                    </code>
                </p>
            </div>
            <div class="flex-1 px-4">
                <p class="uppercase text-xs text-grey pb-1 select-none">Server</p>
                <p><code class="text-sm">{{database.host.address}}:{{database.host.port}}</code></p>
            </div>
            <div class="flex-none px-4">
                <button class="btn btn-xs btn-secondary btn-red" v-on:click="showDeleteModal = true">
                    <trash2-icon class="w-3 h-3 mx-1"/>
                </button>
            </div>
        </div>
        <modal :show="showDeleteModal" v-on:close="showDeleteModal = false">
            <delete-database-modal
                    :database="database"
                    v-on:close="showDeleteModal = false"
                    v-if="showDeleteModal"
            />
        </modal>
    </div>
</template>

<script lang="ts">
    import { LockIcon, Trash2Icon, DatabaseIcon } from 'vue-feather-icons';
    import Modal from '../../../core/Modal';
    import DeleteDatabaseModal from './DeleteDatabaseModal';

    export default {
        name: 'database-row',
        components: {DeleteDatabaseModal, Modal, LockIcon, Trash2Icon, DatabaseIcon},
        props: {
            database: {type: Object, required: true}
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
    };
</script>
