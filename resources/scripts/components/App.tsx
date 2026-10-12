import { useEffect } from 'react';
import { loadExtensions } from '@/extensions/loader';
import { RouterProvider } from '@tanstack/react-router';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/api/queryClient';
import { router } from '@/router/router';
import { setupInterceptors } from '@/api/interceptors';
import AppToaster from '@/components/elements/AppToaster';
import { ThemeFailureNotice } from '@/views/boot';
import { ViewInspector } from '@/views/inspector/ViewInspector';
import { ViewSessionProvider } from '@/views/session';

setupInterceptors();

const App = () => {
    useEffect(() => {
        void loadExtensions();
    }, []);

    return (
        <QueryClientProvider client={queryClient}>
            <ViewSessionProvider>
                <ViewInspector>
                    <RouterProvider router={router} />
                </ViewInspector>
            </ViewSessionProvider>
            <AppToaster />
            <ThemeFailureNotice />
        </QueryClientProvider>
    );
};

export default App;
