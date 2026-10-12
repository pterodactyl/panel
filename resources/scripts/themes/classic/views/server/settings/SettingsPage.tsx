import { ServerContentBlock } from '@/sdk';
import { View } from '@/sdk/theme';

export default function SettingsPage() {
    return (
        <ServerContentBlock title='Settings'>
            <div className='md:flex'>
                <div className='w-full md:flex-1 md:mr-10'>
                    <View name='server.sftp' className='mb-6 md:mb-10' />
                    <View name='server.debug' className='mb-6 md:mb-10' />
                </div>
                <div className='w-full mt-6 md:flex-1 md:mt-0'>
                    <View name='server.rename' className='mb-6 md:mb-10' />
                    <View name='server.reinstall' />
                </div>
            </div>
        </ServerContentBlock>
    );
}
