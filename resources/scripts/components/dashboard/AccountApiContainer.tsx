import React, { useEffect, useState } from 'react';
import ContentBox from '@/components/elements/ContentBox';
import CreateApiKeyForm from '@/components/dashboard/forms/CreateApiKeyForm';
import getApiKeys, { ApiKey } from '@/api/account/getApiKeys';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Simulate } from 'react-dom/test-utils';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey } from '@fortawesome/free-solid-svg-icons/faKey';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';

export default () => {
    const [ keys, setKeys ] = useState<ApiKey[]>([]);
    const [ loading, setLoading ] = useState(true);

    useEffect(() => {
        getApiKeys()
            .then(keys => setKeys(keys))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
            });
    }, []);

    return (
        <div className={'my-10 flex'}>
            <ContentBox title={'Create API Key'} className={'flex-1'} showFlashes={'account'}>
                <CreateApiKeyForm/>
            </ContentBox>
            <ContentBox title={'API Keys'} className={'ml-10 flex-1'}>
                <SpinnerOverlay visible={loading}/>
                {
                    keys.map(key => (
                        <div key={key.identifier} className={'grey-row-box bg-neutral-600 mb-2 flex items-center'}>
                            <FontAwesomeIcon icon={faKey} className={'text-neutral-300'}/>
                            <p className={'text-sm ml-4 flex-1'}>
                                {key.description}
                            </p>
                            <p className={'text-sm ml-4'}>
                                <code className={'font-mono py-1 px-2 bg-neutral-900 rounded'}>
                                    {key.identifier}
                                </code>
                            </p>
                            <button className={'ml-4 p-2 text-sm'}>
                                <FontAwesomeIcon
                                    icon={faTrashAlt}
                                    className={'text-neutral-400 hover:text-red-400 transition-color duration-150'}
                                />
                            </button>
                        </div>
                    ))
                }
            </ContentBox>
        </div>
    );
};
