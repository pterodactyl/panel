import { Server as ServerIcon } from 'lucide-react';
import { cn, ListRow } from '@/sdk';
import { Slot, type ViewProps } from '@/sdk/theme';

export default function ServerCardIdentity({ model, server, className }: ViewProps<'dashboard.serverCard.identity'>) {
    return (
        <div className={cn('flex items-center col-span-12 sm:col-span-5 lg:col-span-6', className)}>
            <ListRow.Icon className='mr-4'>
                <ServerIcon size='1em' className='inline-block' />
            </ListRow.Icon>
            <div>
                <ListRow.Title>{model.name}</ListRow.Title>
                {!!model.description && <ListRow.Description>{model.description}</ListRow.Description>}
            </div>
            <Slot name='dashboard.serverRow.name.after' data={server} />
        </div>
    );
}
