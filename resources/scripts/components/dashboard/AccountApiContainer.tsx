import React, { useEffect, useState } from 'react';
import ContentBox from '@/components/elements/ContentBox';
import CreateApiKeyForm from '@/components/dashboard/forms/CreateApiKeyForm';
import getApiKeys, { ApiKey } from '@/api/account/getApiKeys';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey } from '@fortawesome/free-solid-svg-icons/faKey';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import deleteApiKey from '@/api/account/deleteApiKey';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import FlashMessageRender from '@/components/FlashMessageRender';
import { httpErrorToHuman } from '@/api/http';
import format from 'date-fns/format';

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
        <div className={'my-10 flex'}>
            <FlashMessageRender byKey={'account'} className={'mb-4'}/>
            <ContentBox title={'Create API Key'} className={'flex-1'}>
                <CreateApiKeyForm onKeyCreated={key => setKeys(s => ([...s!, key]))}/>
            </ContentBox>
            <ContentBox title={'API Keys'} className={'ml-10 flex-1'}>
                <SpinnerOverlay visible={loading}/>
                {deleteIdentifier &&
                <ConfirmationModal
                    title={'Confirm key deletion'}
                    buttonText={'Yes, delete key'}
                    visible={true}
                    onConfirmed={() => {
                        doDeletion(deleteIdentifier);
                        setDeleteIdentifier('');
                    }}
                    onDismissed={() => setDeleteIdentifier('')}
                >
                    Are you sure you wish to delete this API key? All requests using it will immediately be
                    invalidated and will fail.
                </ConfirmationModal>
                }
                {
                    keys.length === 0 ?
                        <p className={'text-center text-sm'}>
                            {loading ? 'Loading...' : 'No API keys exist for this account.'}
                        </p>
                        :
                        keys.map(key => (
                            <div key={key.identifier} className={'grey-row-box bg-neutral-600 mb-2 flex items-center'}>
                                <FontAwesomeIcon icon={faKey} className={'text-neutral-300'}/>
                                <div className={'ml-4 flex-1'}>
                                    <p className={'text-sm'}>{key.description}</p>
                                    <p className={'text-2xs text-neutral-300 uppercase'}>
                                        Last
                                        used: {key.lastUsedAt ? format(key.lastUsedAt, 'MMM Do, YYYY HH:mm') : 'Never'}
                                    </p>
                                </div>
                                <p className={'text-sm ml-4'}>
                                    <code className={'font-mono py-1 px-2 bg-neutral-900 rounded'}>
                                        {key.identifier}
                                    </code>
                                </p>
                                <button
                                    className={'ml-4 p-2 text-sm'}
                                    onClick={() => setDeleteIdentifier(key.identifier)}
                                >
                                    <FontAwesomeIcon
                                        icon={faTrashAlt}
                                        className={'text-neutral-400 hover:text-red-400 transition-colors duration-150'}
                                    />
                                </button>
                            </div>
                        ))
                }
            </ContentBox>
        </div>
    );
};
