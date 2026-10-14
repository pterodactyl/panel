import type { ViewProps } from '@pterodactyl/sdk';

export default function ServerCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default className='hw:gap-3' />;
}
