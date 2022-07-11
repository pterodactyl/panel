import tw from 'twin.macro';
import { format } from 'date-fns';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import styled from 'styled-components/macro';
import { useFlashKey } from '@/plugins/useFlash';
import React, { useState, useEffect } from 'react';
import { Dialog } from '@/components/elements/dialog';
import GreyRowBox from '@/components/elements/GreyRowBox';
import ContentBox from '@/components/elements/ContentBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import PageContentBlock from '@/components/elements/PageContentBlock';
import getReferralCodes, { ReferralCode } from '@/api/account/getReferralCodes';
import deleteReferralCode from '@/api/account/deleteReferralCode';

const Container = styled.div`
    ${tw`flex flex-wrap`};

    & > div {
        ${tw`w-full`};

        ${breakpoint('sm')`
      width: calc(50% - 1rem);
    `}

        ${breakpoint('md')`
      ${tw`w-auto flex-1`};
    `}
    }
`;

export default () => {
    const [code, setCode] = useState('');
    const [loading, setLoading] = useState(true);
    const [codes, setCodes] = useState<ReferralCode[]>([]);
    const { clearAndAddHttpError } = useFlashKey('referrals');

    useEffect(() => {
        getReferralCodes()
            .then((codes) => setCodes(codes))
            .then(() => setLoading(false))
            .catch((error) => clearAndAddHttpError(error));
    }, []);

    const doDeletion = (code: string) => {
        setLoading(true);

        clearAndAddHttpError();
        deleteReferralCode(code)
            .then(() => {
                getReferralCodes().then((codes) => setCodes(codes));
            })
            .catch((error) => clearAndAddHttpError(error))
            .then(() => {
                setLoading(false);
                setCode('');
            });
    };

    return (
        <PageContentBlock title={'Referrals'}>
            <h1 className={'text-5xl'}>Referrals</h1>
            <h3 className={'text-2xl mt-2 text-neutral-500'}>Create a code and share it with others.</h3>
            <Container css={tw`lg:grid lg:grid-cols-3 my-10`}>
                <ContentBox title={'Your Referral Codes'} css={tw`sm:mt-0`}>
                    <Dialog.Confirm
                        title={'Delete Referral Code'}
                        confirm={'Delete Code'}
                        open={!!code}
                        onClose={() => setCode('')}
                        onConfirmed={() => doDeletion(code)}
                    >
                        Users will no longer be able to use this key for signup.
                    </Dialog.Confirm>
                    <SpinnerOverlay visible={loading} />
                    {codes.length === 0 ? (
                        <p css={tw`text-center`}>{loading ? 'Loading...' : 'No referral exist for this account.'}</p>
                    ) : (
                        codes.map((code, index) => (
                            <GreyRowBox
                                key={code.code}
                                css={[tw`bg-neutral-900 flex items-center`, index > 0 && tw`mt-2`]}
                            >
                                <Icon.Key css={tw`text-neutral-300`} />
                                <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                    <p css={tw`text-sm break-words`}>{code.code}</p>
                                    <p css={tw`text-2xs text-neutral-300 uppercase`}>
                                        Created at:&nbsp;
                                        {code.createdAt ? format(code.createdAt, 'MMM do, yyyy HH:mm') : 'Never'}
                                    </p>
                                </div>
                                <button css={tw`ml-4 p-2 text-sm`} onClick={() => setCode(code.code)}>
                                    <Icon.Trash
                                        css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`}
                                    />
                                </button>
                            </GreyRowBox>
                        ))
                    )}
                </ContentBox>
                <ContentBox title={'Available Perks'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <h1 css={tw`text-7xl flex justify-center items-center`}>hi</h1>
                </ContentBox>
                <ContentBox title={'Users Referred'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <h1 css={tw`text-7xl flex justify-center items-center`}>hi</h1>
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};
