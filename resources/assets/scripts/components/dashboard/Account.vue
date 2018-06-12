<template>
    <div>
        <navigation/>
        <div class="container animate fadein mt-6">
            <flash container="mt-6 mb-2 mx-4"/>
            <div class="flex">
                <update-email class="flex-1 m-4"/>
                <div class="flex-1 m-4">
                    <form action="" method="post">
                        <div class="bg-white p-6 border border-grey-light rounded rounded-1">
                            <h2 class="mb-6 text-grey-darkest font-medium">Change your password</h2>
                            <div class="mt-6">
                                <label for="grid-password-current" class="input-label">Current password</label>
                                <input id="grid-password-current" name="password" type="password" class="input" required>
                            </div>
                            <div class="mt-6">
                                <label for="grid-password-new" class="input-label">New password</label>
                                <input id="grid-password-new" name="password" type="password" class="input" required>
                                <p class="input-help">Your new password should be at least 8 characters in length, contain one number, and be mixed case.</p>
                            </div>
                            <div class="mt-6">
                                <label for="grid-password-new-confirm" class="input-label">Confirm new password</label>
                                <input id="grid-password-new-confirm" name="password_confirmation" type="password" class="input" required>
                            </div>
                            <div class="mt-6 text-right">
                                <button class="btn btn-blue btn-sm text-right" type="submit">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import Navigation from '../core/Navigation';
    import Flash from '../Flash';
    import { mapState } from 'vuex';
    import UpdateEmail from './account/UpdateEmail';

    export default {
        name: 'account',
        components: {UpdateEmail, Flash, Navigation},
        computed: {
            ...mapState({
                user: state => state.auth.user,
            })
        },
    };
</script>
