import { Outlet } from '@tanstack/react-router';
import ProgressBar from '@/components/elements/ProgressBar';
import { RouteAnnouncer, SkipLink } from '@/views/a11y';

export default function RootLayout() {
    return (
        <>
            <SkipLink />
            <ProgressBar />
            <div className='mx-auto w-auto'>
                <Outlet />
            </div>
            <RouteAnnouncer />
        </>
    );
}
