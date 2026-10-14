import { defineTheme } from '@pterodactyl/sdk/theme';
import AuthLayout from './AuthLayout';

// The sign-in layout renders before anything else, so it is not lazy.
export default defineTheme({ views: { 'layout.auth': AuthLayout } });
