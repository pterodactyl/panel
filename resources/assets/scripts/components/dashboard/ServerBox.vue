<template>
    <div class="server-box animate fadein">
        <router-link :to="{ name: 'server', params: { id: server.identifier }}" class="content">
            <div class="float-right">
                <div class="indicator"></div>
            </div>
            <div class="mb-4">
                <div class="text-black font-bold text-xl">
                    {{ server.name }}
                </div>
            </div>
            <div class="mb-0 flex">
                <div class="usage">
                    <div class="indicator-title">{{ $t('dashboard.index.cpu_title') }}</div>
                </div>
                <div class="usage">
                    <div class="indicator-title">{{ $t('dashboard.index.memory_title') }}</div>
                </div>
            </div>
            <div class="mb-4 flex text-center">
                <div class="inline-block border border-grey-lighter border-l-0 p-4 flex-1">
                    <span class="font-bold text-xl">{{ resources.cpu > 0 ? resources.cpu : '&mdash;' }}</span>
                    <span class="font-light text-sm">%</span>
                </div>
                <div class="inline-block border border-grey-lighter border-l-0 border-r-0 p-4 flex-1">
                    <span class="font-bold text-xl">{{ resources.memory > 0 ? resources.memory : '&mdash;' }}</span>
                    <span class="font-light text-sm">Mb</span>
                </div>
            </div>
            <div class="flex items-center">
                <div class="text-sm">
                    <p class="text-grey">{{ server.node }}</p>
                    <p class="text-grey-dark">{{ server.allocation.ip }}:{{ server.allocation.port }}</p>
                </div>
            </div>
        </router-link>
    </div>
</template>

<script>
    import { Server } from '../../models/server';

    export default {
        name: 'server-box',
        props: {
            server: { type: Server, required: true },
            resources: { type: Object, required: true },
        }
    };
</script>
