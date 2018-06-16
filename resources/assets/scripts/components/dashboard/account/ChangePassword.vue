<template>
    <div>
        <form action="" method="post">
            <div class="content-box">
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
</template>

<script>
    export default {
        name: 'change-password'
    };
</script>
