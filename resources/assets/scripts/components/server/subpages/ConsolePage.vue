<template>
    <div>
        <div class="text-xs font-mono">
            <div class="rounded-t p-2 bg-black text-grey-lightest overflow-scroll" style="min-height: 16rem;max-height:32rem;">
                <span class="block" v-for="line in console">{{line}}</span>
            </div>
            <div class="rounded-b p-2 bg-grey-darkest text-white">$</div>
        </div>
    </div>
</template>

<script>
    import {mapState} from 'vuex';

    export default {
        name: 'console-page',

        computed: {
            ...mapState('server', ['console']),
        },

        data: function () {
            return {
                lines: [],
            };
        }
    };
</script>
