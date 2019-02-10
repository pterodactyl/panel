export type DirectoryContents = {
    files: Array<string>,
    directories: Array<string>,
    editable: Array<string>
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
