import { LanguageDescription, LanguageSupport, StreamLanguage } from '@codemirror/language';
import { shell } from '@codemirror/legacy-modes/mode/shell';
import { faScroll } from '@fortawesome/free-solid-svg-icons';
import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import tw from 'twin.macro';

import { useEggFromRoute } from '@/api/admin/egg';
import updateEgg from '@/api/admin/eggs/updateEgg';
import AdminBox from '@/components/admin/AdminBox';
import { Button } from '@/components/elements/button';
import { Editor } from '@/components/elements/editor';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';

interface Values {
    scriptContainer: string;
    scriptEntry: string;
    scriptInstall: string;
}

export default function EggInstallContainer() {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const { data: egg } = useEggFromRoute();

    if (!egg) {
        return null;
    }

    let fetchFileContent: (() => Promise<string>) | null = null;

    const submit = async (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        if (fetchFileContent === null) {
            return;
        }

        values.scriptInstall = await fetchFileContent();

        clearFlashes('egg');

        updateEgg(egg.id, values)
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
                scriptContainer: egg.scriptContainer,
                scriptEntry: egg.scriptEntry,
                scriptInstall: '',
            }}
        >
            {({ isSubmitting, isValid }) => (
                <AdminBox icon={faScroll} title={'Install Script'} noPadding>
                    <div css={tw`relative pb-4`}>
                        <SpinnerOverlay visible={isSubmitting} />

                        <Form>
                            <Editor
                                className="mb-4"
                                childClassName={tw`h-96`}
                                initialContent={egg.scriptInstall || ''}
                                fetchContent={value => {
                                    fetchFileContent = value;
                                }}
                                language={LanguageDescription.of({
                                    name: 'shell',
                                    support: new LanguageSupport(StreamLanguage.define(shell)),
                                })}
                            />

                            <div css={tw`mx-6 mb-4`}>
                                <div css={tw`grid grid-cols-3 gap-x-8 gap-y-6`}>
                                    <Field
                                        id={'scriptContainer'}
                                        name={'scriptContainer'}
                                        label={'Install Container'}
                                        type={'text'}
                                        description={'The Docker image to use for running this installation script.'}
                                    />

                                    <Field
                                        id={'scriptEntry'}
                                        name={'scriptEntry'}
                                        label={'Install Entrypoint'}
                                        type={'text'}
                                        description={
                                            'The command that should be used to run this script inside of the installation container.'
                                        }
                                    />
                                </div>
                            </div>

                            <div css={tw`flex flex-row border-t border-neutral-600`}>
                                <Button type="submit" css={tw`ml-auto mr-6 mt-4`} disabled={isSubmitting || !isValid}>
                                    Save Changes
                                </Button>
                            </div>
                        </Form>
                    </div>
                </AdminBox>
            )}
        </Formik>
    );
}
