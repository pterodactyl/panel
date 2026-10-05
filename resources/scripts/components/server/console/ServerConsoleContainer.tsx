import { useCurrentServer } from '@/api/server/queries';
import Can from '@/components/elements/Can';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from '@/components/elements/Spinner';
import Features from '@feature/Features';
import Console from '@/components/server/console/Console';
import StatGraphs from '@/components/server/console/StatGraphs';
import PowerButtons from '@/components/server/console/PowerButtons';
import ServerDetailsBlock from '@/components/server/console/ServerDetailsBlock';
import { Alert } from '@/components/elements/alert';
import Slot from '@/extensions/Slot';

const ServerConsoleContainer = ({ active = true }: { active?: boolean }) => {
    const server = useCurrentServer()!;
    const name = server.attributes.name;
    const description = server.attributes.description;
    const isInstalling = server.attributes.status === 'installing' || server.attributes.status === 'install_failed';
    const isTransferring = server.attributes.is_transferring;
    const eggFeatures = server.attributes.egg_features;
    const isNodeUnderMaintenance = server.attributes.is_node_under_maintenance;

    return (
        <ServerContentBlock title={active ? 'Console' : undefined}>
            {(isNodeUnderMaintenance || isInstalling || isTransferring) && (
                <Alert type={'warning'} className={'mb-4'}>
                    {isNodeUnderMaintenance
                        ? 'The node of this server is currently under maintenance and all actions are unavailable.'
                        : isInstalling
                          ? 'This server is currently running its installation process and most actions are unavailable.'
                          : 'This server is currently being transferred to another node and all actions are unavailable.'}
                </Alert>
            )}
            <Slot name={'server.console.before'} data={server} />
            <div className={'grid grid-cols-4 gap-4 mb-4'}>
                <div className={'hidden sm:block sm:col-span-2 lg:col-span-3 pr-4'}>
                    <h1 className={'font-header font-medium text-2xl text-foreground leading-relaxed line-clamp-1'}>
                        {name}
                    </h1>
                    <p className={'text-sm line-clamp-2'}>{description}</p>
                </div>
                <div className={'col-span-4 sm:col-span-2 lg:col-span-1 self-end'}>
                    <Slot name={'server.console.power.before'} data={server} />
                    <Can action={['control.start', 'control.stop', 'control.restart']} matchAny>
                        <PowerButtons className={'flex sm:justify-end space-x-2'} />
                    </Can>
                    <Slot name={'server.console.power.after'} data={server} />
                </div>
            </div>
            <div className={'grid grid-cols-4 gap-2 sm:gap-4 mb-4'}>
                <div className={'flex col-span-4 lg:col-span-3'}>
                    <Spinner.Suspense>
                        <Console />
                    </Spinner.Suspense>
                </div>
                <ServerDetailsBlock className={'col-span-4 lg:col-span-1 order-last lg:order-0'} />
            </div>
            <div className={'grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4'}>
                <Spinner.Suspense>
                    <StatGraphs />
                </Spinner.Suspense>
            </div>
            <Slot name={'server.console.after'} data={server} />
            <Features enabled={eggFeatures ?? []} />
        </ServerContentBlock>
    );
};

export default ServerConsoleContainer;
