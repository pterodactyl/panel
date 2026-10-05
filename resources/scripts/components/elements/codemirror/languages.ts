import type { Extension } from '@codemirror/state';
import { StreamLanguage } from '@codemirror/language';
import { cpp } from '@codemirror/lang-cpp';
import { css } from '@codemirror/lang-css';
import { go } from '@codemirror/lang-go';
import { html } from '@codemirror/lang-html';
import { java } from '@codemirror/lang-java';
import { javascript } from '@codemirror/lang-javascript';
import { json } from '@codemirror/lang-json';
import { markdown } from '@codemirror/lang-markdown';
import { php } from '@codemirror/lang-php';
import { python } from '@codemirror/lang-python';
import { rust } from '@codemirror/lang-rust';
import { sass } from '@codemirror/lang-sass';
import * as sqlDialect from '@codemirror/lang-sql';
import { vue } from '@codemirror/lang-vue';
import { xml } from '@codemirror/lang-xml';
import { yaml } from '@codemirror/lang-yaml';
import { c, csharp } from '@codemirror/legacy-modes/mode/clike';
import { diff } from '@codemirror/legacy-modes/mode/diff';
import { dockerFile } from '@codemirror/legacy-modes/mode/dockerfile';
import { http } from '@codemirror/legacy-modes/mode/http';
import { lua } from '@codemirror/legacy-modes/mode/lua';
import { nginx } from '@codemirror/legacy-modes/mode/nginx';
import { properties } from '@codemirror/legacy-modes/mode/properties';
import { ruby } from '@codemirror/legacy-modes/mode/ruby';
import { shell } from '@codemirror/legacy-modes/mode/shell';
import { toml } from '@codemirror/legacy-modes/mode/toml';

type LanguageFactory = () => Extension;

const legacy = (parser: Parameters<typeof StreamLanguage.define>[0]): Extension => StreamLanguage.define(parser);

const languageByMime = new Map<string, LanguageFactory>(
    Object.entries({
        'text/x-csrc': () => legacy(c),
        'text/x-c++src': () => cpp(),
        'text/x-csharp': () => legacy(csharp),
        'text/css': () => css(),
        'text/x-scss': () => sass(),
        'text/x-sass': () => sass({ indented: true }),
        'text/x-cassandra': () => sqlDialect.sql({ dialect: sqlDialect.Cassandra }),
        'text/x-diff': () => legacy(diff),
        'text/x-dockerfile': () => legacy(dockerFile),
        'text/x-gfm': () => markdown(),
        'text/x-go': () => go(),
        'text/html': () => html(),
        'message/http': () => legacy(http),
        'text/x-java': () => java(),
        'text/javascript': () => javascript({ jsx: true }),
        'text/ecmascript': () => javascript({ jsx: true }),
        'application/javascript': () => javascript({ jsx: true }),
        'application/x-javascript': () => javascript({ jsx: true }),
        'application/ecmascript': () => javascript({ jsx: true }),
        'application/json': () => json(),
        'application/x-json': () => json(),
        'text/x-lua': () => legacy(lua),
        'text/x-markdown': () => markdown(),
        'text/x-mariadb': () => sqlDialect.sql({ dialect: sqlDialect.MariaSQL }),
        'text/x-mssql': () => sqlDialect.sql({ dialect: sqlDialect.MSSQL }),
        'text/x-mysql': () => sqlDialect.sql({ dialect: sqlDialect.MySQL }),
        'text/x-nginx-conf': () => legacy(nginx),
        'text/x-php': () => php(),
        'application/x-httpd-php': () => php(),
        'application/x-httpd-php-open': () => php(),
        'text/x-pgsql': () => sqlDialect.sql({ dialect: sqlDialect.PostgreSQL }),
        'text/x-properties': () => legacy(properties),
        'text/x-python': () => python(),
        'text/x-ruby': () => legacy(ruby),
        'text/x-rustsrc': () => rust(),
        'text/x-sh': () => legacy(shell),
        'application/x-sh': () => legacy(shell),
        'text/x-sql': () => sqlDialect.sql({ dialect: sqlDialect.StandardSQL }),
        'text/x-sqlite': () => sqlDialect.sql({ dialect: sqlDialect.SQLite }),
        'text/x-toml': () => legacy(toml),
        'application/typescript': () => javascript({ jsx: true, typescript: true }),
        'script/x-vue': () => vue(),
        'text/x-vue': () => vue(),
        'application/xml': () => xml(),
        'text/xml': () => xml(),
        'text/x-yaml': () => yaml(),
        'text/yaml': () => yaml(),
    })
);

export const resolveCodemirrorLanguage = (mime: string): Extension => languageByMime.get(mime)?.() ?? [];
