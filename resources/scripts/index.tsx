import React from 'react';
import { createRoot } from 'react-dom/client';
import App from '@/components/App';
import { queryClient } from '@/api/queryClient';
import { syncThemeColorMeta } from '@/lib/theme';
import '@/assets/tailwind.css';
import '@/router/view-transitions.css';
import { followCurrentUserLanguage } from './i18n';

followCurrentUserLanguage(queryClient);
syncThemeColorMeta();

const container = document.getElementById('app');
if (container) {
    const root = createRoot(container);

    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}
