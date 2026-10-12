import { ServerContentBlock, ServerError, Spinner } from '@/sdk';
import { Slot, View, type ViewProps } from '@/sdk/theme';

export default function StartupPage({ model }: ViewProps<'page.server.startup'>) {
    if (model.error !== null) {
        return <ServerError title='Oops!' message={model.error} onRetry={model.retry} />;
    }

    if (model.loading) {
        return <Spinner centered size='large' />;
    }

    return (
        <ServerContentBlock title='Startup Settings'>
            {model.slot && <Slot name='server.startup.form' data={model.slot} />}
            <div className='md:flex md:items-start'>
                <View name='server.startup.command' className='flex-1' />
                <View name='server.startup.image' className='flex-1 lg:flex-none lg:w-1/3 mt-8 md:mt-0 md:ml-10' />
            </div>
            <View name='server.startup.variables' />
        </ServerContentBlock>
    );
}
