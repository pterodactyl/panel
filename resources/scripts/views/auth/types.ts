import type { ComponentType } from 'react';
import type { RouteSlotData } from '@/extensions/registry';
import type { CorePanelDestination } from '@/sdk/navigation';
import type { FormModel } from '../forms';

/** The sign-in pages are compositions of their form; they hold nothing of their own. */
export type AuthPageModel = Readonly<Record<never, never>>;

/**
 * Every auth form: the engine fetches the CSRF cookie, runs the reCAPTCHA, maps errors to toasts and
 * redirects. Place `Captcha` inside the form when it is set, or a captcha-protected panel cannot sign in.
 */
interface AuthFormModel<F extends string> extends FormModel<F> {
    readonly Captcha: ComponentType | null;
}

export interface LoginFormModel extends AuthFormModel<'username' | 'password'> {
    readonly forgotPassword: CorePanelDestination;
    /** What the `auth.login.form.after` slot receives. */
    readonly route: RouteSlotData;
}

/** The second step of a two-factor sign-in, by authenticator code or by recovery code. */
export interface CheckpointFormModel extends FormModel<'code'> {
    /** The code field takes a recovery code instead of an authenticator code. */
    readonly recovery: boolean;
    readonly toggleRecovery: () => void;
    readonly login: CorePanelDestination;
}

export interface ForgotPasswordFormModel extends AuthFormModel<'email'> {
    readonly login: CorePanelDestination;
}

export interface ResetPasswordFormModel extends AuthFormModel<'password' | 'passwordConfirmation'> {
    /** The account being reset, from the emailed link. */
    readonly email: string;
    readonly login: CorePanelDestination;
}

declare module '../registry' {
    interface ViewModels {
        'page.auth.login': AuthPageModel;
        'page.auth.checkpoint': AuthPageModel;
        'page.auth.password': AuthPageModel;
        'page.auth.passwordReset': AuthPageModel;
        'auth.loginForm': LoginFormModel;
        'auth.checkpointForm': CheckpointFormModel;
        'auth.forgotPasswordForm': ForgotPasswordFormModel;
        'auth.resetPasswordForm': ResetPasswordFormModel;
    }
}
