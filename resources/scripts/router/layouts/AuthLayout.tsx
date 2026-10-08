import { Outlet } from '@tanstack/react-router';

export default function AuthLayout() {
    return (
        <div className='pt-8 xl:pt-32'>
            <Outlet />
        </div>
    );
}
