import { Input, InputSpinner, Select, TitledGreyBox, useTranslation } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function StartupImage({ model, className }: ViewProps<'server.startup.image'>) {
    const { t } = useTranslation();

    return (
        <TitledGreyBox title={t('Docker Image')} className={className}>
            {model.images.length > 1 && !model.custom ? (
                <>
                    <InputSpinner visible={model.pending}>
                        <Select
                            disabled={model.images.length < 2}
                            value={model.image}
                            onChange={(value) => void model.select(String(value)).catch(() => {})}
                            options={model.images.map(({ label, value }) => ({ value, label }))}
                        />
                    </InputSpinner>
                    <p className='text-xs text-muted-foreground mt-2'>
                        {t(
                            'This is an advanced feature allowing you to select a Docker image to use when running this server instance.'
                        )}
                    </p>
                </>
            ) : (
                <>
                    <Input disabled readOnly value={model.image} />
                    {model.custom && (
                        <p className='text-xs text-muted-foreground mt-2'>
                            {t(
                                "This server's Docker image has been manually set by an administrator and cannot be changed through this UI."
                            )}
                        </p>
                    )}
                </>
            )}
        </TitledGreyBox>
    );
}
