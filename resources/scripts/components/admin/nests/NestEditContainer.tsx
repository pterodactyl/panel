import React, { useEffect, useState } from 'react';
import { NavLink, useRouteMatch } from 'react-router-dom';
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
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Form, Formik, FormikHelpers } from 'formik';
import AdminBox from '@/components/admin/AdminBox';
import AdminCheckbox from '@/components/admin/AdminCheckbox';
import AdminTable, { ContentWrapper, NoItems, TableBody, TableHead, TableHeader, TableRow } from '@/components/admin/AdminTable';
import CopyOnClick from '@/components/elements/CopyOnClick';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';

interface ctx {
    nest: Nest | undefined;
    setNest: Action<ctx, Nest | undefined>;

    selectedEggs: number[];

    setSelectedEggs: Action<ctx, number[]>;
    appendSelectedEggs: Action<ctx, number>;
    removeSelectedEggs: Action<ctx, number>;
}

export const Context = createContextStore<ctx>({
    nest: undefined,

    setNest: action((state, payload) => {
        state.nest = payload;
    }),

    selectedEggs: [],

    setSelectedEggs: action((state, payload) => {
        state.selectedEggs = payload;
    }),

    appendSelectedEggs: action((state, payload) => {
        state.selectedEggs = state.selectedEggs.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedEggs: action((state, payload) => {
        state.selectedEggs = state.selectedEggs.filter(id => id !== payload);
    }),
});

interface Values {
    name: string;
    description: string;
}

const EditInformationContainer = () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const nest = Context.useStoreState(state => state.nest);
    const setNest = Context.useStoreActions(actions => actions.setNest);

    if (nest === undefined) {
        return (
            <></>
        );
    }

    const submit = ({ name, description }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('nest');

        updateNest(nest.id, name, description)
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
                description: nest.description || '',
            }}
            validationSchema={object().shape({
                name: string().required().min(1),
                description: string().max(255, ''),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <AdminBox title={'Edit Nest'} css={tw`flex-1 self-start w-full relative mb-8 lg:mb-0 mr-0 lg:mr-4`}>
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

                                <div css={tw`w-full flex flex-row items-center mt-6`}>
                                    <div css={tw`flex`}>
                                        {/* <NestDeleteButton */}
                                        {/*     nestId={nest.id} */}
                                        {/*     onDeleted={() => history.push('/admin/nests')} */}
                                        {/* /> */}
                                    </div>

                                    <div css={tw`flex ml-auto`}>
                                        <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                            Save
                                        </Button>
                                    </div>
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
    const nest = Context.useStoreState(state => state.nest);

    if (nest === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Nest Details'} css={tw`flex-1 w-full relative ml-0 lg:ml-4`}>
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

const RowCheckbox = ({ id }: { id: number }) => {
    const isChecked = Context.useStoreState(state => state.selectedEggs.indexOf(id) >= 0);
    const appendSelectedEggs = Context.useStoreActions(actions => actions.appendSelectedEggs);
    const removeSelectedEggs = Context.useStoreActions(actions => actions.removeSelectedEggs);

    return (
        <AdminCheckbox
            name={id.toString()}
            checked={isChecked}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                if (e.currentTarget.checked) {
                    appendSelectedEggs(id);
                } else {
                    removeSelectedEggs(id);
                }
            }}
        />
    );
};

const NestEditContainer = () => {
    const match = useRouteMatch<{ nestId?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const nest = Context.useStoreState(state => state.nest);
    const setNest = Context.useStoreActions(actions => actions.setNest);

    const setSelectedEggs = Context.useStoreActions(actions => actions.setSelectedEggs);
    const selectedEggsLength = Context.useStoreState(state => state.selectedEggs.length);

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
            <AdminContentBlock>
                <FlashMessageRender byKey={'nest'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    const length = nest.relations.eggs?.length || 0;

    const onSelectAllClick = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSelectedEggs(e.currentTarget.checked ? (nest.relations.eggs?.map(egg => egg.id) || []) : []);
    };

    return (
        <AdminContentBlock title={'Nests - ' + nest.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{nest.name}</h2>
                    {
                        (nest.description || '').length < 1 ?
                            <p css={tw`text-base text-neutral-400`}>
                                <span css={tw`italic`}>No description</span>
                            </p>
                            :
                            <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>{nest.description}</p>
                    }
                </div>
            </div>

            <FlashMessageRender byKey={'nest'} css={tw`mb-4`}/>

            <div css={tw`flex flex-col lg:flex-row mb-8`}>
                <EditInformationContainer/>
                <ViewDetailsContainer/>
            </div>

            <AdminTable>
                { length < 1 ?
                    <NoItems/>
                    :
                    <ContentWrapper
                        checked={selectedEggsLength === (length === 0 ? -1 : length)}
                        onSelectAllClick={onSelectAllClick}
                    >
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader name={'ID'}/>
                                    <TableHeader name={'Name'}/>
                                    <TableHeader name={'Description'}/>
                                </TableHead>

                                <TableBody>
                                    {
                                        nest.relations.eggs?.map(egg => (
                                            <TableRow key={egg.id}>
                                                <td css={tw`pl-6`}>
                                                    <RowCheckbox id={egg.id}/>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <CopyOnClick text={egg.id.toString()}>
                                                        <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>{egg.id}</code>
                                                    </CopyOnClick>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink to={`${match.url}/eggs/${egg.id}`} css={tw`text-primary-400 hover:text-primary-300`}>
                                                        {egg.name}
                                                    </NavLink>
                                                </td>

                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>{egg.description}</td>
                                            </TableRow>
                                        ))
                                    }
                                </TableBody>
                            </table>
                        </div>
                    </ContentWrapper>
                }
            </AdminTable>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <NestEditContainer/>
        </Context.Provider>
    );
};
