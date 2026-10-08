import { Outlet } from '@tanstack/react-router';
import ProgressBar from '@/components/elements/ProgressBar';

export default function RootLayout() {
    return (
        <>
            <ProgressBar />
            <div className='mx-auto w-auto'>
                <Outlet />
            </div>
        </>
    );
}
