import { useEggFromRoute } from '@/api/admin/egg';
import updateEgg from '@/api/admin/eggs/updateEgg';
import Field from '@/components/elements/Field';
import useFlash from '@/plugins/useFlash';
// import { shell } from '@codemirror/legacy-modes/mode/shell';
import { faScroll } from '@fortawesome/free-solid-svg-icons';
import { Form, Formik, FormikHelpers } from 'formik';
import tw from 'twin.macro';
import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';
// import Editor from '@/components/elements/Editor';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

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
                            {/*<Editor*/}
                            {/*    overrides={tw`h-96 mb-4`}*/}
                            {/*    initialContent={egg.scriptInstall || ''}*/}
                            {/*    mode={shell}*/}
                            {/*    fetchContent={value => {*/}
                            {/*        fetchFileContent = value;*/}
                            {/*    }}*/}
                            {/*/>*/}

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
                                <Button
                                    type={'submit'}
                                    size={'small'}
                                    css={tw`ml-auto mr-6 mt-4`}
                                    disabled={isSubmitting || !isValid}
                                >
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
