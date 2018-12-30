import Vue from 'vue';
import Icon from "../../../core/Icon";

export default Vue.component('file-context-menu', {
    components: {
        Icon,
    },

    template: `
        <div class="context-menu">
            <div>
                <div class="context-row">
                    <div class="icon">
                        <icon name="edit3"/>
                    </div>
                    <div class="action"><span>Rename</span></div>
                </div>
                <div class="context-row">
                    <div class="icon">
                        <icon name="corner-up-left" class="h-4"/>
                    </div>
                    <div class="action"><span class="text-left">Move</span></div>
                </div>
                <div class="context-row">
                    <div class="icon">
                        <icon name="copy" class="h-4"/>
                    </div>
                    <div class="action">Copy</div>
                </div>
            </div>
            <div>
                <div class="context-row">
                    <div class="icon">
                        <icon name="file-plus" class="h-4"/>
                    </div>
                    <div class="action">New File</div>
                </div>
                <div class="context-row">
                    <div class="icon">
                        <icon name="folder-plus" class="h-4"/>
                    </div>
                    <div class="action">New Folder</div>
                </div>
            </div>
            <div>
                <div class="context-row">
                    <div class="icon">
                        <icon name="download" class="h-4"/>
                    </div>
                    <div class="action">Download</div>
                </div>
                <div class="context-row danger">
                    <div class="icon">
                        <icon name="delete" class="h-4"/>
                    </div>
                    <div class="action">Delete</div>
                </div>
            </div>
        </div>
    `,
})
