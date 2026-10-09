import { useEffect } from 'react';
import { loadExtensions } from '@/extensions/loader';
import { RouterProvider } from '@tanstack/react-router';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/api/queryClient';
import { router } from '@/router/router';
import { setupInterceptors } from '@/api/interceptors';
import AppToaster from '@/components/elements/AppToaster';

setupInterceptors();

const App = () => {
    useEffect(() => {
        void loadExtensions();
    }, []);

    return (
        <QueryClientProvider client={queryClient}>
            <RouterProvider router={router} />
            <AppToaster />
        </QueryClientProvider>
    );
};

export default App;
