import { LanguageDescription } from '@codemirror/language';
import { json } from '@codemirror/lang-json';
import { faDocker } from '@fortawesome/free-brands-svg-icons';
import { faEgg, faFireAlt, faMicrochip, faTerminal } from '@fortawesome/free-solid-svg-icons';
import type { FormikHelpers } from 'formik';
import { Form, Formik, useFormikContext } from 'formik';
import { forwardRef, useImperativeHandle, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import tw from 'twin.macro';
import { object } from 'yup';

import { useEggFromRoute } from '@/api/admin/egg';
import updateEgg from '@/api/admin/eggs/updateEgg';
import AdminBox from '@/components/admin/AdminBox';
import EggDeleteButton from '@/components/admin/nests/eggs/EggDeleteButton';
import EggExportButton from '@/components/admin/nests/eggs/EggExportButton';
import { Button } from '@/components/elements/button';
import { Editor } from '@/components/elements/editor';
import Field, { TextareaField } from '@/components/elements/Field';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';

export function EggInformationContainer() {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faEgg} title={'Egg Information'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting} />

            <Field id={'name'} name={'name'} label={'Name'} type={'text'} css={tw`mb-6`} />

            <Field id={'description'} name={'description'} label={'Description'} type={'text'} css={tw`mb-2`} />
        </AdminBox>
    );
}

function EggDetailsContainer() {
    const { data: egg } = useEggFromRoute();

    if (!egg) {
        return null;
    }

    return (
        <AdminBox icon={faEgg} title={'Egg Details'} css={tw`relative`}>
            <div css={tw`mb-6`}>
                <Label>UUID</Label>
                <Input id={'uuid'} name={'uuid'} type={'text'} value={egg.uuid} readOnly />
            </div>

            <div css={tw`mb-2`}>
                <Label>Author</Label>
                <Input id={'author'} name={'author'} type={'text'} value={egg.author} readOnly />
            </div>
        </AdminBox>
    );
}

export function EggStartupContainer({ className }: { className?: string }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faTerminal} title={'Startup Command'} css={tw`relative`} className={className}>
            <SpinnerOverlay visible={isSubmitting} />

            <Field id={'startup'} name={'startup'} label={'Startup Command'} type={'text'} css={tw`mb-1`} />
        </AdminBox>
    );
}

export function EggImageContainer() {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faDocker} title={'Docker'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting} />

            <TextareaField id={'dockerImages'} name={'dockerImages'} label={'Docker Images'} rows={5} />
        </AdminBox>
    );
}

export function EggLifecycleContainer() {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faFireAlt} title={'Lifecycle'} css={tw`relative`}>
            <SpinnerOverlay visible={isSubmitting} />

            <Field id={'configStop'} name={'configStop'} label={'Stop Command'} type={'text'} css={tw`mb-1`} />
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

export const EggProcessContainer = forwardRef<any, EggProcessContainerProps>(function EggProcessContainer(
    { className },
    ref,
) {
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
            <SpinnerOverlay visible={isSubmitting} />

            <div css={tw`mb-5`}>
                <Label>Startup Configuration</Label>
                <Editor
                    childClassName={tw`h-32 rounded`}
                    initialContent={values.configStartup}
                    fetchContent={value => {
                        fetchStartupConfiguration = value;
                    }}
                    language={LanguageDescription.of({ name: 'json', support: json() })}
                />
            </div>

            <div css={tw`mb-1`}>
                <Label>Configuration Files</Label>
                <Editor
                    childClassName={tw`h-48 rounded`}
                    initialContent={values.configFiles}
                    fetchContent={value => {
                        fetchFilesConfiguration = value;
                    }}
                    language={LanguageDescription.of({ name: 'json', support: json() })}
                />
            </div>
        </AdminBox>
    );
});

interface Values {
    name: string;
    description: string;
    startup: string;
    dockerImages: string;
    configStop: string;
    configStartup: string;
    configFiles: string;
}

export default function EggSettingsContainer() {
    const navigate = useNavigate();

    const ref = useRef<EggProcessContainerRef>();

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data: egg } = useEggFromRoute();

    if (!egg) {
        return null;
    }

    const submit = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('egg');

        values.configStartup = (await ref.current?.getStartupConfiguration()) ?? '';
        values.configFiles = (await ref.current?.getFilesConfiguration()) ?? '';

        const dockerImages: Record<string, string> = {};
        for (const v of values.dockerImages.split('\n')) {
            const parts = v.trim().split('|');
            const image = parts[0] ?? '';
            const alias = parts[1] ?? image;

            dockerImages[alias] = image;
        }

        updateEgg(egg.id, {
            ...values,
            dockerImages,
        })
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
                dockerImages: Object.keys(egg.dockerImages)
                    .map(key => {
                        return `${egg.dockerImages[key]}|${key}`;
                    })
                    .join('\n'),
                configStop: egg.configStop || '',
                configStartup: JSON.stringify(egg.configStartup, null, '\t') || '',
                configFiles: JSON.stringify(egg.configFiles, null, '\t') || '',
            }}
            validationSchema={object().shape({})}
        >
            {({ isSubmitting, isValid }) => (
                <Form>
                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6`}>
                        <EggInformationContainer />
                        <EggDetailsContainer />
                    </div>

                    <EggStartupContainer css={tw`mb-6`} />

                    <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6`}>
                        <EggImageContainer />
                        <EggLifecycleContainer />
                    </div>

                    <EggProcessContainer ref={ref} css={tw`mb-6`} />

                    <div css={tw`bg-neutral-700 rounded shadow-md px-4 xl:px-5 py-4 mb-16`}>
                        <div css={tw`flex flex-row`}>
                            <EggDeleteButton eggId={egg.id} onDeleted={() => navigate('/admin/nests')} />
                            <EggExportButton css={tw`ml-auto mr-4`} />
                            <Button type="submit" disabled={isSubmitting || !isValid}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </Form>
            )}
        </Formik>
    );
}
