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
import { faDocker } from '@fortawesome/free-brands-svg-icons';
import { faEgg, faFireAlt, faMicrochip, faTerminal } from '@fortawesome/free-solid-svg-icons';
import React, { forwardRef, useImperativeHandle, useRef } from 'react';
import AdminBox from '@/components/admin/AdminBox';
import { Egg } from '@/api/admin/eggs/getEgg';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';
import { object } from 'yup';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';

export function EggInformationContainer () {
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

export function EggStartupContainer ({ className }: { className?: string }) {
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

export function EggImageContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faDocker} title={'Docker'} css={tw`relative`}>
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

export function EggLifecycleContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faFireAlt} title={'Lifecycle'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={'configStop'}
                name={'configStop'}
                label={'Stop Command'}
                type={'text'}
                css={tw`mb-1`}
            />
        </AdminBox>
    );
}

interface EggProcessContainerProps {
    className?: string;
}

export interface EggProcessContainerRef {
    getStartupConfiguration: () => Promise<string | null>;
    getFilesConfiguration: () => Promise<string | null>;
}

export const EggProcessContainer = forwardRef<any, EggProcessContainerProps>(
    function EggProcessContainer ({ className }, ref) {
        const { isSubmitting, values } = useFormikContext<Values>();

        let fetchStartupConfiguration: (() => Promise<string>) | null = null;
        let fetchFilesConfiguration: (() => Promise<string>) | null = null;

        useImperativeHandle<EggProcessContainerRef, EggProcessContainerRef>(ref, () => ({
            getStartupConfiguration: async () => {
                if (fetchStartupConfiguration === null) {
                    return new Promise<null>(resolve => resolve(null));
                }
                return await fetchStartupConfiguration();
            },

            getFilesConfiguration: async () => {
                if (fetchFilesConfiguration === null) {
                    return new Promise<null>(resolve => resolve(null));
                }
                return await fetchFilesConfiguration();
            },
        }));

        return (
            <AdminBox icon={faMicrochip} title={'Process Configuration'} css={tw`relative`} className={className}>
                <SpinnerOverlay visible={isSubmitting}/>

                <div css={tw`mb-5`}>
                    <Label>Startup Configuration</Label>
                    <Editor
                        mode={jsonLanguage}
                        initialContent={values.configStartup}
                        overrides={tw`h-32 rounded`}
                        fetchContent={value => {
                            fetchStartupConfiguration = value;
                        }}
                    />
                </div>

                <div css={tw`mb-1`}>
                    <Label>Configuration Files</Label>
                    <Editor
                        mode={jsonLanguage}
                        initialContent={values.configFiles}
                        overrides={tw`h-48 rounded`}
                        fetchContent={value => {
                            fetchFilesConfiguration = value;
                        }}
                    />
                </div>
            </AdminBox>
        );
    }
);

interface Values {
    name: string;
    description: string;
    startup: string;
    dockerImages: string;
    configStop: string;
    configStartup: string;
    configFiles: string;
}

export default function EggSettingsContainer ({ egg }: { egg: Egg }) {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const ref = useRef<EggProcessContainerRef>();

    const submit = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('egg');

        values.configStartup = await ref.current?.getStartupConfiguration() || '';
        values.configFiles = await ref.current?.getFilesConfiguration() || '';

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
                configStop: egg.configStop || '',
                configStartup: JSON.stringify(egg.configStartup, null, '\t') || '',
                configFiles: JSON.stringify(egg.configFiles, null, '\t') || '',
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
                        <EggLifecycleContainer/>
                    </div>

                    <EggProcessContainer ref={ref} css={tw`mb-6`}/>

                    <div css={tw`bg-neutral-700 rounded shadow-md py-2 px-6 mb-16`}>
                        <div css={tw`flex flex-row`}>
                            <EggDeleteButton
                                eggId={egg.id}
                                onDeleted={() => history.push('/admin/nests')}
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
