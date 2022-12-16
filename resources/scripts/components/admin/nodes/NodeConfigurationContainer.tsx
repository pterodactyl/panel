import { faCode, faDragon } from '@fortawesome/free-solid-svg-icons';
import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import { useEffect, useState } from 'react';
import tw from 'twin.macro';

import getNodeConfiguration from '@/api/admin/nodes/getNodeConfiguration';
import AdminBox from '@/components/admin/AdminBox';
import { Context } from '@/components/admin/nodes/NodeRouter';
import CopyOnClick from '@/components/elements/CopyOnClick';
import type { ApplicationStore } from '@/state';

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const [configuration, setConfiguration] = useState('');

    const node = Context.useStoreState(state => state.node);

    if (node === undefined) {
        return <></>;
    }

    useEffect(() => {
        clearFlashes('node');

        getNodeConfiguration(node.id)
            .then(configuration => setConfiguration(configuration))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'node', error });
            });
    }, []);

    return (
        <>
            <AdminBox title={'Configuration'} icon={faCode} css={tw`mb-4`}>
                <div css={tw`relative`}>
                    <div css={tw`absolute top-0 right-0`}>
                        <CopyOnClick text={configuration} showInNotification={false}>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                css={tw`h-5 w-5 text-neutral-500 hover:text-neutral-400 cursor-pointer mt-1 mr-1`}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                />
                            </svg>
                        </CopyOnClick>
                    </div>
                    <pre css={tw`text-sm rounded font-mono bg-neutral-900 shadow-md px-4 py-3 overflow-x-auto`}>
                        {configuration}
                    </pre>
                </div>
            </AdminBox>

            <AdminBox title={'Auto Deploy'} icon={faDragon}>
                Never&trade;
            </AdminBox>
        </>
    );
};
