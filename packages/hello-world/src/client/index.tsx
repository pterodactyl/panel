import './styles.css';
import { definePterodactylExtension, type SdkServer } from '@pterodactyl/sdk';
import HelloWorldCard from './HelloWorldCard';
import SubuserPresets from './SubuserPresets';

function ConsoleGreeting({ data }: { data: SdkServer }) {
    return <HelloWorldCard key={data.attributes.uuid} serverName={data.attributes.name} />;
}

export default definePterodactylExtension({
    setup({ slots, screens, components }) {
        slots.register('server.console.before', ConsoleGreeting);
        slots.register('server.users.permissions.before', SubuserPresets);

        components.replace('dashboard.serverCard', { load: () => import('./ServerCard') });
        components.replace('server.files.details', { load: () => import('./FileDetails') });
        components.replace('server.files.editor', { load: () => import('./FileEditor') });

        screens.register('hello-world', () => import('./HelloWorldScreen'), {
            // A live navigation badge. Returning undefined keeps the manifest's nav.badge (none here).
            badge: ({ server }) => (server?.attributes.status === 'installing' ? 'Installing' : undefined),
        });
    },
});
