import { Card, CopyOnClick, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function ServerDebug({ model, className }: ViewProps<'server.debug'>) {
    const { t } = useTranslation();

    return (
        <Card title={t('Debug Information')} className={className}>
            <div className='flex items-center justify-between text-sm'>
                <p>{t('Node')}</p>
                <code className='font-mono bg-muted rounded-sm py-1 px-2'>{model.node}</code>
            </div>
            <CopyOnClick text={model.uuid}>
                <div className='flex items-center justify-between mt-2 text-sm'>
                    <p>{t('Server ID')}</p>
                    <code className='font-mono bg-muted rounded-sm py-1 px-2'>{model.uuid}</code>
                </div>
            </CopyOnClick>
        </Card>
    );
}
