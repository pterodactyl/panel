import TitledGreyBox from '@/components/elements/TitledGreyBox';
import RenameServerBox from '@/components/server/settings/RenameServerBox';
import Can from '@/components/elements/Can';
import ReinstallServerBox from '@/components/server/settings/ReinstallServerBox';
import { TextInput } from '@/components/form/controls';
import Label from '@/components/elements/Label';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { ip } from '@/lib/formatters';
import Button from '@/components/elements/Button';
import { useCurrentServer } from '@/api/server/queries';
import { useCurrentUser } from '@/api/account/queries';

const SettingsContainer = () => {
    const username = useCurrentUser().username;
    const server = useCurrentServer()!;
    const id = server.attributes.identifier;
    const uuid = server.attributes.uuid;
    const node = server.attributes.node;
    const sftp = server.attributes.sftp_details;

    return (
        <ServerContentBlock title={'Settings'}>
            <div className={'md:flex'}>
                <div className={'w-full md:flex-1 md:mr-10'}>
                    <Can action={'file.sftp'}>
                        <TitledGreyBox title={'SFTP Details'} className={'mb-6 md:mb-10'}>
                            <div>
                                <Label>Server Address</Label>
                                <CopyOnClick text={`sftp://${ip(sftp.ip)}:${sftp.port}`}>
                                    <TextInput type={'text'} value={`sftp://${ip(sftp.ip)}:${sftp.port}`} readOnly />
                                </CopyOnClick>
                            </div>
                            <div className={'mt-6'}>
                                <Label>Username</Label>
                                <CopyOnClick text={`${username}.${id}`}>
                                    <TextInput type={'text'} value={`${username}.${id}`} readOnly />
                                </CopyOnClick>
                            </div>
                            <div className={'mt-6 flex items-center'}>
                                <div className={'flex-1'}>
                                    <div className={'border-l-4 border-accent p-3'}>
                                        <p className={'text-xs text-foreground'}>
                                            Your SFTP password is the same as the password you use to access this panel.
                                        </p>
                                    </div>
                                </div>
                                <div className={'ml-4'}>
                                    <a href={`sftp://${username}.${id}@${ip(sftp.ip)}:${sftp.port}`}>
                                        <Button.Text isSecondary>Launch SFTP</Button.Text>
                                    </a>
                                </div>
                            </div>
                        </TitledGreyBox>
                    </Can>
                    <TitledGreyBox title={'Debug Information'} className={'mb-6 md:mb-10'}>
                        <div className={'flex items-center justify-between text-sm'}>
                            <p>Node</p>
                            <code className={'font-mono bg-muted rounded-sm py-1 px-2'}>{node}</code>
                        </div>
                        <CopyOnClick text={uuid}>
                            <div className={'flex items-center justify-between mt-2 text-sm'}>
                                <p>Server ID</p>
                                <code className={'font-mono bg-muted rounded-sm py-1 px-2'}>{uuid}</code>
                            </div>
                        </CopyOnClick>
                    </TitledGreyBox>
                </div>
                <div className={'w-full mt-6 md:flex-1 md:mt-0'}>
                    <Can action={'settings.rename'}>
                        <div className={'mb-6 md:mb-10'}>
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

export default SettingsContainer;
