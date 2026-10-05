interface DatabaseConnectionFields {
    connectionString: string;
    name: string;
    password?: string;
    username: string;
}

export const jdbcConnectionString = ({ connectionString, name, password, username }: DatabaseConnectionFields) => {
    const credentials = new URLSearchParams({ user: username });
    if (password) {
        credentials.set('password', password);
    }

    return `jdbc:mysql://${connectionString}/${name}?${credentials.toString()}`;
};

/** Strips the `s{serverId}_` prefix. */
export const serverDatabaseShortName = (name: string): string => {
    const separator = name.indexOf('_');

    return separator === -1 ? name : name.slice(separator + 1);
};
