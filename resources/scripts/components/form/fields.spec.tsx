/** @vitest-environment jsdom */

import { createRef, useImperativeHandle, type FocusEventHandler, type Ref } from 'react';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { useAppForm, type AppForm } from '@/components/form';

afterEach(cleanup);

type Values = { name: string; memory: number | null };

function Harness({
    formRef,
    onNameBlur,
}: {
    formRef: Ref<AppForm<Values>>;
    onNameBlur?: FocusEventHandler<HTMLInputElement>;
}) {
    const form = useAppForm({ defaultValues: { name: '', memory: 512 } as Values });

    useImperativeHandle(formRef, () => form, [form]);

    return (
        <>
            <form.AppField
                name='name'
                validators={{ onChange: ({ value }) => (value === 'bad' ? 'invalid CPU limit.' : undefined) }}
            >
                {(field) => (
                    <field.TextField
                        label='Name'
                        description='The display name.'
                        aria-describedby='external-hint'
                        onBlur={onNameBlur}
                    />
                )}
            </form.AppField>
            <form.AppField name='memory'>{(field) => <field.NumberField label='Memory' />}</form.AppField>
        </>
    );
}

const renderHarness = (onNameBlur?: FocusEventHandler<HTMLInputElement>) => {
    const formRef = createRef<AppForm<Values>>();

    render(<Harness formRef={formRef} onNameBlur={onNameBlur} />);

    return () => formRef.current!;
};

describe('form fields', () => {
    it('stores null when a number field is cleared', () => {
        const form = renderHarness();
        const input = screen.getByRole('textbox', { name: 'Memory' });

        fireEvent.change(input, { target: { value: '' } });
        fireEvent.blur(input);

        expect(form().state.values.memory).toBeNull();
    });

    it('links the description to the control and keeps caller ids', () => {
        renderHarness();
        const input = screen.getByRole('textbox', { name: 'Name' });

        expect(input).not.toHaveAttribute('aria-invalid');
        expect(input).toHaveAccessibleDescription('The display name.');
        expect(input.getAttribute('aria-describedby')?.split(' ')).toContain('external-hint');
    });

    it('marks the control invalid and describes it with the error, preserving its casing', () => {
        renderHarness();
        const input = screen.getByRole('textbox', { name: 'Name' });

        act(() => {
            fireEvent.change(input, { target: { value: 'bad' } });
        });

        expect(input).toHaveAttribute('aria-invalid', 'true');
        expect(input).toHaveAccessibleDescription('Invalid CPU limit.');
        expect(screen.getByText('Invalid CPU limit.')).toBeInTheDocument();
    });

    it('runs both the field blur handler and the caller onBlur', () => {
        const onBlur = vi.fn();
        const form = renderHarness(onBlur);
        const input = screen.getByRole('textbox', { name: 'Name' });

        fireEvent.focus(input);
        fireEvent.blur(input);

        expect(onBlur).toHaveBeenCalledOnce();
        expect(form().getFieldMeta('name')?.isBlurred).toBe(true);
    });
});
