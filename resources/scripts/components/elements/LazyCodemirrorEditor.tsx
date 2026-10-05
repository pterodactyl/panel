import React, { Suspense } from 'react';
import Spinner from '@/components/elements/Spinner';
import type { CodemirrorEditorHandle, Props as CodemirrorEditorProps } from '@/components/elements/CodemirrorEditor';
import { editorContainerClass } from '@/components/elements/codemirror/layout';
import { cn } from '@/lib/cn';

export type { CodemirrorEditorHandle };

const CodemirrorEditor = React.lazy(() => import('@/components/elements/CodemirrorEditor'));

const LazyCodemirrorEditor = (props: CodemirrorEditorProps) => (
    <Suspense
        fallback={
            <div className={cn(editorContainerClass, props.className)}>
                <Spinner centered size={'large'} />
            </div>
        }
    >
        <CodemirrorEditor {...props} />
    </Suspense>
);

export default LazyCodemirrorEditor;
