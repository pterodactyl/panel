<template>
    <div>
        <div class="text-xs font-mono">
            <div class="rounded-t p-2 bg-black text-grey-lightest h-48">

            </div>
            <div class="rounded-b p-2 bg-grey-darkest text-white">$</div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'console-page',

        data: function () {
            return {
                lines: [],
            };
        }
    };
</script>
