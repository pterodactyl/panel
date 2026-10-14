import { EthernetPort } from 'lucide-react';
import { cn } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function ServerCardAddress({ model, className }: ViewProps<'dashboard.serverCard.address'>) {
    return (
        <div className={cn('flex-1 ml-4 lg:block lg:col-span-2 hidden', className)}>
            <div className='flex justify-center'>
                <EthernetPort size='1em' className='inline-block shrink-0 text-muted-foreground' />
                <p className='text-sm text-muted-foreground ml-2'>{model.address}</p>
            </div>
        </div>
    );
}
