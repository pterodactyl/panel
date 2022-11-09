import * as Icon from 'react-feather';
import { Form, Formik } from 'formik';
import { Link } from 'react-router-dom';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from 'easy-peasy';
import { number, object, string } from 'yup';
import { megabytesToHuman } from '@/helpers';
import Field from '@/components/elements/Field';
import Select from '@/components/elements/Select';
import { Egg, getEggs } from '@/api/store/getEggs';
import createServer from '@/api/store/createServer';
import Spinner from '@/components/elements/Spinner';
import { getNodes, Node } from '@/api/store/getNodes';
import { getNests, Nest } from '@/api/store/getNests';
import { Button } from '@/components/elements/button';
import InputSpinner from '@/components/elements/InputSpinner';
import StoreError from '@/components/elements/store/StoreError';
import React, { ChangeEvent, useEffect, useState } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import StoreContainer from '@/components/elements/StoreContainer';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';
import {
    faArchive,
    faCube,
    faDatabase,
    faEgg,
    faHdd,
    faLayerGroup,
    faList,
    faMemory,
    faMicrochip,
    faNetworkWired,
    faStickyNote,
} from '@fortawesome/free-solid-svg-icons';

interface CreateValues {
    name: string;
    description: string | null;
    cpu: number;
    memory: number;
    disk: number;
    ports: number;
    backups: number | null;
    databases: number | null;

    egg: number;
    nest: number;
    node: number;
}

export default () => {
    const limit = useStoreState((state) => state.storefront.data!.limit);
    const user = useStoreState((state) => state.user.data!);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [loading, setLoading] = useState(false);
    const [resources, setResources] = useState<Resources>();
    const [egg, setEgg] = useState<number>(0);
    const [eggs, setEggs] = useState<Egg[]>();
    const [nest, setNest] = useState<number>(0);
    const [nests, setNests] = useState<Nest[]>();
    const [node, setNode] = useState<number>(0);
    const [nodes, setNodes] = useState<Node[]>();

    useEffect(() => {
        clearFlashes();

        getResources().then((resources) => setResources(resources));

        getNodes().then((nodes) => {
            setNode(nodes[0].id);
            setNodes(nodes);
        });

        getNests().then((nests) => {
            setNest(nests[0].id);
            setNests(nests);
        });

        getEggs().then((eggs) => {
            setEgg(eggs[0].id);
            setEggs(eggs);
        });
    }, []);

    const changeNest = (e: ChangeEvent<HTMLSelectElement>) => {
        setNest(parseInt(e.target.value));

        getEggs(parseInt(e.target.value)).then((eggs) => {
            setEggs(eggs);
            setEgg(eggs[0].id);
        });
    };

    const submit = (values: CreateValues) => {
        setLoading(true);
        clearFlashes('store:create');

        createServer(values, egg, nest, node)
            .then((data) => {
                if (!data.id) return;

                setLoading(false);
                clearFlashes('store:create');
                // @ts-expect-error this is valid
                window.location = `/server/${data.id}`;
            })
            .catch((error) => {
                setLoading(false);
                clearAndAddHttpError({ key: 'store:create', error });
            });
    };

    if (!resources) return <Spinner size={'large'} centered />;

    if (!nodes) {
        return (
            <StoreError
                message={'No nodes are available for deployment. Try again later.'}
                admin={'Ensure you have at least one node that can be deployed to.'}
            />
        );
    }

    if (!nests || !eggs) {
        return (
            <StoreError
                message={'No server types are available for deployment. Try again later.'}
                admin={'Ensure you have at least one egg which is in a public nest.'}
            />
        );
    }

    return (
        <PageContentBlock title={'Create a server'} showFlashKey={'store:create'}>
            <Formik
                onSubmit={submit}
                initialValues={{
                    name: `${user.username}'s server`,
                    description: 'Write a short description here.',
                    cpu: resources.cpu,
                    memory: resources.memory,
                    disk: resources.disk,
                    ports: resources.ports,
                    backups: resources.backups,
                    databases: resources.databases,
                    nest: 1,
                    egg: 1,
                    node: 1,
                }}
                validationSchema={object().shape({
                    name: string().required().min(3),
                    description: string().optional().min(3).max(191),

                    cpu: number().required().min(25).max(resources.cpu).max(limit.cpu),
                    memory: number().required().min(256).max(resources.memory).max(limit.memory),
                    disk: number().required().min(256).max(resources.disk).max(limit.disk),

                    ports: number().required().min(1).max(resources.ports).max(limit.port),
                    backups: number().optional().max(resources.backups).max(limit.backup),
                    databases: number().optional().max(resources.databases).max(limit.database),

                    node: number().required().default(node),
                    nest: number().required().default(nest),
                    egg: number().required().default(egg),
                })}
            >
                <Form>
                    <div className={'my-10'}>
                        <Link to={'/store'}>
                            <Button.Text className={'w-full lg:w-1/6 m-2'}>
                                <Icon.ArrowLeft className={'mr-1'} />
                                Return to Storefront
                            </Button.Text>
                        </Link>
                        <Link to={'/store/resources'}>
                            <Button className={'w-full lg:w-1/6 m-2'}>
                                <Icon.ShoppingCart className={'mr-2'} />
                                Need more resources?
                            </Button>
                        </Link>
                    </div>
                    <h1 className={'j-left text-5xl'}>Basic Details</h1>
                    <h3 className={'j-left text-2xl text-neutral-500'}>Set the basic fields for your new server.</h3>
                    <StoreContainer className={'lg:grid lg:grid-cols-2 my-10 gap-4'}>
                        <TitledGreyBox title={'Server name'} icon={faStickyNote} className={'mt-8 sm:mt-0'}>
                            <Field name={'name'} />
                            <p className={'mt-1 text-xs'}>Assign a name to your server for use in the Panel.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>
                                Character limits: <code>a-z A-Z 0-9 _ - .</code> and <code>[Space]</code>.
                            </p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server description'} icon={faList} className={'mt-8 sm:mt-0'}>
                            <Field name={'description'} />
                            <p className={'mt-1 text-xs'}>Set a description for your server.</p>
                            <p className={'mt-1 text-xs text-yellow-400'}>* Optional</p>
                        </TitledGreyBox>
                    </StoreContainer>
                    <h1 className={'j-left text-5xl'}>Resource Limits</h1>
                    <h3 className={'j-left text-2xl text-neutral-500'}>Set specific limits for CPU, RAM and more.</h3>
                    <StoreContainer className={'lg:grid lg:grid-cols-3 my-10 gap-4'}>
                        <TitledGreyBox title={'Server CPU limit'} icon={faMicrochip} className={'mt-8 sm:mt-0'}>
                            <Field name={'cpu'} />
                            <p className={'mt-1 text-xs'}>Assign a limit for usable CPU.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>{resources.cpu}% available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server RAM limit'} icon={faMemory} className={'mt-8 sm:mt-0'}>
                            <div className={'relative'}>
                                <Field name={'memory'} />
                                <p className={'absolute text-sm top-1.5 right-2 bg-gray-700 p-2 rounded-lg'}>MB</p>
                            </div>
                            <p className={'mt-1 text-xs'}>Assign a limit for usable RAM.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>
                                {megabytesToHuman(resources.memory)} available
                            </p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server Storage limit'} icon={faHdd} className={'mt-8 sm:mt-0'}>
                            <div className={'relative'}>
                                <Field name={'disk'} />
                                <p className={'absolute text-sm top-1.5 right-2 bg-gray-700 p-2 rounded-lg'}>MB</p>
                            </div>
                            <p className={'mt-1 text-xs'}>Assign a limit for usable storage.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>{megabytesToHuman(resources.disk)} available</p>
                        </TitledGreyBox>
                    </StoreContainer>
                    <h1 className={'j-left text-5xl'}>Feature Limits</h1>
                    <h3 className={'j-left text-2xl text-neutral-500'}>
                        Add databases, allocations and ports to your server.
                    </h3>
                    <StoreContainer className={'lg:grid lg:grid-cols-3 my-10 gap-4'}>
                        <TitledGreyBox title={'Server allocations'} icon={faNetworkWired} className={'mt-8 sm:mt-0'}>
                            <Field name={'ports'} />
                            <p className={'mt-1 text-xs'}>Assign a number of ports to your server.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>{resources.ports} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server backups'} icon={faArchive} className={'mt-8 sm:mt-0'}>
                            <Field name={'backups'} />
                            <p className={'mt-1 text-xs'}>Assign a number of backups to your server.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>{resources.backups} available</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server databases'} icon={faDatabase} className={'mt-8 sm:mt-0'}>
                            <Field name={'databases'} />
                            <p className={'mt-1 text-xs'}>Assign a number of databases to your server.</p>
                            <p className={'mt-1 text-xs text-gray-400'}>{resources.databases} available</p>
                        </TitledGreyBox>
                    </StoreContainer>
                    <h1 className={'j-left text-5xl'}>Deployment</h1>
                    <h3 className={'j-left text-2xl text-neutral-500'}>Choose a node and server type.</h3>
                    <StoreContainer className={'lg:grid lg:grid-cols-3 my-10 gap-4'}>
                        <TitledGreyBox title={'Available Nodes'} icon={faLayerGroup} className={'mt-8 sm:mt-0'}>
                            <Select name={'node'} onChange={(e) => setNode(parseInt(e.target.value))}>
                                {nodes.map((n) => (
                                    <option key={n.id} value={n.id}>
                                        {n.name} - {n.fqdn} | {100 - parseInt(((n?.used / n?.total) * 100).toFixed(0))}%
                                        space remaining
                                    </option>
                                ))}
                            </Select>
                            <p className={'mt-1 text-xs text-gray-400'}>Select a node to deploy your server to.</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server Nest'} icon={faCube} className={'mt-8 sm:mt-0'}>
                            <Select name={'nest'} onChange={(nest) => changeNest(nest)}>
                                {nests.map((n) => (
                                    <option key={n.id} value={n.id}>
                                        {n.name}
                                    </option>
                                ))}
                            </Select>
                            <p className={'mt-1 text-xs text-gray-400'}>Select a nest to use for your server.</p>
                        </TitledGreyBox>
                        <TitledGreyBox title={'Server Egg'} icon={faEgg} className={'mt-8 sm:mt-0'}>
                            <Select name={'egg'} onChange={(e) => setEgg(parseInt(e.target.value))}>
                                {eggs.map((e) => (
                                    <option key={e.id} value={e.id}>
                                        {e.name}
                                    </option>
                                ))}
                            </Select>
                            <p className={'mt-1 text-xs text-gray-400'}>
                                Choose what game you want to run on your server.
                            </p>
                        </TitledGreyBox>
                    </StoreContainer>
                    <InputSpinner visible={loading}>
                        <div className={'text-right'}>
                            <Button
                                type={'submit'}
                                className={'w-1/6 mb-4'}
                                size={Button.Sizes.Large}
                                disabled={loading}
                            >
                                <Icon.Server className={'mr-2'} /> Create
                            </Button>
                        </div>
                    </InputSpinner>
                </Form>
            </Formik>
        </PageContentBlock>
    );
};
