<template>
    <div class="context-menu">
        <div>
            <div class="context-row">
                <div class="icon">
                    <Icon name="edit-3"/>
                </div>
                <div class="action"><span>Rename</span></div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <Icon name="corner-up-left" class="h-4"/>
                </div>
                <div class="action"><span class="text-left">Move</span></div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <Icon name="copy" class="h-4"/>
                </div>
                <div class="action">Copy</div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <Icon name="download" class="h-4"/>
                </div>
                <div class="action">Download</div>
            </div>
        </div>
        <div>
            <div class="context-row">
                <div class="icon">
                    <Icon name="file-plus" class="h-4"/>
                </div>
                <div class="action">New File</div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <Icon name="folder-plus" class="h-4"/>
                </div>
                <div class="action">New Folder</div>
            </div>
        </div>
        <div>
            <div class="context-row danger">
                <div class="icon">
                    <Icon name="delete" class="h-4"/>
                </div>
                <div class="action">Delete</div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import Vue from 'vue';
    import Icon from "../../../core/Icon.vue";

    export default Vue.extend({
        name: 'FileContextMenu',
        components: {Icon},
    });
</script>
