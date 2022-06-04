import tw from 'twin.macro';
import { format } from 'date-fns';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';
import Button from '@/components/elements/Button';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import GreyRowBox from '@/components/elements/GreyRowBox';
import ContentBox from '@/components/elements/ContentBox';
import deleteAccountLogs from '@/api/account/deleteAccountLogs';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import getAccountLogs, { AccountLog } from '@/api/account/getAccountLogs';

const AccountLogContainer = () => {
    const [ loading, setLoading ] = useState(false);
    const [ isSubmitting, setIsSubmitting ] = useState(false);
    const [ logs, setLogs ] = useState<AccountLog[]>([]);

    const { addError, clearFlashes, addFlash } = useFlash();

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

    const submit = () => {
        setIsSubmitting(true);
        deleteAccountLogs()
            .then(() => {
                setIsSubmitting(false);
                addFlash({
                    type: 'success',
                    key: 'account:logs',
                    message: 'Account logs have been deleted.',
                });
            })
            .catch(error => {
                addError({
                    key: 'account:logs',
                    message: httpErrorToHuman(error),
                });
            });
    };

    return (
        <ContentBox css={tw`flex-1 overflow-hidden mt-8 md:mt-0`}>
            <SpinnerOverlay visible={isSubmitting}/>
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
                            <p css={tw`flex-initial text-lg ml-2`}>
                                #{log.id}
                            </p>
                            <p css={tw`flex-1 text-xs ml-4 inline-block`}>
                                <code css={tw`font-mono py-1 px-2 md:bg-neutral-900 rounded mr-2`}>
                                    {log.action}
                                </code>
                            </p>
                            <div css={tw`flex-initial text-xs ml-4 hidden md:block overflow-hidden`}>
                                <p css={tw`text-sm break-words`}>{log.ipAddress}</p>
                                <p css={tw`text-2xs text-neutral-300 uppercase`}>
                                    {log.createdAt ? format(log.createdAt, 'MMM do, yyyy HH:mm') : ''}
                                </p>
                            </div>
                        </GreyRowBox>
                    ))
            }
            <Button
                css={tw`mt-6`}
                size={'xlarge'}
                onClick={submit}
                color={'red'}
            >
                Delete Logs
            </Button>
        </ContentBox>
    );
};

export default AccountLogContainer;
