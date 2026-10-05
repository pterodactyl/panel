import type { ComponentPartProps, ReplacementProps } from '@pterodactyl/sdk';

function FileName({ model }: ComponentPartProps<'server.files.details'>) {
    return <div className='hw:flex-1 hw:truncate hw:font-medium hw:text-foreground'>{model.name}</div>;
}
export default function FileDetails({ Default }: ReplacementProps<'server.files.details'>) {
    return <Default parts={{ name: FileName }} />;
}
