import { useMemo } from 'react';
import { Link, Outlet, useNavigate } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { useAdminEgg } from '@/api/admin/eggs/queries';
import { eggDetailRoute } from '@/router/routeTree';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import ExportEggButton from '@/components/admin/eggs/ExportEggButton';
import UpdateEggFromFileButton from '@/components/admin/eggs/UpdateEggFromFileButton';
import DeleteEggButton from '@/components/admin/eggs/DeleteEggButton';
import Icon from '@/components/elements/Icon';
import SubNavigation from '@/components/elements/SubNavigation';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import { ExtensionResourceProvider, type ExtensionResourceContext } from '@/extensions/resourceContext';
import Slot from '@/extensions/Slot';
import { resourceNavigation, type ResourceTab } from '@/views/navigation';
import { type Translation, useTranslation } from '@/sdk/localization';

const tabs = (t: Translation['t']): ResourceTab[] => [
    { id: 'configuration', label: t('Configuration'), to: '/panel/eggs/$eggId', exact: true },
    { id: 'tags', label: t('Tags'), to: '/panel/eggs/$eggId/tags' },
    { id: 'variables', label: t('Variables'), to: '/panel/eggs/$eggId/variables' },
    { id: 'script', label: t('Install Script'), to: '/panel/eggs/$eggId/script' },
];

export default function EggDetailContainer() {
    const loadedEgg = eggDetailRoute.useLoaderData();
    const navigate = useNavigate();
    const { t } = useTranslation();
    const eggId = loadedEgg.attributes.id;

    const { data: egg = loadedEgg, error } = useAdminEgg(eggId);

    const resourceContext = useMemo<Extract<ExtensionResourceContext, { kind: 'admin.egg' }>>(
        () => ({ kind: 'admin.egg', resource: egg }),
        [egg]
    );

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} />;
    }

    if (!egg) {
        return <Spinner size='large' centered />;
    }

    return (
        <ExtensionResourceProvider.Provider value={resourceContext}>
            <AdminContentBlock
                title={t('Admin · {{name}}', { name: egg.attributes.name })}
                heading={egg.attributes.name}
                description={egg.attributes.description ?? undefined}
            >
                <div className='mb-4 flex flex-wrap items-center justify-between gap-3'>
                    <Link
                        to='/panel/eggs'
                        className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground'
                    >
                        <Icon icon={ArrowLeft} className='mr-2' />
                        {t('Back to Eggs')}
                    </Link>
                    <div className='flex flex-wrap items-center gap-2'>
                        <ExportEggButton egg={egg} />
                        <UpdateEggFromFileButton egg={egg} />
                        <DeleteEggButton egg={egg} onDeleted={() => navigate({ to: '/panel/eggs' })} />
                    </div>
                </div>
                <Slot name='panel.eggs.detail.actions' data={resourceContext} />
                <SubNavigation
                    className='mb-4 rounded-sm'
                    items={resourceNavigation('admin.egg', { eggId: String(eggId) }, tabs(t))}
                />
                <Outlet />
            </AdminContentBlock>
        </ExtensionResourceProvider.Provider>
    );
}
