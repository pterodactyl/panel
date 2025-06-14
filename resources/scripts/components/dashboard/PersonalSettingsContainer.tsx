import * as React from 'react';
import ContentBox from '@/components/elements/ContentBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import { Field as FormikField, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import useFlash from '@/plugins/useFlash';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useEffect, useState } from 'react';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import useSWR from 'swr';
import getPersonalSettings from '@/api/account/getPersonalSettings';
import Spinner from '@/components/elements/Spinner';
import Label from '@/components/elements/Label';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import Select from '@/components/elements/Select';
import savePersonalSettings from '@/api/account/savePersonalSettings';

const Container = styled.div`
    ${tw`flex flex-wrap`};

    & > div {
        ${tw`w-full`};

        ${breakpoint('md')`
            width: calc(50% - 1rem);
        `}

        ${breakpoint('xl')`
            ${tw`w-auto flex-1`};
        `}
    }
`;

interface UpdateValues {
    country: string;
    address: string;
    zipcode: string;
}

export interface PersonalSettingsResponse {
    country: string;
    address: string;
    zipcode: string;
    countries: any[];
}

export default () => {
    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();

    const [ isSubmit, setSubmit ] = useState(false);

    const { data, error } = useSWR<PersonalSettingsResponse>([ '/account/personal' ], () => getPersonalSettings());

    useEffect(() => {
        if (!error) {
            clearFlashes('account:personal');
        } else {
            clearAndAddHttpError({ key: 'account:personal', error });
        }
    }, [ error ]);

    const submit = ({ country, address, zipcode }: UpdateValues, { setSubmitting }: FormikHelpers<UpdateValues>) => {
        clearFlashes('account:personal');
        setSubmitting(false);
        setSubmit(true);

        savePersonalSettings(country, address, zipcode).then(() => {
            setSubmit(false);
            clearFlashes('account:personal');
            addFlash({ key: 'account:personal', message: 'You\'ve successfully updated your details.', type: 'success', title: 'Success' });
        }).catch(error => {
            setSubmit(false);
            clearAndAddHttpError({ key: 'account:personal', error });
        });
    };

    return (
        <PageContentBlock title={'Personal Settings'}>
            <Container css={[ tw`mb-10 mt-10` ]}>
                <ContentBox css={tw`mt-8 md:mt-0 md:ml-8`} title={'Update Personal Settings'} showFlashes={'account:personal'}>
                    {!data ?
                        <div css={tw`w-full`}>
                            <Spinner size={'large'} centered />
                        </div>
                        :
                        <>
                            <Formik
                                onSubmit={submit}
                                initialValues={{ 
									country: data.country ?? '',
									address: data.address ?? '',
									zipcode: data.zipcode ?? 'US',
								}}
                                validationSchema={object().shape({
                                    country: string().required(),
                                    address: string().required().min(1).max(191),
                                    zipcode: string().required(),
                                })}
                            >
                                <React.Fragment>
                                    <SpinnerOverlay size={'large'} visible={isSubmit} />
                                    <Form>
                                        <div css={tw`flex flex-wrap`}>
                                            <div css={tw`mb-6 w-full lg:w-1/2 lg:pr-4`}>
                                                <Label>Country</Label>
                                                <FormikFieldWrapper name={'Country'}>
                                                    <FormikField as={Select} name={'country'}>
                                                        {data.countries.map((item, key) => (
                                                            <option key={key} value={item.code}>{item.name}</option>
                                                        ))}
                                                    </FormikField>
                                                </FormikFieldWrapper>
                                            </div>
                                            <div css={tw`mb-6 w-full lg:w-1/2`}>
                                                <Field
                                                    name={'zipcode'}
                                                    label={'Zip Code'}
                                                />
                                            </div>
                                            <div css={tw`mb-6 w-full`}>
                                                <Field
                                                    name={'address'}
                                                    label={'Address'}
                                                />
                                            </div>
                                        </div>
                                        <div css={tw`flex justify-end`}>
                                            <Button type={'submit'} disabled={isSubmit}>Save</Button>
                                        </div>
                                    </Form>
                                </React.Fragment>
                            </Formik>
                        </>
                    }
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};
