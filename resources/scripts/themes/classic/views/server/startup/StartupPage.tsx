import { ServerContentBlock } from '@/sdk';
import { Slot, View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function StartupPage({ model, className }: ViewProps<'page.server.startup'>) {
    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title='Startup Settings' className={className}>
                {model.slot && <Slot name='server.startup.form' data={model.slot} />}
                <div className='md:flex md:items-start'>
                    <View name='server.startup.command' className='flex-1' />
                    <View name='server.startup.image' className='flex-1 lg:flex-none lg:w-1/3 mt-8 md:mt-0 md:ml-10' />
                </div>
                <View name='server.startup.variables' />
            </ServerContentBlock>
        </PageStatus>
    );
}
