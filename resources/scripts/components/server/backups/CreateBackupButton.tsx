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

interface Values {
    name: string;
    ignored: string;
    isLocked: boolean;
}

const ModalContent = ({ ...props }: RequiredModalProps) => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <Form>
                <FlashMessageRender byKey={'backups:create'} css={tw`mb-4`}/>
                <h2 css={tw`text-2xl mb-6`}>创建服务器备份</h2>
                <Field
                    name={'name'}
                    label={'备份名称'}
                    description={'如果提供，则应用于此备份的名称。'}
                />
                <div css={tw`mt-6`}>
                    <FormikFieldWrapper
                        name={'ignored'}
                        label={'忽略的文件和目录'}
                        description={`
                            输入生成此备份时要忽略的文件或文件夹。 留空使用
                            服务器目录根目录中 .pteroignore 文件的内容（如果存在）。
                            除了通过以下方式否定规则外，还支持文件和文件夹的通配符匹配
                            用感叹号作为路径前缀。
                        `}
                    >
                        <FormikField as={Textarea} name={'ignored'} rows={6}/>
                    </FormikFieldWrapper>
                </div>
                <Can action={'backup.delete'}>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'isLocked'}
                            label={'锁定'}
                            description={'防止此备份被删除，直到解锁。'}
                        />
                    </div>
                </Can>
                <div css={tw`flex justify-end mt-6`}>
                    <Button type={'submit'} disabled={isSubmitting}>
                        开始备份
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

    useEffect(() => {
        clearFlashes('backups:create');
    }, [ visible ]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('backups:create');
        createServerBackup(uuid, values)
            .then(backup => {
                mutate(data => ({ ...data, items: data.items.concat(backup), backupCount: data.backupCount + 1 }), false);
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
                创建备份
            </Button>
        </>
    );
};
