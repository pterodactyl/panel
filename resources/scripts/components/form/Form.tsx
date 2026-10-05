import React from 'react';

interface Props extends Omit<React.FormHTMLAttributes<HTMLFormElement>, 'onSubmit'> {
    form: { handleSubmit: () => unknown };
    children: React.ReactNode;
}

export default function Form({ form, children, ...props }: Props) {
    return (
        <form
            noValidate
            {...props}
            onSubmit={(event) => {
                event.preventDefault();
                event.stopPropagation();
                void form.handleSubmit();
            }}
        >
            {children}
        </form>
    );
}
