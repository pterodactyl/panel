export function replaceEditablePermissions<T extends string>(
    current: readonly T[],
    requested: readonly string[],
    editable: ReadonlySet<T>
): T[] {
    const requestedSet = new Set(requested);

    return [
        ...new Set(current.filter((permission) => !editable.has(permission))),
        ...[...editable].filter((permission) => requestedSet.has(permission)),
    ];
}

export function setPermissionsSelected<T extends string>(
    current: readonly T[],
    permissions: readonly T[],
    selected: boolean
): T[] {
    if (selected) {
        return [...new Set([...current, ...permissions])];
    }

    const removed = new Set(permissions);

    return current.filter((permission) => !removed.has(permission));
}

export interface PermissionGroupRow {
    permission: string;
    key: string;
    description: string;
}

/** Group names may contain dots, such as extension groups named `ext.<extension id>`. */
export const permissionGroupRows = (group: string, keys: Readonly<Record<string, string>>): PermissionGroupRow[] =>
    Object.entries(keys).map(([key, description]) => ({ permission: `${group}.${key}`, key, description }));
