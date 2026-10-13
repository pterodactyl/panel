import { definePterodactylExtension, type ViewProps } from '@pterodactyl/sdk';

// #region override
/** A layer over the server card: it keeps the layers below and adds a note. */
function ServerCard({ Default, model }: ViewProps<'dashboard.serverCard'>) {
    return (
        <>
            <Default className='bl:gap-3' />
            {model.state.kind === 'unavailable' && <p className='bl:text-xs'>Ask support about {model.name}.</p>}
        </>
    );
}
// #endregion override

export default definePterodactylExtension({
    setup({ views }) {
        // #region setup
        views.override('dashboard.serverCard', ServerCard);
        views.override('server.files.details', { load: () => import('./FileDetails') });
        // #endregion setup
    },
});
