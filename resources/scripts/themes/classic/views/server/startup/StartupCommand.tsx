import { Copy } from 'lucide-react';
import { Button, CopyOnClick, Icon, TitledGreyBox } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function StartupCommand({ model, className }: ViewProps<'server.startup.command'>) {
    return (
        <TitledGreyBox
            title={
                <div className='flex items-center justify-between'>
                    <span>Startup Command</span>
                    {model.command && (
                        <CopyOnClick text={model.command}>
                            <Button.Text type='button' size='xsmall' isSecondary aria-label='Copy startup command'>
                                <Icon icon={Copy} className='h-3.5 w-3.5' />
                            </Button.Text>
                        </CopyOnClick>
                    )}
                </div>
            }
            className={className}
        >
            <div className='px-1 py-2'>
                <p className='rounded-sm bg-terminal px-4 py-2 font-mono'>{model.command}</p>
            </div>
        </TitledGreyBox>
    );
}
