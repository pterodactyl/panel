export type DirectoryContents = {
    files: Array<DirectoryContentObject>,
    directories: Array<DirectoryContentObject>,
    editable: Array<string>
}

export type DirectoryContentObject = {
    name: string,
    created: string,
    modified: string,
    mode: string,
    size: number,
    directory: boolean,
    file: boolean,
    symlink: boolean,
    mime: string,
}

export type ServerDatabase = {
    id: string,
    name: string,
    connections_from: string,
    username: string,
    host: {
        address: string,
        port: number,
    },
    password: string,
    showPassword: boolean,
}
