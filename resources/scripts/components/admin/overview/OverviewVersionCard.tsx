import type { AdminVersion } from '@/api/admin/version/queries';
import { Alert } from '@/components/elements/alert';

export default function OverviewVersionCard({ version }: { version: AdminVersion }) {
    return (
        <Alert
            type={version.is_latest ? 'success' : 'warning'}
            title={version.is_latest ? 'Your panel is up-to-date.' : 'A panel update is available.'}
            className={'mb-6'}
        >
            {version.is_latest ? (
                <>
                    You are running version <code className={'font-mono'}>{version.current}</code>.
                </>
            ) : (
                <>
                    You are running version <code className={'font-mono'}>{version.current}</code>; the latest available
                    is{' '}
                    <a
                        href={`https://github.com/pterodactyl/panel/releases/v${version.latest}`}
                        target={'_blank'}
                        rel={'noreferrer'}
                        className={'font-mono underline transition-colors hover:text-accent'}
                    >
                        {version.latest}
                    </a>
                    . Follow the{' '}
                    <a
                        href={'https://pterodactyl.io/docs/v2/panel/updating'}
                        target={'_blank'}
                        rel={'noreferrer'}
                        className={'underline transition-colors hover:text-accent'}
                    >
                        update guide
                    </a>{' '}
                    to upgrade.
                </>
            )}
        </Alert>
    );
}
