import React from 'react';
import Form from '@/components/form/Form';
import PterodactylMark from '@/components/elements/PterodactylMark';

interface Props {
    form: { handleSubmit: () => unknown };
    title?: string;
    className?: string;
    children: React.ReactNode;
}

export default function LoginFormContainer({ form, title, className, children }: Props) {
    return (
        <div className='sm:mx-auto sm:w-4/5 md:p-10 lg:w-3/5 xl:w-full xl:max-w-auth'>
            {title && <h2 className='text-3xl text-center text-foreground font-medium py-4'>{title}</h2>}
            <Form form={form} className={className}>
                <div className='md:flex w-full bg-card text-card-foreground shadow-lg rounded-lg p-6 md:pl-0 mx-1'>
                    <div className='flex-none select-none mb-6 md:mb-0 self-center'>
                        <PterodactylMark className='block w-32 md:w-56 h-auto mx-auto md:px-12' />
                    </div>
                    <div className='flex-1'>{children}</div>
                </div>
            </Form>
            <p className='text-center text-muted-foreground text-xs mt-4'>
                &copy; 2015 - <span suppressHydrationWarning>{new Date().getFullYear()}</span>
                &nbsp;
                <a
                    rel='noopener nofollow noreferrer'
                    href='https://pterodactyl.io'
                    target='_blank'
                    className='no-underline text-muted-foreground hover:text-muted-foreground'
                >
                    Pterodactyl Software
                </a>
            </p>
        </div>
    );
}
