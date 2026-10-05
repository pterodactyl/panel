import { useLocation, useNavigate, useSearch } from '@tanstack/react-router';
import { useAdminSettings } from '@/api/admin/settings/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import AdvancedSettingsForm from '@/components/admin/settings/AdvancedSettingsForm';
import GeneralSettingsForm from '@/components/admin/settings/GeneralSettingsForm';
import MailSettingsForm from '@/components/admin/settings/MailSettingsForm';
import SettingsTabButton from '@/components/admin/settings/SettingsTabButton';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import { Alert } from '@/components/elements/alert';

type Tab = 'general' | 'mail' | 'advanced';
type SettingsSearch = {
    tab?: string;
};

const tabs: { key: Tab; label: string }[] = [
    { key: 'general', label: 'General' },
    { key: 'mail', label: 'Mail' },
    { key: 'advanced', label: 'Advanced' },
];

const parseTab = (tab: string | undefined): Tab =>
    tab === 'mail' || tab === 'advanced' || tab === 'general' ? tab : 'general';

export default function SettingsContainer() {
    const navigate = useNavigate();
    const location = useLocation();
    const search = useSearch({ strict: false }) as SettingsSearch;
    const pathTab = location.pathname.endsWith('/mail')
        ? 'mail'
        : location.pathname.endsWith('/advanced')
          ? 'advanced'
          : undefined;
    const tab = parseTab(pathTab ?? search.tab);

    const { data: settings, error, isFetching, refetch } = useAdminSettings();

    if (error && !settings) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title={'Admin · Settings'}
            heading={'Settings'}
            description={"Manage your panel's general, mail, and advanced settings."}
        >
            {settings?.meta.load_environment_only && (
                <Alert type={'danger'} className={'mb-6 text-sm'}>
                    <span>
                        This Panel reads settings from the environment only. Set <code>APP_ENVIRONMENT_ONLY=false</code>{' '}
                        in your environment file to load settings dynamically.
                    </span>
                </Alert>
            )}
            <div className={'flex border-b border-border mb-6'}>
                {tabs.map((item) => (
                    <SettingsTabButton
                        key={item.key}
                        active={tab === item.key}
                        onClick={() =>
                            navigate({
                                to:
                                    item.key === 'general'
                                        ? '/panel/settings'
                                        : item.key === 'mail'
                                          ? '/panel/settings/mail'
                                          : '/panel/settings/advanced',
                                replace: true,
                                viewTransition: false,
                            })
                        }
                    >
                        {item.label}
                    </SettingsTabButton>
                ))}
            </div>
            {!settings ? (
                <Spinner size={'large'} centered />
            ) : (
                <div aria-busy={isFetching}>
                    {tab === 'general' && <GeneralSettingsForm settings={settings} />}
                    {tab === 'mail' && <MailSettingsForm settings={settings} />}
                    {tab === 'advanced' && <AdvancedSettingsForm settings={settings} />}
                </div>
            )}
        </AdminContentBlock>
    );
}
