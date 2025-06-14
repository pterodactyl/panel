import React, { useContext } from 'react';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import asModal from '@/hoc/asModal';
import ModalContext from '@/context/ModalContext';

type Props = {
    title: string;
    buttonText: string;
    tos: any;
    onConfirmed: () => void;
    showSpinnerOverlay?: boolean;
};

const OrderModal: React.FC<Props> = ({ title, buttonText, onConfirmed, tos }) => {
    const { dismiss } = useContext(ModalContext);

    return (
        <>
            <h2 css={tw`text-2xl mb-6`}>{title}</h2>
            <div css={tw`text-neutral-100`}>
                {tos.tos_url !== '' ?
                    <>
                        By proceeding with this purchase, you confirm that you have read, understood, and agreed to be bound by our <a rel={'noreferrer'} href={tos.tos_url} target={'_blank'} style={{ color: 'rgba(8.771999999999997,102.92479999999992,210.528)' }}>Terms Of Service.</a>.
                    </>
                    :
                    <>
                        By proceeding with this purchase, you confirm that you have read, understood, and agreed to be bound by our Terms Of Service.
                        <br />
                        <br />
                        <hr />
                        <br />
                        <div dangerouslySetInnerHTML={{ __html: tos.tos }} />
                    </>
                }
            </div>
            <div css={tw`flex flex-wrap items-center justify-end mt-8`}>
                <Button isSecondary onClick={() => dismiss()} css={tw`w-full sm:w-auto border-transparent`}>
                    Cancel
                </Button>
                <Button color={'red'} css={tw`w-full sm:w-auto mt-4 sm:mt-0 sm:ml-4`} onClick={() => onConfirmed()}>
                    {buttonText}
                </Button>
            </div>
        </>
    );
};

OrderModal.displayName = 'ConfirmationModal';

export default asModal<Props>(props => ({
    showSpinnerOverlay: props.showSpinnerOverlay,
}))(OrderModal);
