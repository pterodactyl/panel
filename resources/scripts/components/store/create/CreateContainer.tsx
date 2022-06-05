import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from 'easy-peasy';
import { number, object, string } from 'yup';
import { megabytesToHuman } from '@/helpers';
import styled from 'styled-components/macro';
import Field from '@/components/elements/Field';
import Select from '@/components/elements/Select';
import Button from '@/components/elements/Button';
import createServer from '@/api/store/createServer';
import { Form, Formik } from 'formik';
import InputSpinner from '@/components/elements/InputSpinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import PageContentBlock from '@/components/elements/PageContentBlock';

const Container = styled.div`
  ${tw`flex flex-wrap`};

  & > div {
    ${tw`w-full`};

    ${breakpoint('sm')`
      width: calc(50% - 1rem);
    `}

    ${breakpoint('md')`
      ${tw`w-auto flex-1`};
    `}
  }
`;

interface CreateValues {
    name: string;
    description: string | null;
    cpu: number;
    memory: number;
    disk: number;
    ports: number;
    backups: number | null;
    databases: number | null;
}

export default () => {
    const user = useStoreState(state => state.user.data!);
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const [ isSubmit, setSubmit ] = useState(false);
    const [ loading, setLoading ] = useState(false);

    const submit = (values: CreateValues) => {
        setLoading(true);
        clearFlashes('store:create');
        setSubmit(true);

        createServer(values)
            .catch(error => {
                setSubmit(false);
                clearAndAddHttpError({ key: 'store:create', error });
            })
            .then(() => {
                setSubmit(false);
                setLoading(false);
                clearFlashes('store:create');
                // @ts-ignore
                window.location = '/';
            })
            .then(() => addFlash({
                type: 'success',
                key: 'store:create',
                message: 'Your server has been deployed and is now installing.',
            }));
    };

    return (
        <PageContentBlock title={'Create a server'} showFlashKey={'store:create'}>
            <Formik
                onSubmit={submit}
                initialValues={{
                    name: `${user.username}'s server`,
                    description: 'Write a short description here.',
                    cpu: user.store.cpu,
                    memory: user.store.memory / 1024,
                    disk: user.store.disk / 1024,
                    ports: user.store.ports,
                    backups: user.store.backups,
                    databases: user.store.databases,
                }}
                validationSchema={object().shape({
                    name: string().required().min(3),
                    description: string().optional().min(3).max(191),
                    cpu: number().required().min(50).max(user.store.cpu),
                    memory: number().required().min(1).max(user.store.memory / 1024),
                    disk: number().required().min(1).max(user.store.disk / 1024),
                    ports: number().required().min(1).max(user.store.ports),
                    backups: number().optional().max(user.store.backups),
                    databases: number().optional().max(user.store.databases),
                })}
            >
                <Form>
                    <h1 css={tw`text-5xl`}>Basic Details</h1>
                    <h3 css={tw`text-2xl ml-2 text-neutral-500`}>Set the basic fields for your new server.</h3>
                    <Container css={tw`lg:grid lg:grid-cols-2 my-10 gap-4`}>
                        <TitledGreyBox title={'Server name'} css={tw`mt-8 sm:mt-0`}>
                            <Field name={'name'} />
                            <p css={tw`mt-1 text-xs`}>Assign a name to your server for use in the Panel.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>Character limits: <code>a-z A-Z 0-9 _ - .</code> and <code>[Space]</code>.</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server description'} css={tw`mt-8 sm:mt-0 `}>
                            <Field name={'description'} />
                            <p css={tw`mt-1 text-xs`}>Set a description for your server.</p>
                            <p css={tw`mt-1 text-xs text-red-400`}>* Optional</p>
                        </TitledGreyBox>
                    </Container>
                    <h1 css={tw`text-5xl`}>Resource Limits</h1>
                    <h3 css={tw`text-2xl ml-2 text-neutral-500`}>Set specific limits for CPU, RAM and more.</h3>
                    <Container css={tw`lg:grid lg:grid-cols-3 my-10 gap-4`}>
                        <TitledGreyBox title={'Server CPU limit'} css={tw`mt-8 sm:mt-0`}>
                            <Field name={'cpu'} />
                            <p css={tw`mt-1 text-xs`}>Assign a limit for usable CPU.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.cpu}% available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server RAM limit'} css={tw`mt-8 sm:mt-0 `}>
                            <Field name={'memory'} />
                            <p css={tw`mt-1 text-xs`}>Assign a limit for usable RAM.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{megabytesToHuman(user.store.memory)} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server Storage limit'} css={tw`mt-8 sm:mt-0 `}>
                            <Field name={'disk'} />
                            <p css={tw`mt-1 text-xs`}>Assign a limit for usable storage.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{megabytesToHuman(user.store.disk)} available</p>
                        </TitledGreyBox>
                    </Container>
                    <h1 css={tw`text-5xl`}>Feature Limits</h1>
                    <h3 css={tw`text-2xl ml-2 text-neutral-500`}>Add databases, allocations and ports to your server.</h3>
                    <Container css={tw`lg:grid lg:grid-cols-3 my-10 gap-4`}>
                        <TitledGreyBox title={'Server allocations'} css={tw`mt-8 sm:mt-0`}>
                            <Field name={'ports'} />
                            <p css={tw`mt-1 text-xs`}>Assign a number of ports to your server.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.ports} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server backups'} css={tw`mt-8 sm:mt-0 `}>
                            <Field name={'backups'} />
                            <p css={tw`mt-1 text-xs`}>Assign a number of backups to your server.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.backups} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server databases'} css={tw`mt-8 sm:mt-0 `}>
                            <Field name={'databases'} />
                            <p css={tw`mt-1 text-xs`}>Assign a number of databases to your server.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.databases} available</p>
                        </TitledGreyBox>
                    </Container>
                    <h1 css={tw`text-5xl`}>Server Type</h1>
                    <h3 css={tw`text-2xl ml-2 text-neutral-500`}>Choose a server distribution to use.</h3>
                    <Container css={tw`lg:grid lg:grid-cols-2 my-10 gap-4`}>
                        <TitledGreyBox title={'Server Egg'} css={tw`mt-8 sm:mt-0`}>
                            <Select name={'egg'}>
                                <option key={'egg:paper'}>Minecraft Paper</option>
                            </Select>
                            <p css={tw`mt-1 text-xs`}>Choose what game you want to run on your server.</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Docker Image'} css={tw`mt-8 sm:mt-0 `}>
                            <Select name={'image'}>
                                <option key={'image:java_17'}>Java 17</option>
                            </Select>
                            <p css={tw`mt-1 text-xs`}>Choose what Docker image you&apos;d like to use.</p>
                        </TitledGreyBox>
                    </Container>
                    <InputSpinner visible={loading}>
                        <TitledGreyBox title={'Create server instance'} css={tw`mt-8 sm:mt-0 `}>
                            <div css={tw`flex justify-end text-right`}>
                                <Button type={'submit'} disabled={isSubmit}>
                                    Create
                                </Button>
                            </div>
                        </TitledGreyBox>
                    </InputSpinner>
                </Form>
            </Formik>
        </PageContentBlock>
    );
};
