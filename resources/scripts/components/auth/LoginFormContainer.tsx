import * as React from 'react';
import { Form } from 'formik';

export default ({ className, ...props }: React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement>) => (
    <Form
        className={'flex items-center justify-center login-box'}
        {...props}
        style={{
            paddingLeft: 0,
        }}
    >
        <div className={'flex-none select-none'}>
            <img src={'/assets/pterodactyl.svg'} className={'w-64'}/>
        </div>
        <div className={'flex-1'}>
            {props.children}
        </div>
    </Form>
);
