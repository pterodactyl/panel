import React, { useEffect } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import PayPalBox from '@/components/shop/payment/PayPalBox';
import StripeBox from '@/components/shop/payment/StripeBox';
import useSWR from 'swr';
import getDetails from '@/api/shop/payment/getDetails';
import useFlash from '@/plugins/useFlash';
import Spinner from '@/components/elements/Spinner';
import Button from '@/components/elements/Button';
import Label from '@/components/elements/Label';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faMoneyBill } from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import styled from 'styled-components/macro';
import { useParams } from 'react-router';

const Code = styled.code`
    ${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm inline-block`}
`;

export interface DetailsResponse {
    stripeKey: string;
    enabled: PaymentEnabledInterface;
    minAmount: number;
    maxAmount: number;
    paymentState: string;
    paymentMessage: string;
    balance: number;
    transactions: any[];
    currency: string;
}

export interface PaymentEnabledInterface {
    paypal: number;
    stripe: number;
}

export default () => {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    const { provider, sessionId } = useParams();

    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();

    const search = window.location.search;
    const params = new URLSearchParams(search);
    const PayerID = params.get('PayerID');
    let session = params.get('paymentId');

    if (provider === 'stripe') {
        session = sessionId || '';
    }

    const { data, error } = useSWR<DetailsResponse>([provider, session, PayerID, '/payment/details'], (provider, session, PayerID) => getDetails(provider, session, PayerID), {
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (!error) {
            clearFlashes('payments');
        } else {
            clearAndAddHttpError({ key: 'payments', error });
        }
    }, [error]);

    useEffect(() => {
        if (data) {
            clearFlashes('payments');

            if (data.paymentState === 'success') {
                addFlash({ key: 'payments', message: data.paymentMessage, type: 'success', title: 'Success' });
            }

            if (data.paymentState === 'error') {
                addFlash({ key: 'payments', message: data.paymentMessage, type: 'error', title: 'Error' });
            }
        }
    }, [data]);

    return (
        <PageContentBlock title={'Payments'} showFlashKey={'payments'}>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    <div css={tw`w-full flex flex-wrap`}>
                        {data.enabled.paypal === 1 ? <PayPalBox enabled={data.enabled} minAmount={data.minAmount} maxAmount={data.maxAmount} currency={data.currency} /> : <></>}
                        {data.enabled.stripe === 1 ? (
                            <StripeBox enabled={data.enabled} minAmount={data.minAmount} maxAmount={data.maxAmount} currency={data.currency} stripeKey={data.stripeKey} />
                        ) : (
                            <></>
                        )}
                    </div>
                    <div css={tw`w-full pt-4`}>
                        <div css={tw`rounded shadow-md bg-neutral-700 mt-2`}>
                            <div css={tw`bg-neutral-900 rounded-t p-3 border-b border-black`}>
                                Transaction Log - My Balance: {data.balance} {data.currency}
                            </div>
                        </div>

                        {data.transactions.length < 1 ? (
                            <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>There are no transactions.</p>
                        ) : (
                            data.transactions.map((item, key) => (
                                <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                                    <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`}>
                                        <div css={tw`flex items-center w-full md:w-auto`}>
                                            <div css={tw`pr-4 text-neutral-400`}>
                                                <FontAwesomeIcon icon={faMoneyBill} />
                                            </div>
                                            <div css={tw`flex-1 w-64`}>
                                                <Code>{item.payment_type.charAt(0).toUpperCase() + item.payment_type.slice(1)}</Code>
                                                <Label>Payment Method</Label>
                                            </div>
                                            <div css={tw`flex-1 w-64 ml-4`}>
                                                <Code>
                                                    {item.amount} {data.currency}
                                                </Code>
                                                <Label>Amount</Label>
                                            </div>
                                            <div css={tw`flex-1 w-64 ml-4`}>
                                                <Code>{item.completed === 1 ? 'Yes' : 'No'}</Code>
                                                <Label>Completed</Label>
                                            </div>
                                            <div css={tw`flex-1 w-16`}>
                                                <Code>{item.created_at}</Code>
                                                <Label>Date</Label>
                                            </div>
                                        </div>
                                        <div css={tw`w-full md:flex-none md:w-32 md:text-center mt-4 md:mt-0 text-right`}>
                                            <Link to={`/api/client/shop/payment/invoice/${item.id}`} rel={'noreferrer'} target={'_blank'}>
                                                <Button color={'primary'}>View Invoice</Button>
                                            </Link>
                                        </div>
                                    </GreyRowBox>
                                </GreyRowBox>
                            ))
                        )}
                    </div>
                </>
            )}
        </PageContentBlock>
    );
};
