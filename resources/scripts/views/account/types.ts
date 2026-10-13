import type { FormModel } from '../forms';
import type { PageStatusModel } from '../pageStatus';

export interface AccountOverviewModel {
    /** The panel sent the user here because their account must enable two-step verification. */
    readonly twoFactorRequired: boolean;
}

export type AccountPasswordModel = FormModel<'current' | 'password' | 'confirmPassword'>;
export type AccountEmailModel = FormModel<'email' | 'password'>;

export type TwoFactorDialog = 'enable' | 'disable';
export interface TwoFactorSetupModel extends FormModel<'code' | 'password'> {
    /** The secret to enter by hand, once loaded. */
    readonly secret: string | null;
    /** The `otpauth://` URI to show as a QR code, once loaded. */
    readonly qrCode: string | null;
    /** Loading the secret failed. */
    readonly error: string | null;
    readonly retry: () => void;
}
/**
 * Two-step verification: the engine loads the secret while the enable dialog is open, and closes the
 * dialogs itself once a request succeeds.
 */
export interface AccountTwoFactorModel {
    readonly enabled: boolean;
    readonly dialog: TwoFactorDialog | null;
    readonly openDialog: (dialog: TwoFactorDialog) => void;
    readonly closeDialog: () => void;
    readonly setup: TwoFactorSetupModel;
    readonly disable: FormModel<'password'>;
    /** Shown once after enabling, until dismissed. */
    readonly recoveryTokens: readonly string[];
    readonly dismissRecoveryTokens: () => void;
}

export interface ApiKeyItem {
    readonly identifier: string;
    readonly description: string;
    readonly allowedIps: readonly string[];
    readonly lastUsedAt: string | null;
    readonly createdAt: string;
}
export interface ApiKeyListModel {
    readonly keys: readonly ApiKeyItem[];
    /** The keys have not loaded yet. */
    readonly loading: boolean;
    readonly deleting: boolean;
    /** Settles once the request does; a failure has already been toasted. Confirm with the user first. */
    readonly remove: (identifier: string) => Promise<void>;
}
export interface ApiKeyCreateModel extends FormModel<'description' | 'allowedIps'> {
    /** The new key with its secret, which is shown once until dismissed. */
    readonly createdKey: string | null;
    readonly dismissCreatedKey: () => void;
}

export interface SshKeyItem {
    readonly name: string;
    readonly fingerprint: string;
    readonly createdAt: string;
}
export interface SshKeyListModel {
    readonly keys: readonly SshKeyItem[];
    readonly loading: boolean;
    /** Settles once the request does; a failure has already been toasted. Confirm with the user first. */
    readonly remove: (fingerprint: string) => Promise<void>;
}
export type SshKeyCreateModel = FormModel<'name' | 'publicKey'>;

declare module '../registry' {
    interface ViewModels {
        'page.account.overview': AccountOverviewModel;
        'page.account.api': PageStatusModel;
        'page.account.ssh': PageStatusModel;
        'account.password': AccountPasswordModel;
        'account.email': AccountEmailModel;
        'account.twoFactor': AccountTwoFactorModel;
        'account.apiKeys.list': ApiKeyListModel;
        'account.apiKeys.create': ApiKeyCreateModel;
        'account.sshKeys.list': SshKeyListModel;
        'account.sshKeys.create': SshKeyCreateModel;
    }
}
