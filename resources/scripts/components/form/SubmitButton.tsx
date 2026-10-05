import React from 'react';
import { useFormContext } from './context';
import Button from '@/components/elements/Button';

type Props = Omit<React.ComponentProps<typeof Button>, 'type'>;

/** Disabled while the form cannot submit; shows a spinner while it submits. */
export function SubmitButton({ children, disabled, ...props }: Props) {
    const form = useFormContext();

    return (
        <form.Subscribe selector={(state) => ({ canSubmit: state.canSubmit, isSubmitting: state.isSubmitting })}>
            {({ canSubmit, isSubmitting }) => (
                <Button type={'submit'} disabled={disabled || !canSubmit} isLoading={isSubmitting} {...props}>
                    {children}
                </Button>
            )}
        </form.Subscribe>
    );
}
