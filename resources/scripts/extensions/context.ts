import { createContext, useContext } from 'react';
import { clearExtensionError, reportExtensionError } from '@/extensions/registry';

export const ExtensionContext = createContext<{ extensionId: string; context: string } | null>(null);

export function useExtensionCallback<TArgs extends readonly unknown[]>(
    event: string,
    callback: (...args: TArgs) => void | Promise<void>
): (...args: TArgs) => void {
    const mount = useContext(ExtensionContext);

    if (!mount) {
        throw new Error('Extension callbacks require an extension mount.');
    }

    const context = `${mount.context}, event "${event}"`;

    return (...args: TArgs): void => {
        try {
            const result = callback(...args);

            if (result) {
                void result.then(
                    () => clearExtensionError(mount.extensionId, context),
                    (error) => reportExtensionError(mount.extensionId, context, error)
                );
            } else {
                clearExtensionError(mount.extensionId, context);
            }
        } catch (error) {
            reportExtensionError(mount.extensionId, context, error);
        }
    };
}
