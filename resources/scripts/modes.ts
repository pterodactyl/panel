export interface Mode {
    name: string;
    mime: string;
    mimes?: string[];
    mode: string;
    ext?: string[];
    alias?: string[];
    file?: RegExp;
}

const modes: Mode[] = [
    { name: 'C', mime: 'text/x-csrc', mode: 'clike', ext: ['c', 'h', 'ino'] },
    {
        name: 'C++',
        mime: 'text/x-c++src',
        mode: 'clike',
        ext: ['cpp', 'c++', 'cc', 'cxx', 'hpp', 'h++', 'hh', 'hxx'],
        alias: ['cpp'],
    },
    { name: 'C#', mime: 'text/x-csharp', mode: 'clike', ext: ['cs'], alias: ['csharp', 'cs'] },
    { name: 'CSS', mime: 'text/css', mode: 'css', ext: ['css'] },
    { name: 'CQL', mime: 'text/x-cassandra', mode: 'sql', ext: ['cql'] },
    { name: 'Diff', mime: 'text/x-diff', mode: 'diff', ext: ['diff', 'patch'] },
    { name: 'Dockerfile', mime: 'text/x-dockerfile', mode: 'dockerfile', file: /^Dockerfile$/ },
    { name: 'Git Markdown', mime: 'text/x-gfm', mode: 'gfm', file: /^(readme|contributing|history|license).md$/i },
    { name: 'Golang', mime: 'text/x-go', mode: 'go', ext: ['go'] },
    { name: 'HTML', mime: 'text/html', mode: 'htmlmixed', ext: ['html', 'htm', 'handlebars', 'hbs'], alias: ['xhtml'] },
    { name: 'HTTP', mime: 'message/http', mode: 'http' },
    {
        name: 'JavaScript',
        mime: 'text/javascript',
        mimes: [
            'text/javascript',
            'text/ecmascript',
            'application/javascript',
            'application/x-javascript',
            'application/ecmascript',
        ],
        mode: 'javascript',
        ext: ['js'],
        alias: ['ecmascript', 'js', 'node'],
    },
    {
        name: 'JSON',
        mime: 'application/json',
        mimes: ['application/json', 'application/x-json'],
        mode: 'javascript',
        ext: ['json', 'map'],
        alias: ['json5'],
    },
    { name: 'Lua', mime: 'text/x-lua', mode: 'lua', ext: ['lua'] },
    { name: 'Markdown', mime: 'text/x-markdown', mode: 'markdown', ext: ['markdown', 'md', 'mkd'] },
    { name: 'MariaDB', mime: 'text/x-mariadb', mode: 'sql' },
    { name: 'MS SQL', mime: 'text/x-mssql', mode: 'sql' },
    { name: 'MySQL', mime: 'text/x-mysql', mode: 'sql' },
    { name: 'Nginx', mime: 'text/x-nginx-conf', mode: 'nginx', file: /nginx.*\.conf$/i },
    {
        name: 'PHP',
        mime: 'text/x-php',
        mimes: ['text/x-php', 'application/x-httpd-php', 'application/x-httpd-php-open'],
        mode: 'php',
        ext: ['php', 'php3', 'php4', 'php5', 'php7', 'phtml'],
    },
    { name: 'Plain Text', mime: 'text/plain', mode: 'null', ext: ['txt', 'text', 'conf', 'def', 'list', 'log'] },
    { name: 'PostgreSQL', mime: 'text/x-pgsql', mode: 'sql' },
    {
        name: 'Properties',
        mime: 'text/x-properties',
        mode: 'properties',
        ext: ['properties', 'ini', 'in'],
        alias: ['ini', 'properties'],
    },
    { name: 'Pug', mime: 'text/x-pug', mimes: ['text/x-pug', 'text/x-jade'], mode: 'null', ext: ['pug'] },
    {
        name: 'Python',
        mime: 'text/x-python',
        mode: 'python',
        ext: ['BUILD', 'bzl', 'py', 'pyw'],
        file: /^(BUCK|BUILD)$/,
    },
    { name: 'Ruby', mime: 'text/x-ruby', mode: 'ruby', ext: ['rb'], alias: ['jruby', 'macruby', 'rake', 'rb', 'rbx'] },
    { name: 'Rust', mime: 'text/x-rustsrc', mode: 'rust', ext: ['rs'] },
    { name: 'Sass', mime: 'text/x-sass', mode: 'sass', ext: ['sass'] },
    { name: 'SCSS', mime: 'text/x-scss', mode: 'css', ext: ['scss'] },
    {
        name: 'Shell',
        mime: 'text/x-sh',
        mimes: ['text/x-sh', 'application/x-sh'],
        mode: 'shell',
        ext: ['sh', 'ksh', 'bash'],
        alias: ['bash', 'sh', 'zsh'],
        file: /^PKGBUILD$/,
    },
    { name: 'SQL', mime: 'text/x-sql', mode: 'sql', ext: ['sql'] },
    { name: 'SQLite', mime: 'text/x-sqlite', mode: 'sql' },
    { name: 'TOML', mime: 'text/x-toml', mode: 'toml', ext: ['toml'] },
    { name: 'TypeScript', mime: 'application/typescript', mode: 'javascript', ext: ['ts'], alias: ['ts'] },
    { name: 'Vue', mime: 'script/x-vue', mimes: ['script/x-vue', 'text/x-vue'], mode: 'vue', ext: ['vue'] },
    {
        name: 'XML',
        mime: 'application/xml',
        mimes: ['application/xml', 'text/xml'],
        mode: 'xml',
        ext: ['xml', 'xsl', 'xsd', 'svg'],
        alias: ['rss', 'wsdl', 'xsd'],
    },
    {
        name: 'YAML',
        mime: 'text/x-yaml',
        mimes: ['text/x-yaml', 'text/yaml'],
        mode: 'yaml',
        ext: ['yaml', 'yml'],
        alias: ['yml'],
    },
];

export default modes;
