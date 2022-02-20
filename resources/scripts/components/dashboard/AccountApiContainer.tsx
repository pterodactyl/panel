import React, { useEffect } from 'react';
import ContentBox from '@/components/elements/ContentBox';
import CreateApiKeyForm from '@/components/dashboard/forms/CreateApiKeyForm';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey } from '@fortawesome/free-solid-svg-icons';
import FlashMessageRender from '@/components/FlashMessageRender';
import { format } from 'date-fns';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { useAPIKeys } from '@/api/account/api-keys';
import { useFlashKey } from '@/plugins/useFlash';
import DeleteAPIKeyButton from '@/components/dashboard/security/DeleteAPIKeyButton';

export default () => {
    const { clearAndAddHttpError } = useFlashKey('account');
    const { data: keys, isValidating, error } = useAPIKeys({
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });

    useEffect(() => {
        clearAndAddHttpError(error);
    }, [ error ]);

    return (
        <PageContentBlock title={'Account API'}>
            <FlashMessageRender byKey={'account'}/>
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Create API Key'} css={tw`flex-none w-full md:w-1/2`}>
                    <CreateApiKeyForm/>
                </ContentBox>
                <ContentBox title={'API Keys'} css={tw`flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8`}>
                    <SpinnerOverlay visible={!keys && isValidating}/>
                    {
                        !keys || !keys.length ?
                            <p css={tw`text-center text-sm`}>
                                {!keys ? 'Loading...' : 'No API keys exist for this account.'}
                            </p>
                            :
                            keys.map((key, index) => (
                                <GreyRowBox
                                    key={key.identifier}
                                    css={[ tw`bg-neutral-600 flex items-center`, index > 0 && tw`mt-2` ]}
                                >
                                    <FontAwesomeIcon icon={faKey} css={tw`text-neutral-300`}/>
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
                                    <DeleteAPIKeyButton identifier={key.identifier}/>
                                </GreyRowBox>
                            ))
                    }
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
