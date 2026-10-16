import { TitledGreyBox } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function StartupCommand({ model, className }: ViewProps<'server.startup.command'>) {
    return (
        <TitledGreyBox title='Startup Command' className={className}>
            <div className='px-1 py-2'>
                <p className='rounded-sm bg-terminal px-4 py-2 font-mono'>{model.command}</p>
            </div>
        </TitledGreyBox>
    );
}
