import { View, type ViewProps } from '@pterodactyl/sdk/theme';

/** Classic's server layout, under a header with the server's name and its power controls. */
export default function ServerLayout({ model, Default }: ViewProps<'layout.server'>) {
    return (
        <>
            <header className='mx-auto flex w-full max-w-panel flex-wrap items-center gap-4 px-4 pt-6'>
                <h1 className='min-w-0 flex-1 truncate text-xl font-semibold'>{model.name}</h1>
                {/* No power controls while the server installs, transfers or is suspended. */}
                {model.conflict === null && <View name='server.power' />}
            </header>
            <Default />
        </>
    );
}
