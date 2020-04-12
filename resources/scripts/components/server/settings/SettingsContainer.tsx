import React from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { UserData } from '@/state/user';
import RenameServerBox from '@/components/server/settings/RenameServerBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Can from '@/components/elements/Can';
import ReinstallServerBox from '@/components/server/settings/ReinstallServerBox';

export default () => {
    const user = useStoreState<ApplicationStore, UserData>(state => state.user.data!);
    const server = ServerContext.useStoreState(state => state.server.data!);
    document.title = server.name + " | Settings";

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'settings'} className={'mb-4'}/>
            <div className={'md:flex'}>
                <Can action={'file.sftp'}>
                    <div className={'w-full md:flex-1 md:max-w-1/2 md:mr-10'}>
                        <TitledGreyBox title={'SFTP Details'}>
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
                            <div className={'mt-6 flex items-center'}>
                                <div className={'flex-1'}>
                                    <div className={'border-l-4 border-cyan-500 p-3'}>
                                        <p className={'text-xs text-neutral-200'}>
                                            Your SFTP password is the same as the password you use to access this panel.
                                        </p>
                                    </div>
                                </div>
                                <div className={'ml-4'}>
                                    <a
                                        href={`sftp://${user.username}.${server.id}@${server.sftpDetails.ip}:${server.sftpDetails.port}`}
                                        className={'btn btn-sm btn-secondary'}
                                    >
                                        Launch SFTP
                                    </a>
                                </div>
                            </div>
                        </TitledGreyBox>
                    </div>
                </Can>
                <div className={'w-full mt-6 md:flex-1 md:max-w-1/2 md:mt-0'}>
                    <Can action={'settings.rename'}>
                        <div className={'mb-6 md:mb-10'}>
                            <RenameServerBox/>
                        </div>
                    </Can>
                    <Can action={'settings.reinstall'}>
                        <ReinstallServerBox/>
                    </Can>
                </div>
            </div>
        </div>
    );
};
