import CopyOnClick from '@/components/elements/CopyOnClick';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import React, { createContext, useContext, useEffect, useState } from 'react';
import { useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Nest } from '@/api/admin/nests/getNests';
import getNest from '@/api/admin/nests/getNest';
import updateNest from '@/api/admin/nests/updateNest';
import { object, string } from 'yup';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import { Form, Formik, FormikHelpers } from 'formik';
import AdminBox from '@/components/admin/AdminBox';

interface ctx {
    nest: Nest | undefined;
    setNest: (value: Nest | undefined) => void;
}

export const Context = createContext<ctx>({ nest: undefined, setNest: () => 1 });

interface Values {
    name: string;
    description: string | null;
}

const EditInformationContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const { nest, setNest } = useContext(Context);

    if (nest === undefined) {
        return (
            <></>
        );
    }

    const submit = ({ name, description }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('nest');

        updateNest(nest.id, name, description || undefined)
            .then(() => setNest({ ...nest, name, description }))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'nest', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                name: nest.name,
                description: nest.description,
            }}
            validationSchema={object().shape({
                name: string().required().min(1),
                description: string().max(255, ''),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <AdminBox title={'Edit Nest'} css={tw`flex-1 self-start w-full relative mr-4`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div>
                                    <Field
                                        id={'name'}
                                        name={'name'}
                                        label={'Name'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'description'}
                                        name={'description'}
                                        label={'Description'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mt-6 text-right`}>
                                    <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                        Save
                                    </Button>
                                </div>
                            </Form>
                        </AdminBox>
                    </React.Fragment>
                )
            }
        </Formik>
    );
};

const ViewDetailsContainer = () => {
    const { nest } = useContext(Context);

    if (nest === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Nest Details'} css={tw`flex-1 w-full relative ml-4`}>
            <div>
                <div>
                    <div>
                        <Label>ID</Label>
                        <CopyOnClick text={nest.id.toString()}>
                            <Input
                                type={'text'}
                                value={nest.id}
                                readOnly
                            />
                        </CopyOnClick>
                    </div>

                    <div css={tw`mt-6`}>
                        <Label>UUID</Label>
                        <CopyOnClick text={nest.uuid}>
                            <Input
                                type={'text'}
                                value={nest.uuid}
                                readOnly
                            />
                        </CopyOnClick>
                    </div>

                    <div css={tw`mt-6 mb-2`}>
                        <Label>Author</Label>
                        <CopyOnClick text={nest.author}>
                            <Input
                                type={'text'}
                                value={nest.author}
                                readOnly
                            />
                        </CopyOnClick>
                    </div>
                </div>
            </div>
        </AdminBox>
    );
};

const NestEditContainer = () => {
    const match = useRouteMatch<{ nestId?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const { nest, setNest } = useContext(Context);

    useEffect(() => {
        clearFlashes('nest');

        getNest(Number(match.params?.nestId), [ 'eggs' ])
            .then(nest => setNest(nest))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'nest', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || nest === undefined) {
        return (
            <AdminContentBlock title={'Nests'}>
                <FlashMessageRender byKey={'nest'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Nests - ' + nest.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{nest.name}</h2>
                    <p css={tw`text-base text-neutral-400`}>{nest.description}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'nest'} css={tw`mb-4`}/>

            <div css={tw`flex flex-row`}>
                <EditInformationContainer/>
                <ViewDetailsContainer/>
            </div>
        </AdminContentBlock>
    );
};

export default () => {
    const [ nest, setNest ] = useState<Nest | undefined>(undefined);

    return (
        <Context.Provider value={{ nest, setNest }}>
            <NestEditContainer/>
        </Context.Provider>
    );
};
