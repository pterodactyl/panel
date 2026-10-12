import {
    Badge,
    Button,
    Card,
    definePterodactylExtension,
    type ExtensionViewProps,
    type ViewProps,
} from '@pterodactyl/sdk';
import { View } from '@pterodactyl/sdk/theme';

// #region override
/** A layer over the server card: it keeps the layers below and adds a note. */
function ServerCard({ Default, model }: ViewProps<'dashboard.serverCard'>) {
    return (
        <>
            <Default className='hw:gap-3' />
            {model.state.kind === 'unavailable' && <p className='hw:text-xs'>Ask support about {model.name}.</p>}
        </>
    );
}
// #endregion override

// #region define
interface BalanceModel {
    readonly credits: number;
    readonly topUp: () => void;
}

function useBalance(): BalanceModel {
    return { credits: 120, topUp: () => window.open('/billing', '_self') };
}

/** The bottom layer of the extension's own view; themes restyle or move it. Built from the UI kit, it takes their skins. */
function Balance({ model, className }: ExtensionViewProps<BalanceModel>) {
    return (
        <Card title='Balance' className={className} actions={<Badge tone='success'>{model.credits} credits</Badge>}>
            <Button size='xsmall' onClick={model.topUp}>
                Top up
            </Button>
        </Card>
    );
}
// #endregion define

export default definePterodactylExtension({
    setup({ views, slots }) {
        // #region setup
        views.override('dashboard.serverCard', ServerCard);
        views.override('server.files.details', { load: () => import('./FileDetails') });
        views.define<BalanceModel>('billing.balance', {
            context: 'app',
            use: useBalance,
            Default: Balance,
            fixtures: {
                funded: () => ({ credits: 120, topUp: () => {} }),
                empty: () => ({ credits: 0, topUp: () => {} }),
            },
        });
        // Wherever classic has no place for it yet, a slot places the view.
        slots.register('dashboard.after', () => <View name='billing.balance' />);
        // #endregion setup
    },
});
