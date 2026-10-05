import { rm, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

/** Replace generated transport with the host runtime; fail loudly when generator output changes. */
export async function externalizeExtensionApi(directory) {
    const generatedRoot = resolve(directory);
    const rewriteFile = async (path, replacements) => {
        const fullPath = resolve(generatedRoot, path);
        let source = await readFile(fullPath, 'utf8');
    
        for (const [from, to] of replacements) {
            if (!source.includes(from)) {
                throw new Error(`Expected generated import not found in ${path}: ${from}`);
            }
    
            source = source.replace(from, to);
        }
    
        await writeFile(fullPath, source);
    };
    
    await rewriteFile('sdk.gen.ts', [
        [
            "import type { Client, ClientMeta, Options as Options2, RequestResult, TDataShape } from './client';\nimport { client } from './client.gen';",
            "import { client } from '@pterodactyl/sdk/api';\nimport type { Client, ClientMeta, Options as Options2, RequestResult, RequestData as TDataShape } from '@pterodactyl/sdk/api';",
        ],
    ]);
    
    await rewriteFile('@tanstack/react-query.gen.ts', [
        ["import { client } from '../client.gen';", "import { client } from '@pterodactyl/sdk/api';"],
    ]);
    
    await Promise.all([
        rm(resolve(generatedRoot, 'client.gen.ts'), { force: true }),
        rm(resolve(generatedRoot, 'client'), { force: true, recursive: true }),
        rm(resolve(generatedRoot, 'core'), { force: true, recursive: true }),
    ]);
    
}
