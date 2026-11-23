import { useState } from 'react';
import { Copy } from 'lucide-react';
import {
    type DeployToken,
    generateAdminNodeDeployTokenInput,
    useAdminNodeConfiguration,
    useGenerateAdminNodeDeployToken,
} from '@/api/admin/nodes/queries';
import { useNodeDetail } from '@/components/admin/nodes/useNodeDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import Spinner from '@/components/elements/Spinner';
import CopyOnClick from '@/components/elements/CopyOnClick';
import Icon from '@/components/elements/Icon';
import { Alert } from '@/components/elements/alert';
import { httpErrorToHuman } from '@/api/http';
import { toYaml } from '@/components/admin/nodes/nodeConfigurationYaml';
import Translate from '@/components/elements/Translate';
import { useTranslation } from '@/sdk/localization';

type NodeConfigurationQuery = ReturnType<typeof useAdminNodeConfiguration>;

const ConfigurationFile = ({ query }: { query: NodeConfigurationQuery }) => {
    const { t } = useTranslation();
    const { data: config, error, isFetching, refetch } = query;

    if (error && !isFetching) {
        return (
            <div className='space-y-4'>
                <Alert type='danger'>{httpErrorToHuman(error)}</Alert>
                <Button.Text type='button' onClick={() => refetch()}>
                    {t('Retry')}
                </Button.Text>
            </div>
        );
    }

    if (!config) {
        return <Spinner size='large' centered />;
    }

    const yaml = toYaml(config);

    return (
        <div className='overflow-hidden rounded-sm border border-border bg-sunken'>
            <div className='flex items-center justify-between border-b border-border py-1.5 pr-1.5 pl-4 text-xs text-muted-foreground'>
                <span className='font-mono'>config.yml</span>
                <CopyOnClick text={yaml} showInNotification={false}>
                    <Button.Text type='button' size='xsmall' isSecondary aria-label={t('Copy configuration file')}>
                        <Icon icon={Copy} className='h-3.5 w-3.5' />
                    </Button.Text>
                </CopyOnClick>
            </div>
            <pre className='overflow-x-auto px-4 py-3 font-mono text-xs leading-relaxed text-foreground'>{yaml}</pre>
        </div>
    );
};

export default function NodeConfigurationTab() {
    const { t } = useTranslation();
    const { node } = useNodeDetail();
    const { attributes } = node;
    const [token, setToken] = useState<DeployToken | null>(null);
    const generateDeployToken = useGenerateAdminNodeDeployToken();

    const query = useAdminNodeConfiguration(attributes.id);

    const generate = () => {
        generateDeployToken.mutate(generateAdminNodeDeployTokenInput(attributes.id), {
            onSuccess: setToken,
        });
    };

    const insecureFlag = token?.allow_insecure ? ' --allow-insecure' : '';
    const command = token
        ? `cd /etc/pterodactyl && sudo wings configure --panel-url ${token.panel_url} --token ${token.token} --node ${token.node}${insecureFlag}`
        : '';

    return (
        <div className='grid grid-cols-1 lg:grid-cols-3 gap-6'>
            <Dialog open={token !== null} title={t('Auto-deploy command generated')} onClose={() => setToken(null)}>
                <p className='text-sm text-muted-foreground mb-4'>
                    {t(
                        'Run the following command on the target machine to automatically configure Wings for this node.'
                    )}
                </p>
                <div className='relative'>
                    <CopyOnClick text={command} showInNotification={false}>
                        <Button.Text
                            type='button'
                            size='xsmall'
                            isSecondary
                            aria-label={t('Copy auto-deploy command')}
                            className='absolute right-2 top-2'
                        >
                            <Icon icon={Copy} className='h-3.5 w-3.5' />
                        </Button.Text>
                    </CopyOnClick>
                    <pre className='whitespace-pre-wrap break-all rounded-sm border border-border bg-sunken p-4 pr-12 font-mono text-xs leading-relaxed text-foreground'>
                        {command}
                    </pre>
                </div>
            </Dialog>
            <div className='lg:col-span-2'>
                <TitledGreyBox title={t('Configuration File')}>
                    <ConfigurationFile query={query} />
                    <p className='text-xs text-muted-foreground mt-3'>
                        <Translate
                            i18nKey="Place this file in your daemon's root directory (usually <code>/etc/pterodactyl</code>) in a file called <code>config.yml</code>."
                            components={{ code: <code /> }}
                        />
                    </p>
                </TitledGreyBox>
            </div>
            <div>
                <TitledGreyBox title={t('Auto-Deploy')}>
                    <p className='text-sm text-muted-foreground mb-4'>
                        {t(
                            'Generate a custom deployment command that configures Wings on the target server with a single command.'
                        )}
                    </p>
                    <Button
                        color='green'
                        disabled={generateDeployToken.isPending}
                        isLoading={generateDeployToken.isPending}
                        onClick={generate}
                        className='w-full'
                    >
                        {t('Generate Token')}
                    </Button>
                </TitledGreyBox>
            </div>
        </div>
    );
}
