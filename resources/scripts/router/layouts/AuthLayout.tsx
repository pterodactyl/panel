import type { ReactNode } from 'react';
import { Main } from '@/views/a11y';

export default function AuthLayout({ children }: { children?: ReactNode }) {
    return (
        <div className='pt-8 xl:pt-32'>
            <Main>{children}</Main>
        </div>
    );
}
