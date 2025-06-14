import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import useSWR from 'swr';
import getRenewDetails from '@/api/server/getRenewDetails';
import Spinner from '@/components/elements/Spinner';
import renewServer from '@/api/server/renewServer';

export interface RenewDetailsResponse {
    price: number;
    // eslint-disable-next-line camelcase
    expired_at: string;
    balance: number;
    currency: string;
}

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const [isSubmitting, setIsSubmitting] = useState(false);
    const [modalVisible, setModalVisible] = useState(false);

    const { addFlash, clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes
    );

    const { data, error, mutate } = useSWR<RenewDetailsResponse>([uuid, '/renew'], (uuid) => getRenewDetails(uuid));

    useEffect(() => {
        if (!error) {
            clearFlashes('settings');
        } else {
            clearAndAddHttpError({ key: 'settings', error });
        }
    }, [error]);

    const renew = () => {
        clearFlashes('settings');
        setIsSubmitting(true);

        renewServer(uuid)
            .then(() => {
                setIsSubmitting(false);
                setModalVisible(false);
                addFlash({
                    key: 'settings',
                    message: "You've successfully renewed your server.",
                    type: 'success',
                    title: 'Success',
                });
                mutate();
            })
            .catch((error) => {
                setIsSubmitting(false);
                setModalVisible(false);
                addFlash({ key: 'settings', type: 'error', message: httpErrorToHuman(error) });
            });
    };

    return (
        <TitledGreyBox title={'Renew Server'} css={tw`relative`}>
            {!data ? (
                <div css={tw`w-full`}>
                    <Spinner size={'large'} centered />
                </div>
            ) : (
                <>
                    <ConfirmationModal
                        title={'Confirm server renew'}
                        buttonText={'Yes, renew server'}
                        onConfirmed={renew}
                        showSpinnerOverlay={isSubmitting}
                        visible={modalVisible}
                        onModalDismissed={() => setModalVisible(false)}
                    >
                        Your server will be renewed with 1 month, are you sure you wish to continue?
                    </ConfirmationModal>
                    <div css={tw`flex items-center justify-between mt-2 text-sm`}>
                        <p>Your Balance</p>
                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                            {data.balance} {data.currency}
                        </code>
                    </div>
                    {data.expired_at !== null && (
                        <div css={tw`flex items-center justify-between mt-2 text-sm pb-6`}>
                            <p>Expiration Date</p>
                            <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{data.expired_at}</code>
                        </div>
                    )}
                    <p css={tw`text-sm`}>
                        Your server will be renew with 1 month and the price will be removed from your balance.
                        {data.price === 0 && (
                            <strong css={tw`font-medium`}>
                                <br />
                                <br />
                                You can&apos;t renew this server.
                            </strong>
                        )}
                    </p>
                    <div css={tw`mt-6 text-right`}>
                        <Button
                            type={'button'}
                            color={'green'}
                            isSecondary
                            disabled={data.price === 0}
                            onClick={() => setModalVisible(true)}
                        >
                            Renew Server - {data.price} {data.currency}
                        </Button>
                    </div>
                </>
            )}
        </TitledGreyBox>
    );
};
