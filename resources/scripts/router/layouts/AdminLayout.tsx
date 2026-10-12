import type { ReactNode } from 'react';
import AdminSidebar from '@/components/admin/AdminSidebar';
import { Main } from '@/views/a11y';

export default function AdminLayout({ children }: { children?: ReactNode }) {
    return (
        <div className='mx-auto flex w-full max-w-panel flex-col lg:flex-row'>
            <AdminSidebar />
            <div className='min-w-0 flex-1 xl:pl-4'>
                <Main>{children}</Main>
            </div>
        </div>
    );
}
