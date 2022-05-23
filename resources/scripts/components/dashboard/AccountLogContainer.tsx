import tw from 'twin.macro';
import { Actions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { useStoreActions } from '@/state/hooks';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';
import ContentBox from '@/components/elements/ContentBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import getAccountLogs, { AccountLog } from '@/api/account/getAccountLogs';

const AccountLogContainer = () => {
    const [ loading, setLoading ] = useState(false);
    const [ logs, setLogs ] = useState<AccountLog[]>([]);

    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('account:logs');
        getAccountLogs()
            .then(logs => setLogs(logs))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
                addError({ key: 'account:logs', message: httpErrorToHuman(error) });
            });
    }, []);

    return (
        <ContentBox css={tw`flex-1 overflow-hidden mt-8 md:mt-0`}>
            <FlashMessageRender byKey={'account:logs'} css={tw`mb-2`} />
            {
                logs.length === 0 ?
                    <p css={tw`text-center text-sm`}>
                        {loading ? <Spinner size={'large'} centered /> : 'You do not have any account logs.'}
                    </p>
                    :
                    logs.map((log, index) => (
                        <GreyRowBox
                            key={log.id}
                            css={[ tw`bg-neutral-800 flex`, index > 0 && tw`mt-2` ]}
                        >
                            <p css={tw`flex-inline text-lg ml-2`}>
                                #{log.id}
                            </p>
                            <p css={tw`flex-1 text-xs ml-4 inline-block`}>
                                <code css={tw`font-mono py-1 px-2 bg-neutral-900 rounded mr-2`}>
                                    {log.action}
                                </code>
                            </p>
                            <p css={tw`flex-inlinetext-xs ml-4 hidden inline-block`}>
                                IP address:&nbsp;
                                <code css={tw`font-mono py-1 px-2 bg-neutral-900 rounded mr-2`}>
                                    {log.ipAddress}
                                </code>
                            </p>
                        </GreyRowBox>
                    ))
            }
        </ContentBox>
    );
};

export default AccountLogContainer;
