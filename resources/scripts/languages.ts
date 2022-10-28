import { LanguageSupport, StreamLanguage } from '@codemirror/language';
import { loadLanguage } from '@uiw/codemirror-extensions-langs';

/**
 * The string is the file extension and the language is the syntax highlighting
 */
export const languages: Record<string, StreamLanguage<unknown> | LanguageSupport | null> = {
    c: loadLanguage('c'),
    h: loadLanguage('c'),
    ino: loadLanguage('c'),
    cs: loadLanguage('csharp'),
    css: loadLanguage('css'),
    diff: loadLanguage('diff'),
    patch: loadLanguage('diff'),
    go: loadLanguage('go'),
    html: loadLanguage('html'),
    java: loadLanguage('java'),
    js: loadLanguage('javascript'),
    jsx: loadLanguage('jsx'),
    json: loadLanguage('json'),
    map: loadLanguage('json'),
    lua: loadLanguage('lua'),
    markdown: loadLanguage('markdown'),
    mkd: loadLanguage('markdown'),
    md: loadLanguage('markdown'),
    sql: loadLanguage('sql'),
    php: loadLanguage('php'),
    properties: loadLanguage('properties'),
    ini: loadLanguage('properties'),
    in: loadLanguage('properties'),
    py: loadLanguage('python'),
    pyw: loadLanguage('python'),
    rb: loadLanguage('ruby'),
    rs: loadLanguage('rust'),
    sass: loadLanguage('sass'),
    sh: loadLanguage('shell'),
    bash: loadLanguage('shell'),
    toml: loadLanguage('toml'),
    ts: loadLanguage('typescript'),
    tsx: loadLanguage('tsx'),
    xml: loadLanguage('xml'),
    yaml: loadLanguage('yaml'),
    yml: loadLanguage('yaml'),
};
