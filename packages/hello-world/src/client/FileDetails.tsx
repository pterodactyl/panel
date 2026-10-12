import type { ViewPartProps, ViewProps } from '@pterodactyl/sdk';

function FileName({ model }: ViewPartProps<'server.files.details'>) {
    return <div className='hw:flex-1 hw:truncate hw:font-medium hw:text-foreground'>{model.name}</div>;
}

export default function FileDetails({ Default }: ViewProps<'server.files.details'>) {
    return <Default parts={{ name: FileName }} />;
}
