import { Outlet } from '@tanstack/react-router';
import NavigationBar from '@/components/NavigationBar';

export default function AuthenticatedLayout() {
    return (
        <>
            <NavigationBar />
            <Outlet />
        </>
    );
}
