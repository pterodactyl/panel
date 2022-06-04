import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import { Form, Formik } from 'formik';
import { useStoreState } from 'easy-peasy';
import { number, object, string } from 'yup';
import { megabytesToHuman } from '@/helpers';
import styled from 'styled-components/macro';
import Field from '@/components/elements/Field';
import Button from '@/components/elements/Button';
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

export default () => {
    const user = useStoreState(state => state.user.data!);

    const submit = () => { /* TODO: Post server creation request */ };

    return (
        <PageContentBlock title={'Create a server'}>
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
                    description: string().optional().max(191),
                    cpu: number().required().min(50).max(user.store.cpu),
                    memory: number().required().min(1).max(user.store.memory / 1024),
                    disk: number().required().min(1).max(user.store.disk / 1024),
                    ports: number().required().min(1).max(user.store.ports),
                    backups: number().required().min(1).max(user.store.backups),
                    databases: number().required().min(1).max(user.store.databases),
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
                        <TitledGreyBox title={'Server description'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                            <Field name={'description'} />
                            <p css={tw`mt-1 text-xs`}>Set a description for your server.</p>
                            <p css={tw`mt-1 text-xs text-red-400`}>* Optional</p>
                        </TitledGreyBox>
                    </Container>
                    <h1 css={tw`text-5xl`}>Resource Limits</h1>
                    <h3 css={tw`text-2xl ml-2 text-neutral-500`}>Set specific limits for CPU, RAM and more.</h3>
                    <Container css={tw`lg:grid lg:grid-cols-2 my-10 gap-4`}>
                        <TitledGreyBox title={'Server CPU limit'} css={tw`mt-8 sm:mt-0`}>
                            <Field name={'cpu'} />
                            <p css={tw`mt-1 text-xs`}>Assign a limit for usable CPU.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.cpu}% available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server RAM limit'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                            <Field name={'memory'} />
                            <p css={tw`mt-1 text-xs`}>Assign a limit for usable RAM.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{megabytesToHuman(user.store.memory)} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server Storage limit'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                            <Field name={'disk'} />
                            <p css={tw`mt-1 text-xs`}>Assign a limit for usable storage.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{megabytesToHuman(user.store.disk)} available</p>
                        </TitledGreyBox>
                    </Container>
                    <h1 css={tw`text-5xl`}>Feature limits</h1>
                    <h3 css={tw`text-2xl ml-2 text-neutral-500`}>Add databases, allocations and ports to your server.</h3>
                    <Container css={tw`lg:grid lg:grid-cols-2 my-10 gap-4`}>
                        <TitledGreyBox title={'Server allocations'} css={tw`mt-8 sm:mt-0`}>
                            <Field name={'ports'} />
                            <p css={tw`mt-1 text-xs`}>Assign a number of ports to your server.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.ports} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server backups'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                            <Field name={'backups'} />
                            <p css={tw`mt-1 text-xs`}>Assign a number of backups to your server.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.backups} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server databases'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                            <Field name={'databases'} />
                            <p css={tw`mt-1 text-xs`}>Assign a number of databases to your server.</p>
                            <p css={tw`mt-1 text-xs text-neutral-400`}>{user.store.databases} available</p>
                        </TitledGreyBox>
                    </Container>
                    <TitledGreyBox title={'Create server instance'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                        <div css={tw`flex justify-end text-right`}>
                            <Button>Create</Button>
                        </div>
                    </TitledGreyBox>
                </Form>
            </Formik>
        </PageContentBlock>
    );
};
