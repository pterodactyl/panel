import { useId, type FormEvent } from 'react';
import { Button, Input, Label, PanelLink, type FieldModel } from '@pterodactyl/sdk';
import { Slot, type ViewProps } from '@pterodactyl/sdk/theme';

/**
 * Spreads the engine's `fieldProps` (name, type, autocomplete, value and handlers) onto each input,
 * so password managers, validation and the disabled state while signing in keep working.
 */
function Field({ field, label }: { field: FieldModel; label: string }) {
    const id = useId();

    return (
        <div className='space-y-1.5'>
            <Label htmlFor={id}>{label}</Label>
            <Input
                {...field.fieldProps}
                id={id}
                $hasError={field.error !== null}
                aria-invalid={field.error !== null || undefined}
                aria-describedby={field.error ? `${id}-error` : undefined}
            />
            {field.error && (
                <p id={`${id}-error`} className='input-help error'>
                    {field.error}
                </p>
            )}
        </div>
    );
}

/** The engine owns the request, the CSRF cookie, the captcha, two-factor and the redirect. */
export default function LoginForm({ model }: ViewProps<'auth.loginForm'>) {
    const { fields, Captcha } = model;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        model.submit();
    };

    return (
        <form onSubmit={submit} className='space-y-5'>
            <h1 className='text-2xl font-semibold'>Sign in</h1>
            <Field field={fields.username} label='Username or email' />
            <Field field={fields.password} label='Password' />
            {/* A panel with reCAPTCHA turned on cannot sign anyone in without it. */}
            {Captcha && <Captcha />}
            <Button type='submit' size='xlarge' disabled={!model.canSubmit} isLoading={model.submitting}>
                Sign in
            </Button>
            <Slot name='auth.login.form.after' data={model.route} />
            <PanelLink destination={model.forgotPassword} className='block text-center text-sm text-muted-foreground'>
                Forgot your password?
            </PanelLink>
        </form>
    );
}
