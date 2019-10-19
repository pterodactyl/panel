import React, { lazy } from 'react';
import { ServerContext } from '@/state/server';

const LazyAceEditor = lazy(() => import(/* webpackChunkName: "editor" */'@/components/elements/AceEditor'));

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    let ref: null| (() => Promise<string>) = null;

    setTimeout(() => ref && ref().then(console.log), 5000);

    return (
        <div className={'my-10'}>
            <LazyAceEditor
                fetchContent={value => {
                    ref = value;
                }}
                onContentSaved={() => null}
            />
        </div>
    );
};
