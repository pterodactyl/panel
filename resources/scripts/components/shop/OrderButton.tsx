import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import orderServer from '@/api/shop/orderServer';
import tw from 'twin.macro';
import OrderModal from '@/components/shop/OrderModal';

interface Props {
    game: any;
    currency: string;
    tos: any[];
    onBuy: () => void;
}

export default ({ game, onBuy, currency, tos }: Props) => {
    const [ visible, setVisible ] = useState(false);
    const [ isLoading, setIsLoading ] = useState(false);
    const [ disabled, setDisabled ] = useState(false);

    const { clearAndAddHttpError, clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = () => {
        setDisabled(true);
        setIsLoading(true);
        clearFlashes('shop');

        orderServer(game.id)
            .then(() => {
                setDisabled(false);
                setIsLoading(false);
                setVisible(false);
                addFlash({ key: 'shop', message: 'You\'ve successfully ordered your server.', type: 'success', title: 'Success' });
                onBuy();
            })
            .catch((error) => {
                setDisabled(false);
                setIsLoading(false);
                setVisible(false);
                clearAndAddHttpError({ key: 'shop', error });
            });
    };

    return (
        <>
            <OrderModal
                visible={visible}
                tos={tos}
                title={'Are you sure you want to order this server?'}
                buttonText={'Yes, order it'}
                onConfirmed={submit}
                showSpinnerOverlay={isLoading}
                onModalDismissed={() => setVisible(false)}
            />
            <Button isLoading={disabled} disabled={disabled} type={'button'} color={'primary'} css={tw`mt-3`} onClick={() => setVisible(true)}>
                Order Now - {game.price} {currency}
            </Button>
        </>
    );
};
