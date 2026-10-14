import { defineTheme, skin, type ViewProps } from '@pterodactyl/sdk/theme';

/** Aurora's server card is the layer below, so `Default` renders it. */
function SunsetCard({ Default }: ViewProps<'dashboard.serverCard'>) {
    return (
        <div className='border-l-2 border-primary pl-3'>
            <Default />
        </div>
    );
}

/** `below` is Aurora's recipe: its pill buttons, with a warm shadow. */
const button = skin('Button', {
    root: (state, below) => below('shadow-lg shadow-primary/20'),
});

export default defineTheme({ ui: { Button: button }, views: { 'dashboard.serverCard': SunsetCard } });
