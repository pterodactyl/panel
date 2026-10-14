import type { ViewProps } from '@pterodactyl/sdk';

/** Loaded with the file browser, which shows the view's placeholder until the chunk has loaded. */
export default function FileDetails({ model, Default }: ViewProps<'server.files.details'>) {
    return model.kind === 'archive' ? <Default model={{ ...model, name: `${model.name} (archive)` }} /> : <Default />;
}
