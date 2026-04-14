import React from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import { useStoreState } from 'easy-peasy';
import RenameServerBox from '@/components/server/settings/RenameServerBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Can from '@/components/elements/Can';
import ReinstallServerBox from '@/components/server/settings/ReinstallServerBox';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import isEqual from 'react-fast-compare';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { ip } from '@/lib/formatters';
import { Button } from '@/components/elements/button/index';

export default () => {
    const username = useStoreState((state) => state.user.data!.username);
    const id = ServerContext.useStoreState((state) => state.server.data!.id);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const node = ServerContext.useStoreState((state) => state.server.data!.node);
    const sftp = ServerContext.useStoreState((state) => state.server.data!.sftpDetails, isEqual);

    return (
        <ServerContentBlock title={'Settings'}>
            <FlashMessageRender byKey={'settings'} className="mb-4" />
            <div className="md:flex">
                <div className="w-full md:flex-1 md:mr-10">
                    <Can action={'file.sftp'}>
                        <TitledGreyBox title={'SFTP Details'} className="mb-6 md:mb-10">
                            <div>
                                <Label>Server Address</Label>
                                <CopyOnClick text={`sftp://${ip(sftp.ip)}:${sftp.port}`}>
                                    <Input type={'text'} value={`sftp://${ip(sftp.ip)}:${sftp.port}`} readOnly />
                                </CopyOnClick>
                            </div>
                            <div className="mt-6">
                                <Label>Username</Label>
                                <CopyOnClick text={`${username}.${id}`}>
                                    <Input type={'text'} value={`${username}.${id}`} readOnly />
                                </CopyOnClick>
                            </div>
                            <div className="mt-6 flex items-center">
                                <div className="flex-1">
                                    <div className="border-l-4 border-cyan-500 p-3">
                                        <p className="text-xs text-neutral-200">
                                            Your SFTP password is the same as the password you use to access this panel.
                                        </p>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <a href={`sftp://${username}.${id}@${ip(sftp.ip)}:${sftp.port}`}>
                                        <Button.Text variant={Button.Variants.Secondary}>Launch SFTP</Button.Text>
                                    </a>
                                </div>
                            </div>
                        </TitledGreyBox>
                    </Can>
                    <TitledGreyBox title={'Debug Information'} className="mb-6 md:mb-10">
                        <div className="flex items-center justify-between text-sm">
                            <p>Node</p>
                            <code className="font-mono bg-neutral-900 rounded py-1 px-2">{node}</code>
                        </div>
                        <CopyOnClick text={uuid}>
                            <div className="flex items-center justify-between mt-2 text-sm">
                                <p>Server ID</p>
                                <code className="font-mono bg-neutral-900 rounded py-1 px-2">{uuid}</code>
                            </div>
                        </CopyOnClick>
                    </TitledGreyBox>
                </div>
                <div className="w-full mt-6 md:flex-1 md:mt-0">
                    <Can action={'settings.rename'}>
                        <div className="mb-6 md:mb-10">
                            <RenameServerBox />
                        </div>
                    </Can>
                    <Can action={'settings.reinstall'}>
                        <ReinstallServerBox />
                    </Can>
                </div>
            </div>
        </ServerContentBlock>
    );
};
