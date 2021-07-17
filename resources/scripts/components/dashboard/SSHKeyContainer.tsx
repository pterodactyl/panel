import React, { useEffect, useState } from 'react';
import { Field as FormikField, Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';
import { object, string } from 'yup';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import createSSHKey from '@/api/account/ssh/createSSHKey';
import deleteSSHKey from '@/api/account/ssh/deleteSSHKey';
import getSSHKeys, { SSHKey } from '@/api/account/ssh/getSSHKeys';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import ContentBox from '@/components/elements/ContentBox';
import Field from '@/components/elements/Field';
import GreyRowBox from '@/components/elements/GreyRowBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import { Textarea } from '@/components/elements/Input';

interface Values {
    name: string;
    publicKey: string;
}

const AddSSHKeyForm = ({ onKeyAdded }: { onKeyAdded: (key: SSHKey) => void }) => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const submit = ({ name, publicKey }: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearFlashes('ssh_keys');

        createSSHKey(name, publicKey)
            .then(key => {
                resetForm();
                onKeyAdded(key);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'ssh_keys', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ name: '', publicKey: '' }}
            validationSchema={object().shape({
                name: string().required(),
                publicKey: string().required(),
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
                        description={'A descriptive name for this SSH key.'}
                    />
                    <div css={tw`mt-6`}>
                        <FormikFieldWrapper
                            name={'publicKey'}
                            label={'Public Key'}
                            description={'SSH Public Key starting with ssh-*'}
                        >
                            <FormikField as={Textarea} name={'publicKey'} rows={6}/>
                        </FormikFieldWrapper>
                    </div>
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

    const [ keys, setKeys ] = useState<SSHKey[]>([]);
    const [ loading, setLoading ] = useState(true);
    const [ deleteId, setDeleteId ] = useState<number | null>(null);

    const doDeletion = (id: number | null) => {
        if (id === null) {
            return;
        }

        clearFlashes('ssh_keys');

        deleteSSHKey(id)
            .then(() => setKeys(s => ([
                ...(s || []).filter(key => key.id !== id),
            ])))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'ssh_keys', error });
            });
    };

    useEffect(() => {
        clearFlashes('ssh_keys');

        getSSHKeys()
            .then(keys => setKeys(keys))
            .then(() => setLoading(false))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'ssh_keys', error });
            });
    }, []);

    return (
        <PageContentBlock title={'SSH Keys'}>
            <FlashMessageRender byKey={'ssh_keys'}/>
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'SSH Keys'} css={tw`flex-1 md:mr-8`}>
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
                        Are you sure you wish to delete this SSH key?
                    </ConfirmationModal>
                    {keys.length === 0 ?
                        !loading ?
                            <p css={tw`text-center text-sm`}>
                                No SSH keys have been configured for this account.
                            </p>
                            : null
                        :
                        keys.map((key, index) => (
                            <GreyRowBox key={index} css={[ tw`bg-neutral-600 flex items-center`, index > 0 && tw`mt-2` ]}>
                                <FontAwesomeIcon icon={faKey} css={tw`text-neutral-300`}/>
                                <div css={tw`ml-4 flex-1 overflow-hidden`}>
                                    <p css={tw`text-sm break-words`}>{key.name}</p>
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

                <ContentBox title={'Add SSH Key'} css={tw`flex-none w-full mt-8 md:mt-0 md:w-1/2`}>
                    <AddSSHKeyForm onKeyAdded={key => setKeys(s => ([ ...s!, key ]))}/>
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
