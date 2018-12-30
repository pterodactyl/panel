<template>
    <div class="context-menu">
        <div>
            <div class="context-row">
                <div class="icon">
                    <edit3-icon/>
                </div>
                <div class="action"><span>Rename</span></div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <corner-up-left-icon class="h-4"/>
                </div>
                <div class="action"><span class="text-left">Move</span></div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <copy-icon class="h-4"/>
                </div>
                <div class="action">Copy</div>
            </div>
        </div>
        <div>
            <div class="context-row">
                <div class="icon">
                    <file-plus-icon class="h-4"/>
                </div>
                <div class="action">New File</div>
            </div>
            <div class="context-row">
                <div class="icon">
                    <folder-plus-icon class="h-4"/>
                </div>
                <div class="action">New Folder</div>
            </div>
        </div>
        <div>
            <div class="context-row">
                <div class="icon">
                    <download-icon class="h-4"/>
                </div>
                <div class="action">Download</div>
            </div>
            <div class="context-row danger">
                <div class="icon">
                    <delete-icon class="h-4"/>
                </div>
                <div class="action">Delete</div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import { CopyIcon, CornerUpLeftIcon, DeleteIcon, DownloadIcon, Edit3Icon, FilePlusIcon, FolderPlusIcon } from 'vue-feather-icons';

    export default {
        name: 'file-manager-context-menu',
        components: {
            CopyIcon, CornerUpLeftIcon, DeleteIcon, DownloadIcon, Edit3Icon, FilePlusIcon, FolderPlusIcon,
        },
    };
</script>
