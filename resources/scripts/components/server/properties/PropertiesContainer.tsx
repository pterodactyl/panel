import React, { useEffect, useRef, useState } from 'react';
import { ServerContext } from '@/state/server';
import useSWR from 'swr';
import loadProperties, { PropertiesResponse } from '@/api/server/properties/loadProperties';
import useFlash from '@/plugins/useFlash';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@/components/elements/Spinner';
import { Form, Formik, FormikHelpers, FormikProps } from 'formik';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import saveProperties from '@/api/server/properties/saveProperties';
import PropertiesRow from '@/components/server/properties/PropertiesRow';
import Field from '@/components/elements/Field';

interface Search {
    query: string;
}

export default () => {
    let formikRef = useRef<FormikProps<Search>>();
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const [search, setSearch] = useState('');

    const { data, error, mutate } = useSWR<PropertiesResponse>([uuid, '/properties'], (uuid) => loadProperties(uuid));
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();

    const onSave = (values: any[], { setSubmitting }: FormikHelpers<any[]>) => {
        clearFlashes('server:properties');

        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        const postValues: any[] = {};

        for (const key in values) {
            if (data!.properties.filter((item) => item.key === key)[0].type === 'switch') {
                postValues[key] = values[key] ? 'true' : 'false';
            } else {
                postValues[key] = values[key];
            }
        }

        saveProperties(uuid, postValues)
            .then(() => {
                addFlash({
                    key: 'server:properties',
                    type: 'success',
                    title: 'success',
                    message: 'Properties are saved.',
                });
                mutate();
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'server:properties', error });
            })
            .finally(() => {
                setSubmitting(false);
            });
    };

    const onSearch = ({ query }: Search, { setSubmitting }: FormikHelpers<Search>) => {
        setSearch(query);
        setSubmitting(false);
    };

    const searchFilter = (item: any) => {
        if (search === '') {
            return true;
        }

        return item.key.toLowerCase().includes(search.toLowerCase());
    };

    const switchCount = () => {
        return Math.ceil(
            data!.properties.filter((item) => item.type === 'switch').filter((item) => searchFilter(item)).length / 2
        );
    };

    useEffect(() => {
        if (!error) {
            clearFlashes('server:properties');
        } else {
            clearAndAddHttpError({ key: 'server:properties', error });
        }
    }, [error]);

    return (
        <ServerContentBlock title={'Server Properties'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full`}>
                <FlashMessageRender byKey={'server:properties'} css={tw`mb-4`} />
                {!data ? (
                    <Spinner size={'large'} centered />
                ) : (
                    <>
                        <div css={tw`flex flex-wrap pb-4`}>
                            <div css={tw`w-full lg:w-2/3`}>
                                <Formik onSubmit={onSearch} initialValues={{ query: '' }}>
                                    {({ handleChange }) => (
                                        <Form>
                                            <Field
                                                placeholder={'Search for properties name...'}
                                                name={'query'}
                                                onChange={(e) => {
                                                    handleChange(e);
                                                    setSearch(e.target.value);
                                                }}
                                            />
                                        </Form>
                                    )}
                                </Formik>
                            </div>
                            <div css={tw`w-full lg:w-1/3 mt-1 text-right lg:pt-0 pt-2`}>
                                <Button
                                    type={'button'}
                                    color={'grey'}
                                    css={tw`mr-2`}
                                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                                    // @ts-ignore
                                    onClick={() => formikRef.resetForm()}
                                >
                                    Reset
                                </Button>
                                {/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */}
                                {/* @ts-ignore */}
                                <Button type={'submit'} onClick={() => formikRef.submitForm()}>
                                    Save
                                </Button>
                            </div>
                        </div>
                        <Formik
                            onSubmit={onSave}
                            initialValues={data.initial}
                            innerRef={(ref: any) => (formikRef = ref)}
                        >
                            {({ isSubmitting, resetForm }) => (
                                <Form>
                                    <SpinnerOverlay visible={isSubmitting} />
                                    <div css={tw`flex flex-wrap`}>
                                        <div css={tw`w-full lg:pr-2 lg:w-1/2`}>
                                            <div css={tw`flex flex-wrap`}>
                                                {data.properties
                                                    .filter((item) => item.type !== 'switch')
                                                    .slice(0, switchCount())
                                                    .filter((item) => searchFilter(item))
                                                    .map((item, key) => (
                                                        <div key={key} css={tw`w-full mb-4`}>
                                                            <PropertiesRow item={item} />
                                                        </div>
                                                    ))}
                                            </div>
                                        </div>
                                        <div css={tw`w-full lg:w-1/2`}>
                                            <div css={tw`flex flex-wrap`}>
                                                {data.properties
                                                    .filter((item) => item.type === 'switch')
                                                    .filter((item) => searchFilter(item))
                                                    .map((item, key) => (
                                                        <div key={key} css={tw`w-full sm:w-1/2 sm:px-1 mb-4`}>
                                                            <PropertiesRow item={item} />
                                                        </div>
                                                    ))}
                                            </div>
                                        </div>
                                        {data.properties
                                            .filter((item) => item.type !== 'switch')
                                            .slice(switchCount())
                                            .filter((item) => searchFilter(item))
                                            .map((item, key) => (
                                                <div key={key} css={tw`w-full lg:w-1/2 lg:px-1 mb-4`}>
                                                    <PropertiesRow item={item} />
                                                </div>
                                            ))}
                                    </div>
                                    <div css={tw`text-right`}>
                                        <Button
                                            type={'button'}
                                            color={'grey'}
                                            css={tw`mr-2`}
                                            onClick={() => resetForm()}
                                        >
                                            Reset
                                        </Button>
                                        <Button type={'submit'}>Save</Button>
                                    </div>
                                </Form>
                            )}
                        </Formik>
                    </>
                )}
            </div>
        </ServerContentBlock>
    );
};
