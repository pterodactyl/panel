const INSTALLING_STATUSES: ReadonlySet<string> = new Set(['installing', 'install_failed', 'reinstall_failed']);

export const SKIPPED_INSTALL_SCRIPT_MESSAGE =
    "This server is configured to skip its egg's install script. Reinstalling is not available until that setting is disabled.";

export const isInstallingStatus = (status: string | null): boolean =>
    status !== null && INSTALLING_STATUSES.has(status);

export const canReinstallServer = (status: string | null, skipScripts: boolean): boolean =>
    !skipScripts || isInstallingStatus(status);
