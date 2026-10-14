import type { ViewProps } from '@pterodactyl/sdk';

// Keeps the native row and dims hidden files; the prefixed class merges with the row's own.
export default function FileDetails({ Default, model }: ViewProps<'server.files.details'>) {
    return <Default className={model.name.startsWith('.') ? 'hw:opacity-60' : undefined} />;
}
