import React from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { UserData } from '@/state/user';

export default () => {
    const user = useStoreState<ApplicationStore, UserData>(state => state.user.data!);
    const server = ServerContext.useStoreState(state => state.server.data!);

    return (
        <div className={'my-10 mb-6 flex'}>
            <TitledGreyBox title={'SFTP Details'} className={'w-full md:w-1/2'}>
                <div>
                    <label className={'input-dark-label'}>Server Address</label>
                    <input
                        type={'text'}
                        className={'input-dark'}
                        value={`sftp://${server.sftpDetails.ip}:${server.sftpDetails.port}`}
                        readOnly={true}
                    />
                </div>
                <div className={'mt-6'}>
                    <label className={'input-dark-label'}>Username</label>
                    <input
                        type={'text'}
                        className={'input-dark'}
                        value={`${user.username}.${server.id}`}
                        readOnly={true}
                    />
                </div>
                <div className={'mt-6'}>
                    <div className={'border-l-4 border-cyan-500 p-3'}>
                        <p className={'text-xs text-neutral-200'}>
                            Your SFTP password is the same as the password you use to access this panel.
                        </p>
                    </div>
                </div>
            </TitledGreyBox>
        </div>
    );
};
