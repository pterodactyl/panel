import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useHistory, useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Location } from '@/api/admin/locations/getLocations';
import getLocation from '@/api/admin/locations/getLocation';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import { object, string } from 'yup';
import AdminBox from '@/components/admin/AdminBox';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers } from 'formik';
import updateLocation from '@/api/admin/locations/updateLocation';
import LocationDeleteButton from '@/components/admin/locations/LocationDeleteButton';

interface ctx {
    location: Location | undefined;
    setLocation: Action<ctx, Location | undefined>;
}

export const Context = createContextStore<ctx>({
    location: undefined,

    setLocation: action((state, payload) => {
        state.location = payload;
    }),
});

interface Values {
    short: string;
    long: string;
}

const EditInformationContainer = () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const location = Context.useStoreState(state => state.location);
    const setLocation = Context.useStoreActions(actions => actions.setLocation);

    if (location === undefined) {
        return (
            <></>
        );
    }

    const submit = ({ short, long }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('location');

        updateLocation(location.id, short, long)
            .then(() => setLocation({ ...location, short, long }))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'location', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                short: location.short,
                long: location.long || '',
            }}
            validationSchema={object().shape({
                short: string().required().min(1),
                long: string().max(255, ''),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <AdminBox title={'Edit Location'} css={tw`relative`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div>
                                    <Field
                                        id={'short'}
                                        name={'short'}
                                        label={'Short Name'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'long'}
                                        name={'long'}
                                        label={'Long Name'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`w-full flex flex-row items-center mt-6`}>
                                    <div css={tw`flex`}>
                                        <LocationDeleteButton
                                            locationId={location.id}
                                            onDeleted={() => history.push('/admin/locations')}
                                        />
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

const LocationEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const location = Context.useStoreState(state => state.location);
    const setLocation = Context.useStoreActions(actions => actions.setLocation);

    useEffect(() => {
        clearFlashes('location');

        getLocation(Number(match.params?.id))
            .then(location => setLocation(location))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'location', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || location === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'location'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Location - ' + location.short}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{location.short}</h2>
                    {
                        (location.long || '').length < 1 ?
                            <p css={tw`text-base text-neutral-400`}>
                                <span css={tw`italic`}>No long name</span>
                            </p>
                            :
                            <p css={tw`text-base text-neutral-400`}>{location.long}</p>
                    }
                </div>
            </div>

            <FlashMessageRender byKey={'location'} css={tw`mb-4`}/>

            <EditInformationContainer/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <LocationEditContainer/>
        </Context.Provider>
    );
};
