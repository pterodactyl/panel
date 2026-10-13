import './styles.css';
import { definePterodactylExtension } from '@pterodactyl/sdk';
import ConsoleGreeting from './ConsoleGreeting';
import SubuserPresets from './SubuserPresets';

export default definePterodactylExtension({
    setup({ slots, screens, views }) {
        slots.register('server.console.before', ConsoleGreeting);
        slots.register('server.users.permissions.before', SubuserPresets);

        views.override('dashboard.serverCard', { load: () => import('./ServerCard') });
        views.override('server.files.details', { load: () => import('./FileDetails') });
        views.override('server.files.editor', { load: () => import('./FileEditor') });

        screens.register('hello-world', () => import('./HelloWorldScreen'), {
            // A live navigation badge. Returning undefined keeps the manifest's nav.badge (none here).
            badge: ({ server }) => (server?.attributes.status === 'installing' ? 'Installing' : undefined),
        });
    },
});
