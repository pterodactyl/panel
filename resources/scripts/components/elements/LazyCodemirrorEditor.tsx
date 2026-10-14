import { Suspense, use } from 'react';
import Spinner from '@/components/elements/Spinner';
import type * as EditorChunk from '@/components/elements/CodemirrorEditor';
import type { CodemirrorEditorHandle, Props as CodemirrorEditorProps } from '@/components/elements/CodemirrorEditor';
import { editorContainerClass } from '@/components/elements/codemirror/layout';
import { cn } from '@/lib/cn';

export type { CodemirrorEditorHandle };

type EditorModule = typeof EditorChunk;
/** React's `use` reads a promise's `status` and `value`, so a settled one renders without suspending. */
type EditorLoad = Promise<EditorModule> & { status?: 'fulfilled'; value?: EditorModule };

let editor: EditorLoad | undefined;

/** Loads the editor's chunk once; a render after it has loaded does not suspend. */
export function loadCodemirrorEditor(): Promise<EditorModule> {
    if (!editor) {
        const loading: EditorLoad = import('@/components/elements/CodemirrorEditor');

        loading.then(
            (module) => Object.assign(loading, { status: 'fulfilled', value: module }),
            () => {
                editor = undefined;
            }
        );
        editor = loading;
    }

    return editor;
}

function CodemirrorEditor(props: CodemirrorEditorProps) {
    const { default: Editor } = use(loadCodemirrorEditor());

    return <Editor {...props} />;
}

const LazyCodemirrorEditor = (props: CodemirrorEditorProps) => (
    <Suspense
        fallback={
            <div className={cn(editorContainerClass, props.className)}>
                <Spinner centered size='large' />
            </div>
        }
    >
        <CodemirrorEditor {...props} />
    </Suspense>
);

export default LazyCodemirrorEditor;
