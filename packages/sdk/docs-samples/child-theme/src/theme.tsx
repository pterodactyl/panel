import { defineTheme, type ViewProps } from '@pterodactyl/sdk/theme';

/** Aurora's server card is the layer below, so `Default` renders it. */
function SunsetCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className='border-l-2 border-primary pl-3'>
            <Default />
        </div>
    );
}

export default defineTheme({ views: { 'dashboard.serverCard': SunsetCard } });
