import getNodeConfiguration from '@/api/admin/nodes/getNodeConfiguration';
import { Context } from '@/components/admin/nodes/NodeEditContainer';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import React, { useEffect, useState } from 'react';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { faCode, faDragon } from '@fortawesome/free-solid-svg-icons';

export default () => {
    const { clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const [ configuration, setConfiguration ] = useState('');

    const node = Context.useStoreState(state => state.node);

    if (node === undefined) {
        return (
            <></>
        );
    }

    useEffect(() => {
        getNodeConfiguration(node.id)
            .then((configuration) => {
                console.log(configuration);
                setConfiguration(configuration);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'node', error });
            });
    }, []);

    return (
        <div>
            <AdminBox title={'Configuration'} icon={faCode} css={tw`mb-4`}>
                <pre css={tw`text-sm rounded font-mono bg-neutral-900 shadow-md p-4 overflow-x-auto`}>
                    {configuration}
                </pre>
            </AdminBox>

            <AdminBox title={'Auto Deploy'} icon={faDragon}>
                Never&trade;
            </AdminBox>
        </div>
    );
};
