const newFileDraftKey = (uuid: string, directory: string) => `pterodactyl:new-file:${uuid}:${directory}`;

export const readNewFileDraft = (uuid: string, directory: string): string => {
    try {
        return sessionStorage.getItem(newFileDraftKey(uuid, directory)) ?? '';
    } catch {
        return '';
    }
};

export const writeNewFileDraft = (uuid: string, directory: string, content: string): void => {
    try {
        if (content.length > 0) {
            sessionStorage.setItem(newFileDraftKey(uuid, directory), content);
        } else {
            sessionStorage.removeItem(newFileDraftKey(uuid, directory));
        }
    } catch {
        // Storage is full or blocked.
    }
};

export const clearNewFileDraft = (uuid: string, directory: string): void => writeNewFileDraft(uuid, directory, '');
