export function LogHandler(log: any){
    switch(log.action){
        case "server:backup.start":
            return `Backup ${log.metadata.backup_name} got created`
        case "server:backup.fail":
            return `Backup ${log.metadata.backup_name} failed`
            case "server:backup.complete":
                return `Backup ${log.metadata.backup_name} got completed`
                case "server:backup.delete":
                    return `Backup ${log.metadata.backup_name} got deleted`
                    case "server:backup.download":
                        return `Backup ${log.metadata.backup_name} got downloaded`
                        case "server:backup.lock":
                            return `Backup ${log.metadata.backup_name} got locked`
                            case "server:backup.unlock":
                                return `Backup ${log.metadata.backup_name} got unlocked`
                                case "server:backup.restore.start":
                                    return `Backup ${log.metadata.backup_name} restoration started`
                                    case "server:backup.restore.complete":
                                        return `Backup ${log.metadata.backup_name} restoration got completed`
                                        case "server:backup.restore.fail":
                                            return `Backup ${log.metadata.backup_name} restoration failed`

                                            case "server:database.create":
                                            return `Database ${log.metadata.database_name} got created`
                                            case "server:database.password.rotate":
                                            return `Password of database ${log.metadata.database_name} got rotated`
                                            case "server:database.delete":
                                            return `Database ${log.metadata.database_name} got deleted`

                                            case "server:filesystem.download":
                                            return `File ${log.metadata.file} got downloaded`
                                            case "server:filesystem.write":
                                            return `File ${log.metadata.file} got edited`
                                            case "server:filesystem.delete":
                                                if(log.metadata.files.length > 1){
                                                    return `Files ${log.metadata.files.join(", ")} got deleted`
                                                }else{
                                                    return `File ${log.metadata.files[0]} got deleted`
                                                }
                                            case "server:filesystem.rename":
                                            return `File ${log.metadata.file} got renamed`
                                            case "server:filesystem.compress":
                                                if(log.metadata.files.length > 1){
                                                    return `Files ${log.metadata.files.join(", ")} got compressed in ${log.metadata.root}`
                                                }else{
                                                    return `File ${log.metadata.root+log.metadata.files[0]} got compressed`
                                                }
                                                case "server:filesystem.decompress":
                                                    return `File ${log.metadata.root+log.metadata.files} got decompressed`
    }
}