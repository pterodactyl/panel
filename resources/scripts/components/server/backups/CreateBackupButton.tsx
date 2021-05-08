import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Field as FormikField, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { boolean, object, string } from 'yup';
import Field from '@/components/elements/Field';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import useFlash from '@/plugins/useFlash';
import createServerBackup from '@/api/server/backups/createServerBackup';
import FlashMessageRender from '@/components/FlashMessageRender';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import { Textarea } from '@/components/elements/Input';
import getServerBackups from '@/api/swr/getServerBackups';
import { ServerContext } from '@/state/server';
import FormikSwitch from '@/components/elements/FormikSwitch';
import Can from '@/components/elements/Can';
import { useTranslation } from 'react-i18next';

interface Values {
    name: string;
    ignored: string;
    isLocked: boolean;
}

const ModalContent = ({ ...props }: RequiredModalProps) => {
    const { isSubmitting } = useFormikContext<Values>();
    const { t } = useTranslation('server');

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <Form>
                <FlashMessageRender byKey={'backups:create'} css={tw`mb-4`}/>
                <h2 css={tw`text-2xl mb-6`}>{t('create_server_backup')}</h2>
                <Field
                    name={'name'}
                    label={t('backup_name')}
                    description={t('backup_name_description')}
                />
                <div css={tw`mt-6`}>
                    <FormikFieldWrapper
                        name={'ignored'}
                        label={t('ignored')}
                        description={t('ignored_description')}
                    >
                        <FormikField as={Textarea} name={'ignored'} rows={6}/>
                    </FormikFieldWrapper>
                </div>
                <Can action={'backup.delete'}>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'isLocked'}
                            label={'Locked'}
                            description={'Prevents this backup from being deleted until explicitly unlocked.'}
                        />
                    </div>
                </Can>
                <div css={tw`flex justify-end mt-6`}>
                    <Button type={'submit'} disabled={isSubmitting}>
                        {t('start_backup')}
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [ visible, setVisible ] = useState(false);
    const { mutate } = getServerBackups();
    const { t } = useTranslation('server');

    useEffect(() => {
        clearFlashes('backups:create');
    }, [ visible ]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('backups:create');
        createServerBackup(uuid, values)
            .then(backup => {
                mutate(data => ({ ...data, items: data.items.concat(backup) }), false);
                setVisible(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'backups:create', error });
                setSubmitting(false);
            });
    };

    return (
        <>
            {visible &&
            <Formik
                onSubmit={submit}
                initialValues={{ name: '', ignored: '', isLocked: false }}
                validationSchema={object().shape({
                    name: string().max(191),
                    ignored: string(),
                    isLocked: boolean(),
                })}
            >
                <ModalContent appear visible={visible} onDismissed={() => setVisible(false)}/>
            </Formik>
            }
            <Button css={tw`w-full sm:w-auto`} onClick={() => setVisible(true)}>
                {t('create_backup')}
            </Button>
        </>
    );
};
