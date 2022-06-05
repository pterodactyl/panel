import tw from 'twin.macro';
import { format } from 'date-fns';
import * as Icon from 'react-feather';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import React, { useEffect, useState } from 'react';
import deleteApiKey from '@/api/account/deleteApiKey';
import { Actions, useStoreActions } from 'easy-peasy';
import ContentBox from '@/components/elements/ContentBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import getApiKeys, { ApiKey } from '@/api/account/getApiKeys';
import FlashMessageRender from '@/components/FlashMessageRender';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import CreateApiKeyForm from '@/components/dashboard/forms/CreateApiKeyForm';

export default () => {
    const [ deleteIdentifier, setDeleteIdentifier ] = useState('');
    const [ keys, setKeys ] = useState<ApiKey[]>([]);
    const [ loading, setLoading ] = useState(true);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('account');
        getApiKeys()
            .then(keys => setKeys(keys))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
                addError({ key: 'account', message: httpErrorToHuman(error) });
            });
    }, []);

    const doDeletion = (identifier: string) => {
        setLoading(true);
        clearFlashes('account');
        deleteApiKey(identifier)
            .then(() => setKeys(s => ([
                ...(s || []).filter(key => key.identifier !== identifier),
            ])))
            .catch(error => {
                console.error(error);
                addError({ key: 'account', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    return (
        <PageContentBlock title={'Account API'}>
            <h1 css={tw`text-5xl`}>API Keys</h1>
            <h3 css={tw`text-2xl ml-4 text-neutral-500`}>Create API keys to interact with the Panel.</h3>
            <FlashMessageRender byKey={'account'}/>
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Create API Key'} css={tw`flex-none w-full md:w-1/2`}>
                    <CreateApiKeyForm onKeyCreated={key => setKeys(s => ([ ...s!, key ]))}/>
                </ContentBox>
                <ContentBox title={'API Keys'} css={tw`flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8`}>
                    <SpinnerOverlay visible={loading}/>
                    <ConfirmationModal
                        visible={!!deleteIdentifier}
                        title={'Confirm key deletion'}
                        buttonText={'Yes, delete key'}
                        onConfirmed={() => {
                            doDeletion(deleteIdentifier);
                            setDeleteIdentifier('');
                        }}
                        onModalDismissed={() => setDeleteIdentifier('')}
                    >
                        Are you sure you wish to delete this API key? All requests using it will immediately be
                        invalidated and will fail.
                    </ConfirmationModal>
                    {
                        keys.length === 0 ?
                            <p css={tw`text-center text-sm`}>
                                {loading ? 'Loading...' : 'No API keys exist for this account.'}
                            </p>
                            :
                            keys.map((key, index) => (
                                <GreyRowBox
                                    key={key.identifier}
                                    css={[ tw`bg-neutral-600 flex items-center`, index > 0 && tw`mt-2` ]}
                                >
                                    <Icon.Key css={tw`text-neutral-300`} />
                                    <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                        <p css={tw`text-sm break-words`}>{key.description}</p>
                                        <p css={tw`text-2xs text-neutral-300 uppercase`}>
                                            Last used:&nbsp;
                                            {key.lastUsedAt ? format(key.lastUsedAt, 'MMM do, yyyy HH:mm') : 'Never'}
                                        </p>
                                    </div>
                                    <p css={tw`text-sm ml-4 hidden md:block`}>
                                        <code css={tw`font-mono py-1 px-2 bg-neutral-900 rounded`}>
                                            {key.identifier}
                                        </code>
                                    </p>
                                    <button
                                        css={tw`ml-4 p-2 text-sm`}
                                        onClick={() => setDeleteIdentifier(key.identifier)}
                                    >
                                        <Icon.Trash css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`} />
                                    </button>
                                </GreyRowBox>
                            ))
                    }
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
