import React, { forwardRef } from 'react';
import { Form } from 'formik';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement>;

export default forwardRef<any, Props>(({ ...props }, ref) => (
    <Form {...props}>
        <div className={'md:flex w-full bg-white shadow-lg rounded-lg p-6 mx-1'}>
            <div className={'flex-none select-none mb-6 md:mb-0 self-center'}>
                <img src={'/assets/pterodactyl.svg'} className={'block w-48 md:w-64 mx-auto'}/>
            </div>
            <div className={'flex-1'}>
                {props.children}
            </div>
        </div>
    </Form>
));
