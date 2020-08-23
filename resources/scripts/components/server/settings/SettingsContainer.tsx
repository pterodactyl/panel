import React from 'react';
import { Helmet } from 'react-helmet';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { UserData } from '@/state/user';
import RenameServerBox from '@/components/server/settings/RenameServerBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Can from '@/components/elements/Can';
import ReinstallServerBox from '@/components/server/settings/ReinstallServerBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import { LinkButton } from '@/components/elements/Button';

export default () => {
    const user = useStoreState<ApplicationStore, UserData>(state => state.user.data!);
    const server = ServerContext.useStoreState(state => state.server.data!);

    return (
        <PageContentBlock>
            <Helmet>
                <title> {server.name} | Settings </title>
            </Helmet>
            <FlashMessageRender byKey={'settings'} css={tw`mb-4`}/>
            <div css={tw`md:flex`}>
                <div css={tw`w-full md:flex-1 md:mr-10`}>
                    <Can action={'file.sftp'}>
                        <TitledGreyBox title={'SFTP Details'} css={tw`mb-6 md:mb-10`}>
                            <div>
                                <Label>Server Address</Label>
                                <Input
                                    type={'text'}
                                    value={`sftp://${server.sftpDetails.ip}:${server.sftpDetails.port}`}
                                    readOnly
                                />
                            </div>
                            <div css={tw`mt-6`}>
                                <Label>Username</Label>
                                <Input
                                    type={'text'}
                                    value={`${user.username}.${server.id}`}
                                    readOnly
                                />
                            </div>
                            <div css={tw`mt-6 flex items-center`}>
                                <div css={tw`flex-1`}>
                                    <div css={tw`border-l-4 border-cyan-500 p-3`}>
                                        <p css={tw`text-xs text-neutral-200`}>
                                            Your SFTP password is the same as the password you use to access this panel.
                                        </p>
                                    </div>
                                </div>
                                <div css={tw`ml-4`}>
                                    <LinkButton
                                        isSecondary
                                        href={`sftp://${user.username}.${server.id}@${server.sftpDetails.ip}:${server.sftpDetails.port}`}
                                    >
                                        Launch SFTP
                                    </LinkButton>
                                </div>
                            </div>
                        </TitledGreyBox>
                    </Can>
                </div>
                <div css={tw`w-full mt-6 md:flex-1 md:mt-0`}>
                    <Can action={'settings.rename'}>
                        <div css={tw`mb-6 md:mb-10`}>
                            <RenameServerBox/>
                        </div>
                    </Can>
                    <Can action={'settings.reinstall'}>
                        <ReinstallServerBox/>
                    </Can>
                </div>
            </div>
        </PageContentBlock>
    );
};
