import updateEgg from '@/api/admin/eggs/updateEgg';
import EggDeleteButton from '@/components/admin/nests/eggs/EggDeleteButton';
import Button from '@/components/elements/Button';
import Editor from '@/components/elements/Editor';
import Field, { TextareaField } from '@/components/elements/Field';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
import { jsonLanguage } from '@codemirror/lang-json';
import { faEgg, faTerminal } from '@fortawesome/free-solid-svg-icons';
import React from 'react';
import AdminBox from '@/components/admin/AdminBox';
import { Egg } from '@/api/admin/eggs/getEgg';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import { object } from 'yup';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';

function EggInformationContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faEgg} title={'Egg Information'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={'name'}
                name={'name'}
                label={'Name'}
                type={'text'}
                css={tw`mb-6`}
            />

            <Field
                id={'description'}
                name={'description'}
                label={'Description'}
                type={'text'}
                css={tw`mb-2`}
            />
        </AdminBox>
    );
}

function EggDetailsContainer ({ egg }: { egg: Egg }) {
    return (
        <AdminBox icon={faEgg} title={'Egg Details'} css={tw`relative`}>
            <div css={tw`mb-6`}>
                <Label>UUID</Label>
                <Input
                    id={'uuid'}
                    name={'uuid'}
                    type={'text'}
                    value={egg.uuid}
                    readOnly
                />
            </div>

            <div css={tw`mb-2`}>
                <Label>Author</Label>
                <Input
                    id={'author'}
                    name={'author'}
                    type={'text'}
                    value={egg.author}
                    readOnly
                />
            </div>
        </AdminBox>
    );
}

function EggStartupContainer ({ className }: { className?: string }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faTerminal} title={'Startup Command'} css={tw`relative`} className={className}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={'startup'}
                name={'startup'}
                label={'Startup Command'}
                type={'text'}
                css={tw`mb-1`}
            />
        </AdminBox>
    );
}

function EggImageContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={undefined} title={'Image'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <TextareaField
                id={'dockerImages'}
                name={'dockerImages'}
                label={'Docker Images'}
                rows={5}
            />
        </AdminBox>
    );
}

function EggStopContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={undefined} title={'Stop'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={'stopCommand'}
                name={'stopCommand'}
                label={'Stop Command'}
                type={'text'}
                css={tw`mb-1`}
            />
        </AdminBox>
    );
}

function EggProcessContainer ({ className, egg }: { className?: string, egg: Egg }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox title={'Process Configuration'} css={tw`relative`} className={className}>
            <SpinnerOverlay visible={isSubmitting}/>

            <div css={tw`mb-6`}>
                <Label>Startup Configuration</Label>
                <Editor
                    mode={jsonLanguage}
                    initialContent={JSON.stringify(egg.configStartup, null, '\t') || ''}
                    overrides={tw`h-32 rounded`}
                />
            </div>

            <div css={tw`mb-1`}>
                <Label>Configuration Files</Label>
                <Editor
                    mode={jsonLanguage}
                    initialContent={JSON.stringify(egg.configFiles, null, '\t') || ''}
                    overrides={tw`h-48 rounded`}
                />
            </div>
        </AdminBox>
    );
}

interface Values {
    name: string;
    description: string;
    startup: string;
    dockerImages: string;
    stopCommand: string;
}

export default function EggSettingsContainer ({ egg }: { egg: Egg }) {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('egg');

        // TODO: Send data from code blocks.

        updateEgg(egg.id, { ...values, dockerImages: values.dockerImages.split('\n') })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'egg', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                name: egg.name,
                description: egg.description || '',
                startup: egg.startup,
                dockerImages: egg.dockerImages.join('\n'),
                stopCommand: egg.configStop || '',
            }}
            validationSchema={object().shape({
            })}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6`}>
                        <EggInformationContainer/>
                        <EggDetailsContainer egg={egg}/>
                    </div>

                    <EggStartupContainer css={tw`mb-6`}/>

                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6`}>
                        <EggImageContainer/>
                        <EggStopContainer/>
                    </div>

                    <EggProcessContainer egg={egg} css={tw`mb-6`}/>

                    <div css={tw`bg-neutral-700 rounded shadow-md py-2 px-6 mb-16`}>
                        <div css={tw`flex flex-row`}>
                            <EggDeleteButton
                                eggId={egg.id}
                                onDeleted={() => history.push('/admin/eggs')}
                            />
                            <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
}
