import type { AdminVersion } from '@/api/admin/version/queries';
import { Alert } from '@/components/elements/alert';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

export default function OverviewVersionCard({ version }: { version: AdminVersion }) {
    const { t } = useTranslation();
    const code = <code className='font-mono' />;

    return (
        <Alert
            type={version.is_latest ? 'success' : 'warning'}
            title={version.is_latest ? t('Your panel is up-to-date.') : t('A panel update is available.')}
            className='mb-6'
        >
            {version.is_latest ? (
                <Translate
                    i18nKey='You are running version <code>{{current}}</code>.'
                    values={{ current: version.current }}
                    components={{ code }}
                />
            ) : (
                <>
                    <Translate
                        i18nKey='You are running version <code>{{current}}</code>; the latest available is <latest>{{latest}}</latest>.'
                        values={{ current: version.current, latest: version.latest }}
                        components={{
                            code,
                            latest: (
                                <a
                                    href={`https://github.com/pterodactyl/panel/releases/v${version.latest}`}
                                    target='_blank'
                                    rel='noreferrer'
                                    className='font-mono underline transition-colors hover:text-accent'
                                />
                            ),
                        }}
                    />{' '}
                    <Translate
                        i18nKey='Follow the <guide>update guide</guide> to upgrade.'
                        components={{
                            guide: (
                                <a
                                    href='https://pterodactyl.io/docs/v2/panel/updating'
                                    target='_blank'
                                    rel='noreferrer'
                                    className='underline transition-colors hover:text-accent'
                                />
                            ),
                        }}
                    />
                </>
            )}
        </Alert>
    );
}
