import { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Spinner from '@/components/elements/Spinner';
import AddSubuserButton from '@/components/server/users/AddSubuserButton';
import UserRow from '@/components/server/users/UserRow';
import FlashMessageRender from '@/components/FlashMessageRender';
import getServerSubusers from '@/api/server/users/getServerSubusers';
import { httpErrorToHuman } from '@/api/http';
import Can from '@/components/elements/Can';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';

export default () => {
    const [loading, setLoading] = useState(true);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const subusers = ServerContext.useStoreState(state => state.subusers.data);
    const setSubusers = ServerContext.useStoreActions(actions => actions.subusers.setSubusers);

    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);
    const getPermissions = useStoreActions((actions: Actions<ApplicationStore>) => actions.permissions.getPermissions);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('users');
        getServerSubusers(uuid)
            .then(subusers => {
                setSubusers(subusers);
                setLoading(false);
            })
            .catch(error => {
                console.error(error);
                addError({ key: 'users', message: httpErrorToHuman(error) });
            });
    }, []);

    useEffect(() => {
        getPermissions().catch(error => {
            addError({ key: 'users', message: httpErrorToHuman(error) });
            console.error(error);
        });
    }, []);

    if (!subusers.length && (loading || !Object.keys(permissions).length)) {
        return <Spinner size={'large'} centered />;
    }

    return (
        <ServerContentBlock title={'Users'}>
            <FlashMessageRender byKey={'users'} css={tw`mb-4`} />
            {!subusers.length ? (
                <p css={tw`text-center text-sm text-neutral-300`}>It looks like you don&apos;t have any subusers.</p>
            ) : (
                subusers.map(subuser => <UserRow key={subuser.uuid} subuser={subuser} />)
            )}
            <Can action={'user.create'}>
                <div css={tw`flex justify-end mt-6`}>
                    <AddSubuserButton />
                </div>
            </Can>
        </ServerContentBlock>
    );
};
