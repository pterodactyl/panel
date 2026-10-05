import type { ReplacementProps } from '@pterodactyl/sdk';

export default function ServerCard({ Default }: ReplacementProps<'dashboard.serverCard'>) {
    return <Default className='hw:gap-3' />;
}
