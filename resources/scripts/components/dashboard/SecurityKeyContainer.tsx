import React, { useEffect, useState } from 'react';
import { format } from 'date-fns';
import { Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';
import { object, string } from 'yup';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import deleteWebauthnKey from '@/api/account/webauthn/deleteWebauthnKey';
import getWebauthnKeys, { WebauthnKey } from '@/api/account/webauthn/getWebauthnKeys';
import registerWebauthnKey from '@/api/account/webauthn/registerWebauthnKey';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import ContentBox from '@/components/elements/ContentBox';
import Field from '@/components/elements/Field';
import GreyRowBox from '@/components/elements/GreyRowBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
import ConfirmationModal from '@/components/elements/ConfirmationModal';

interface Values {
    name: string;
}

const AddSecurityKeyForm = ({ onKeyAdded }: { onKeyAdded: (key: WebauthnKey) => void }) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const submit = ({ name }: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearFlashes('security_keys');

        registerWebauthnKey(name)
            .then(key => {
                resetForm();
                onKeyAdded(key);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'security_keys', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ name: '' }}
            validationSchema={object().shape({
                name: string().required(),
            })}
        >
            {({ isSubmitting }) => (
                <Form>
                    <SpinnerOverlay visible={isSubmitting}/>
                    <Field
                        type={'text'}
                        id={'name'}
                        name={'name'}
                        label={'Name'}
                        description={'A descriptive name for this security key.'}
                    />
                    <div css={tw`flex justify-end mt-6`}>
                        <Button>Create</Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const [ keys, setKeys ] = useState<WebauthnKey[]>([]);
    const [ loading, setLoading ] = useState(true);
    const [ deleteId, setDeleteId ] = useState<number | null>(null);

    const doDeletion = (id: number | null) => {
        if (id === null) {
            return;
        }

        clearFlashes('security_keys');

        deleteWebauthnKey(id)
            .then(() => setKeys(s => ([
                ...(s || []).filter(key => key.id !== id),
            ])))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'security_keys', error });
            });
    };

    useEffect(() => {
        clearFlashes('security_keys');

        getWebauthnKeys()
            .then(keys => setKeys(keys))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'security_keys', error });
            });
    }, []);

    return (
        <PageContentBlock title={'Security Keys'}>
            <FlashMessageRender byKey={'security_keys'}/>
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Add Security Key'} css={tw`flex-1 md:mr-8`}>
                    <AddSecurityKeyForm onKeyAdded={key => setKeys(s => ([ ...s!, key ]))}/>
                </ContentBox>
                <ContentBox title={'Security Keys'} css={tw`flex-none w-full mt-8 md:mt-0 md:w-1/2`}>
                    <SpinnerOverlay visible={loading}/>
                    <ConfirmationModal
                        visible={!!deleteId}
                        title={'Confirm key deletion'}
                        buttonText={'Yes, delete key'}
                        onConfirmed={() => {
                            doDeletion(deleteId);
                            setDeleteId(null);
                        }}
                        onModalDismissed={() => setDeleteId(null)}
                    >
                        Are you sure you wish to delete this security key?
                        You will no longer be able to authenticate using this key.
                    </ConfirmationModal>
                    {keys.length === 0 ?
                        !loading ?
                            <p css={tw`text-center text-sm`}>
                                No security keys have been configured for this account.
                            </p>
                            : null
                        :
                        keys.map((key, index) => (
                            <GreyRowBox key={index} css={[ tw`bg-neutral-900 flex items-center`, index > 0 && tw`mt-2` ]}>
                                <FontAwesomeIcon icon={faKey} css={tw`text-neutral-300`}/>
                                <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                    <p css={tw`text-sm break-words`}>{key.name}</p>
                                    <p css={tw`text-2xs text-neutral-300 uppercase`}>
                                        Last used:&nbsp;
                                        {key.lastUsedAt ? format(key.lastUsedAt, 'MMM do, yyyy HH:mm') : 'Never'}
                                    </p>
                                </div>
                                <button css={tw`ml-4 p-2 text-sm`} onClick={() => setDeleteId(key.id)}>
                                    <FontAwesomeIcon
                                        icon={faTrashAlt}
                                        css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`}
                                    />
                                </button>
                            </GreyRowBox>
                        ))
                    }
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
