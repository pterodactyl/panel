<template>
    <div>
        <Navigation/>
        <div class="container animate fadein mt-2 sm:mt-6">
            <Modal :show="modalVisible" v-on:close="modalVisible = false">
                <TwoFactorAuthentication v-on:close="modalVisible = false"/>
            </Modal>
            <Flash container="mt-2 sm:mt-6 mb-2"/>
            <div class="flex flex-wrap">
                <div class="w-full md:w-1/2">
                    <div class="sm:m-4 md:ml-0">
                        <UpdateEmail class="mb-4 sm:mb-8"/>
                        <div class="content-box text-center mb-4 sm:mb-0">
                            <button class="btn btn-green btn-sm" type="submit" id="grid-open-two-factor-modal"
                                    v-on:click="openModal"
                            >Configure 2-Factor Authentication
                            </button>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-1/2">
                    <ChangePassword class="sm:m-4 md:mr-0"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Navigation from "../core/Navigation.vue";
    import Flash from "@/components/Flash.vue";
    import UpdateEmail from "./account/UpdateEmail.vue";
    import ChangePassword from "./account/ChangePassword.vue";
    import TwoFactorAuthentication from "./account/TwoFactorAuthentication.vue";
    import Modal from "../core/Modal.vue";

    export default Vue.extend({
        name: 'Account',
        components: {
            TwoFactorAuthentication,
            Modal,
            ChangePassword,
            UpdateEmail,
            Flash,
            Navigation
        },

        data: function () {
            return {
                modalVisible: false,
            };
        },

        methods: {
            openModal: function () {
                this.modalVisible = true;
                window.events.$emit('two_factor:open');
            },
        },
    });
</script>
