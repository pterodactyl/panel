import { PageContentBlock } from '@pterodactyl/sdk';
import { defineTheme, Slot, View, type ViewPartProps, type ViewProps } from '@pterodactyl/sdk/theme';

// #region wrap
/** Keeps the layer below and frames it. */
function FramedCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className='rounded-2xl ring-1 ring-primary/30'>
            <Default />
        </div>
    );
}
// #endregion wrap

// #region transform
/** Hands the layer below another model and an extra class. */
function LoudCard({ model, Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default model={{ ...model, name: model.name.toUpperCase() }} className='gap-6' />;
}
// #endregion transform

// #region parts
/** Replaces one part of classic's card and keeps the others. */
function Uptime({ model }: ViewPartProps<'dashboard.serverCard'>) {
    return <span>{model.state.kind === 'ready' ? model.state.power : 'unknown'}</span>;
}

function CardWithUptime({ Default }: ViewProps<'dashboard.serverCard'>) {
    return <Default parts={{ metrics: Uptime }} />;
}
// #endregion parts

// #region compose
/** A page is a composition of blocks: this one lays classic's account cards out in two columns. */
function AccountOverview({ model }: ViewProps<'page.account.overview'>) {
    return (
        <PageContentBlock title='Account'>
            {model.twoFactorRequired && <p role='alert'>Turn on two-factor authentication to continue.</p>}
            <Slot name='account.overview.before' />
            <div className='grid gap-6 lg:grid-cols-2'>
                <View name='account.twoFactor' className='lg:col-span-2' />
                <View name='account.password' />
                <View name='account.email' />
            </div>
            <Slot name='account.overview.after' />
        </PageContentBlock>
    );
}
// #endregion compose

// #region optional
/** Places a block a billing extension defines; nothing renders while that extension is not installed. */
function SidebarFooter() {
    return <View name='billing.balance' optional className='mt-auto' />;
}

/** Restyles that block. Its model is `unknown` here: the theme does not know the extension's types. */
function Balance({ Default }: ViewProps<'billing.balance'>) {
    return (
        <div className='glass rounded-xl p-3'>
            <Default />
        </div>
    );
}
// #endregion optional

export default defineTheme({
    views: {
        'dashboard.serverCard': FramedCard,
        'page.account.overview': AccountOverview,
        'billing.balance': Balance,
    },
});

export { CardWithUptime, LoudCard, SidebarFooter };
