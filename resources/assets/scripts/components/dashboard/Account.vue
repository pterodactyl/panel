<template>
    <div>
        <navigation/>
        <div class="container animate fadein mt-6">
            <modal :show="modalVisible" v-on:close="modalVisible = false">
                <TwoFactorAuthentication/>
            </modal>
            <flash container="mt-6 mb-2 mx-4"/>
            <div class="flex">
                <div class="flex-1 m-4 ml-0">
                    <update-email class="mb-8"/>
                    <div class="bg-white p-6 border border-grey-light rounded rounded-1 text-center">
                        <button class="btn btn-green btn-sm" type="submit" v-on:click="modalVisible = true">Configure 2-Factor Authentication</button>
                    </div>
                </div>
                <change-password class="flex-1 m-4 mr-0"/>
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
    };
</script>
