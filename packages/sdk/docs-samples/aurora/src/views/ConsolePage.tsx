import { Alert, ServerContentBlock } from '@pterodactyl/sdk';
import { Slot, View, type ServerConsoleModel, type ViewProps } from '@pterodactyl/sdk/theme';

const notices: Record<NonNullable<ServerConsoleModel['notice']>, string> = {
    maintenance: 'This node is under maintenance, so the server cannot be controlled.',
    installing: 'The server is installing; most actions wait until it finishes.',
    transferring: 'The server is moving to another node.',
};

/**
 * The console without its power controls, which the server layout shows in its header. The page stays
 * mounted while another server page shows, so it only titles the document while `active`.
 */
export default function ConsolePage({ model, active }: ViewProps<'page.server.console'>) {
    return (
        <ServerContentBlock title={active ? 'Console' : undefined}>
            {model.notice && (
                <Alert type='warning' className='mb-4'>
                    {notices[model.notice]}
                </Alert>
            )}
            <Slot name='server.console.before' data={model.server} />
            <div className='grid gap-4 xl:grid-cols-[minmax(0,1fr)_18rem]'>
                <View name='server.terminal' />
                <View name='server.stats' />
            </div>
            <div className='mt-4 grid gap-4 md:grid-cols-3'>
                <View name='server.graphs' />
            </div>
            <Slot name='server.console.after' data={model.server} />
            <View name='server.features' />
        </ServerContentBlock>
    );
}
