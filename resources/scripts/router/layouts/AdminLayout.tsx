import { Outlet } from '@tanstack/react-router';
import AdminSidebar from '@/components/admin/AdminSidebar';

export default function AdminLayout() {
    return (
        <div className={'mx-auto flex w-full max-w-panel flex-col lg:flex-row'}>
            <AdminSidebar />
            <div className={'min-w-0 flex-1 xl:pl-4'}>
                <Outlet />
            </div>
        </div>
    );
}
