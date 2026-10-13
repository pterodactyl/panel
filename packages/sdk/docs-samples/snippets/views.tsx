import { PanelLink, usePanelNavigate, useShortcut } from '@pterodactyl/sdk';
import { defineTheme, type AppLayoutLink, type ViewPartProps, type ViewProps } from '@pterodactyl/sdk/theme';

// #region shortcut
/** A link of `layout.app` that a key opens, such as D for the dashboard. */
function ShortcutLink({ link, keys }: { link: AppLayoutLink; keys: string }) {
    const navigate = usePanelNavigate();
    const shortcut = useShortcut(keys, () => void navigate(link.destination));

    return (
        <PanelLink destination={link.destination} aria-keyshortcuts={shortcut.aria}>
            {link.label} <kbd>{shortcut.label}</kbd>
        </PanelLink>
    );
}
// #endregion shortcut

/** Keeps the layer below and frames it. */
function FramedCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className='rounded-2xl ring-1 ring-primary/30'>
            <Default />
        </div>
    );
}

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

export default defineTheme({
    views: {
        'dashboard.serverCard': FramedCard,
    },
});

export { CardWithUptime, LoudCard, ShortcutLink };
