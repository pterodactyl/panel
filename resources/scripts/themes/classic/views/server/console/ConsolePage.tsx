import { Alert, ServerContentBlock } from '@/sdk';
import { Slot, View, type ServerConsoleModel, type ViewProps } from '@/sdk/theme';

const notices = {
    maintenance: 'The node of this server is currently under maintenance and all actions are unavailable.',
    installing: 'This server is currently running its installation process and most actions are unavailable.',
    transferring: 'This server is currently being transferred to another node and all actions are unavailable.',
} satisfies Record<NonNullable<ServerConsoleModel['notice']>, string>;

export default function ConsolePage({ model, active }: ViewProps<'page.server.console'>) {
    return (
        <ServerContentBlock title={active ? 'Console' : undefined}>
            {model.notice && (
                <Alert type='warning' className='mb-4'>
                    {notices[model.notice]}
                </Alert>
            )}
            <Slot name='server.console.before' data={model.server} />
            <div className='grid grid-cols-4 gap-4 mb-4'>
                <div className='hidden sm:block sm:col-span-2 lg:col-span-3 pr-4'>
                    <h1 className='font-header font-medium text-2xl text-foreground leading-relaxed line-clamp-1'>
                        {model.name}
                    </h1>
                    <p className='text-sm line-clamp-2'>{model.description}</p>
                </div>
                <div className='col-span-4 sm:col-span-2 lg:col-span-1 self-end'>
                    <View name='server.power' />
                </div>
            </div>
            <div className='grid grid-cols-4 gap-2 sm:gap-4 mb-4'>
                <div className='flex col-span-4 lg:col-span-3'>
                    <View name='server.terminal' />
                </div>
                <View name='server.stats' className='col-span-4 lg:col-span-1 order-last lg:order-0' />
            </div>
            <div className='grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4'>
                <View name='server.graphs' />
            </div>
            <Slot name='server.console.after' data={model.server} />
            <View name='server.features' />
        </ServerContentBlock>
    );
}
