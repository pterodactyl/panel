import { Outlet } from '@tanstack/react-router';
import { HttpProgressBar } from '@/components/elements/ProgressBar';
import { RouteAnnouncer, SkipLink } from '@/views/a11y';

export default function RootLayout() {
    return (
        <>
            <SkipLink />
            <HttpProgressBar />
            <div className='mx-auto w-auto'>
                <Outlet />
            </div>
            <RouteAnnouncer />
        </>
    );
}
