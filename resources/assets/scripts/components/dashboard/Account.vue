<template>
    <div>
        <navigation/>
        <div class="container animate fadein mt-2 sm:mt-6">
            <modal :show="modalVisible" v-on:close="modalVisible = false">
                <TwoFactorAuthentication v-on:close="modalVisible = false"/>
            </modal>
            <flash container="mt-2 sm:mt-6 mb-2"/>
            <div class="flex flex-wrap">
                <div class="w-full md:w-1/2">
                    <div class="sm:m-4 md:ml-0">
                        <update-email class="mb-4 sm:mb-8"/>
                        <div class="content-box text-center mb-4 sm:mb-0">
                            <button class="btn btn-green btn-sm" type="submit" id="grid-open-two-factor-modal"
                                    v-on:click="openModal"
                            >Configure 2-Factor Authentication</button>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-1/2">
                    <change-password class="sm:m-4 md:mr-0"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import Navigation from '../core/Navigation';
    import Flash from '../Flash';
    import UpdateEmail from './account/UpdateEmail';
    import ChangePassword from './account/ChangePassword';
    import Modal from '../core/Modal';
    import TwoFactorAuthentication from './account/TwoFactorAuthentication';

    export default {
        name: 'account',
        components: {TwoFactorAuthentication, Modal, ChangePassword, UpdateEmail, Flash, Navigation},
        data: function () {
            return {
                modalVisible: false,
            };
        },
        methods: {
            openModal: function () {
                this.$data.modalVisible = true;
                window.events.$emit('two_factor:open');
            },
        }
    };
</script>
