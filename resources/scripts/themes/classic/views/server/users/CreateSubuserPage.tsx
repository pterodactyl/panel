import { ArrowLeft } from 'lucide-react';
import { Icon, PanelLink, ServerContentBlock, Spinner, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function CreateSubuserPage({ model, className }: ViewProps<'page.server.users.create'>) {
    const { t } = useTranslation();

    return (
        <ServerContentBlock title={t('Create Subuser')} className={className}>
            <PanelLink
                destination={{ to: '/server/$id/users', params: { id: model.serverId } }}
                className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                {t('Back to Users')}
            </PanelLink>
            {model.loading ? <Spinner size='large' centered /> : <View name='server.subuserForm' />}
        </ServerContentBlock>
    );
}
