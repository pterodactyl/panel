import i18next from 'i18next';

const INSTALLING_STATUSES: ReadonlySet<string> = new Set(['installing', 'install_failed', 'reinstall_failed']);

export const skippedInstallScriptMessage = (): string =>
    i18next.t(
        "This server is configured to skip its egg's install script. Reinstalling is not available until that setting is disabled."
    );

export const isInstallingStatus = (status: string | null): boolean =>
    status !== null && INSTALLING_STATUSES.has(status);

export const canReinstallServer = (status: string | null, skipScripts: boolean): boolean =>
    !skipScripts || isInstallingStatus(status);
