import { BookOpen, Code2, HeartHandshake, LifeBuoy } from 'lucide-react';
import { Link, type LinkProps } from '@tanstack/react-router';
import Icon from '@/components/elements/Icon';
import { useAdminVersion } from '@/api/admin/version/queries';
import type { AdminVersion } from '@/api/admin/version/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import OverviewVersionCard from '@/components/admin/overview/OverviewVersionCard';
import Spinner from '@/components/elements/Spinner';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import Slot from '@/extensions/Slot';
import { interactiveSurfaceClass } from '@/components/ui/styles';
import { cn } from '@/lib/cn';
import { useTranslation, type Translation } from '@/sdk/localization';

const sections = (t: Translation['t']): { to: LinkProps['to']; label: string; description: string }[] => [
    { to: '/panel/users', label: t('Users'), description: t('Create, edit, and remove user accounts.') },
    { to: '/panel/locations', label: t('Locations'), description: t('Group nodes into physical locations.') },
    { to: '/panel/nodes', label: t('Nodes'), description: t('Manage Wings daemons and allocations.') },
    { to: '/panel/servers', label: t('Servers'), description: t('Provision and manage game servers.') },
    {
        to: '/panel/databases',
        label: t('Database Hosts'),
        description: t('Configure MySQL hosts for server databases.'),
    },
    { to: '/panel/mounts', label: t('Mounts'), description: t('Define host → container mount points.') },
    { to: '/panel/eggs', label: t('Eggs'), description: t('Manage server templates and variables.') },
    { to: '/panel/settings', label: t('Settings'), description: t('General, mail, and advanced configuration.') },
    { to: '/panel/api', label: t('API Keys'), description: t('Issue application API credentials.') },
];

const externalLinks = (version: AdminVersion, t: Translation['t']) => [
    { href: version.discord, label: t('Get Help'), icon: LifeBuoy },
    { href: 'https://pterodactyl.io', label: t('Documentation'), icon: BookOpen },
    { href: 'https://github.com/pterodactyl/panel', label: 'GitHub', icon: Code2 },
    { href: version.donations, label: t('Support Project'), icon: HeartHandshake },
];

export default function OverviewContainer() {
    const { t } = useTranslation();
    const { data: version, error, refetch } = useAdminVersion();

    if (error && !version) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title={t('Admin Overview')}
            heading={t('Administration')}
            description={t('Manage your users, infrastructure, servers, and panel settings.')}
        >
            <Slot name='panel.overview.before' />
            {version ? (
                <>
                    <OverviewVersionCard version={version} />
                    <div className='mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4'>
                        {externalLinks(version, t).map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                target='_blank'
                                rel='noreferrer'
                                className={cn(
                                    'inline-flex items-center justify-center rounded-sm border border-border bg-card px-4 py-3 text-sm font-medium text-foreground no-underline hover:text-accent',
                                    interactiveSurfaceClass
                                )}
                            >
                                <Icon icon={link.icon} className='mr-2 h-4 w-4' />
                                {link.label}
                            </a>
                        ))}
                    </div>
                </>
            ) : (
                <Spinner size='large' centered />
            )}
            <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>
                {sections(t).map((s) => (
                    <Link
                        key={s.to}
                        to={s.to}
                        className={cn(
                            'block rounded-sm border border-transparent bg-card p-5 no-underline',
                            interactiveSurfaceClass
                        )}
                    >
                        <p className='text-foreground font-medium'>{s.label}</p>
                        <p className='text-muted-foreground text-sm mt-1'>{s.description}</p>
                    </Link>
                ))}
            </div>
            <Slot name='panel.overview.after' />
        </AdminContentBlock>
    );
}
