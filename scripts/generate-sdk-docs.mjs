import ts from 'typescript';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, extname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { classicSkins } from '../packages/sdk/bin/pterodactyl-theme.mjs';

// Fills the generated parts of the SDK docs: the view catalogue (views.json and the published view models),
// the UI primitive reference and the token reference in THEMES.md, and every code sample in either file,
// read from packages/sdk/docs-samples where `npm run sdk:check` compiles it. `--check` fails on stale docs.
const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sdk = resolve(root, 'packages/sdk');
const documents = ['README.md', 'THEMES.md'];
const read = (path) => readFileSync(resolve(sdk, path), 'utf8');

const program = ts.createProgram(
    ['types/index.d.ts', 'types/theme.d.ts', 'types/testing.d.ts'].map((path) => resolve(sdk, path)),
    {
        strict: true,
        noEmit: true,
        skipLibCheck: true,
        target: ts.ScriptTarget.ES2022,
        module: ts.ModuleKind.ESNext,
        moduleResolution: ts.ModuleResolutionKind.Bundler,
        jsx: ts.JsxEmit.ReactJSX,
        types: [],
    }
);
const checker = program.getTypeChecker();

function exported(file, name) {
    const source = program.getSourceFile(resolve(sdk, file));
    const symbol = checker.getExportsOfModule(checker.getSymbolAtLocation(source)).find((entry) => entry.name === name);

    if (!symbol) throw new Error(`${file} does not export ${name}.`);

    return symbol;
}

/** An interface the SDK declares, with every module augmentation merged in. */
const declared = (file, name) => checker.getDeclaredTypeOfSymbol(exported(file, name));

const properties = (type) => new Map(checker.getPropertiesOfType(type).map((property) => [property.name, property]));

/** The type a property is declared with, as written, or as the checker prints it. */
function typeText(property) {
    const declaration = property.valueDeclaration ?? property.declarations?.[0];

    if (declaration && ts.isPropertySignature(declaration) && declaration.type) {
        return declaration.type.getText().replace(/\s+/g, ' ');
    }

    return checker.typeToString(checker.getTypeOfSymbol(property));
}

/** The union a type node spells out, following type aliases, so its members keep their written order. */
function unionNode(node) {
    if (!node) return null;
    if (ts.isUnionTypeNode(node)) return node;
    if (!ts.isTypeReferenceNode(node)) return null;

    const symbol = checker.getSymbolAtLocation(node.typeName);
    const target = symbol && symbol.flags & ts.SymbolFlags.Alias ? checker.getAliasedSymbol(symbol) : symbol;

    return unionNode(target?.declarations?.find(ts.isTypeAliasDeclaration)?.type);
}

const optional = (property) => (property.flags & ts.SymbolFlags.Optional ? '?' : '');
const code = (text) => `\`${text.replaceAll('|', '\\|')}\``;
const documentation = (symbol) => ts.displayPartsToString(symbol.getDocumentationComment(checker)).replace(/\s+/g, ' ');

/** The literal values of a property's union type, such as `'sm' | 'md'`, in written order; null for any other type. */
function literals(property) {
    const declaration = property.valueDeclaration;
    const node = declaration && ts.isPropertySignature(declaration) ? unionNode(declaration.type) : null;

    if (node?.types.every((member) => ts.isLiteralTypeNode(member) && ts.isStringLiteral(member.literal))) {
        return node.types.map((member) => member.literal.text);
    }

    const type = checker.getTypeOfSymbol(property);
    const members = (type.isUnion() ? type.types : [type]).filter(
        (member) => !(member.flags & (ts.TypeFlags.Undefined | ts.TypeFlags.Null))
    );

    if (members.every((member) => member.flags & ts.TypeFlags.BooleanLiteral)) return 'boolean';
    if (members.every((member) => member.isStringLiteral() || member.isNumberLiteral())) {
        return members.map((member) => String(member.value));
    }

    return null;
}

function viewCatalogue() {
    const views = JSON.parse(read('views.json'));
    const models = properties(declared('types/views/registry.d.ts', 'ViewModels'));
    const inputs = properties(declared('types/views/registry.d.ts', 'ViewInputs'));
    const model = (name) => {
        const property = models.get(name);

        if (!property) throw new Error(`views.json names ${name}, which has no published model.`);

        return typeText(property);
    };
    const row = (withEngine) => ([name, view]) => {
        const input = inputs.get(name);
        const inputText = input
            ? [...properties(checker.getTypeOfSymbol(input)).values()]
                  .map((property) => code(`${property.name}${optional(property)}: ${typeText(property)}`))
                  .join(', ')
            : '';
        const area = view.areaSlots ? `<br>where it draws an area's navigation: ${view.areaSlots.map(code).join(' ')}` : '';

        return [
            code(name),
            view.context,
            code(model(name)),
            inputText,
            view.slots.map(code).join(' ') + area,
            ...(withEngine ? [(view.engineSlots ?? []).map(code).join(' ')] : []),
            view.fixtures.join(', '),
        ];
    };
    const table = (title, entries, withEngine = false) =>
        [
            `#### ${title}`,
            '',
            `| View | Context | Model | Inputs | Slots |${withEngine ? ' Engine slots |' : ''} Fixtures |`,
            `| --- | --- | --- | --- | --- |${withEngine ? ' --- |' : ''} --- |`,
            ...entries.map(row(withEngine)).map((cells) => `| ${cells.join(' | ')} |`),
        ].join('\n');
    const all = Object.entries(views);

    return [
        table(
            'Layouts',
            all.filter(([, view]) => view.kind === 'layout')
        ),
        table(
            'Pages',
            all.filter(([, view]) => view.kind === 'page'),
            true
        ),
        table(
            'Blocks',
            all.filter(([, view]) => view.kind === 'block')
        ),
    ].join('\n\n');
}

const kebab = (name) => name.replaceAll(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);

function primitiveReference() {
    const primitives = properties(declared('types/themes/primitives.d.ts', 'UiPrimitives'));
    const partLists = properties(checker.getTypeOfSymbol(exported('types/themes/primitives.d.ts', 'UI_PRIMITIVE_PARTS')));
    const rows = [...primitives.values()].map((primitive) => {
        const shape = properties(checker.getTypeOfSymbol(primitive));
        const parts = checker
            .getTypeArguments(checker.getTypeOfSymbol(partLists.get(primitive.name)))
            .map((part) => part.value);
        const state = [...properties(checker.getTypeOfSymbol(shape.get('state'))).values()].map((property) => {
            const values = literals(property);
            const attribute = code(`data-${kebab(property.name)}`);

            return values === 'boolean' || values === null ? attribute : `${attribute}: ${values.join(', ')}`;
        });
        const notes = [documentation(primitive), documentation(shape.get('state'))].filter(Boolean).join(' ');

        return [
            primitive.name,
            parts.map(code).join(', '),
            state.join('; ') || 'none',
            shape.has('props') ? 'yes' : 'no',
            notes,
        ];
    });

    return [
        '| Primitive | Parts | State on `root` | Replaceable | Notes |',
        '| --- | --- | --- | --- | --- |',
        ...rows.map((cells) => `| ${cells.join(' | ')} |`),
    ].join('\n');
}

/*
 * Every custom property the panel stylesheet declares, by group, with its role. A token the stylesheet
 * declares without an entry here, or an entry for one it no longer declares, fails the generator.
 */
const tokens = [
    [
        'Surfaces and text',
        [
            ['--background', 'Page background.'],
            ['--foreground', 'Body text.'],
            ['--card', 'Cards, rows and raised panels.'],
            ['--card-foreground', 'Text on cards.'],
            ['--popover', 'Raised fills: menus, tooltips, hover fills, stat tiles and chart cards.'],
            ['--popover-foreground', 'Text on popovers.'],
            ['--muted', 'Quiet fills: table heads, disabled inputs.'],
            ['--muted-foreground', 'Secondary text, icons and placeholders.'],
            ['--sunken', 'A recessed surface on a card: meter tracks, code chips.'],
            ['--border', 'Borders and dividers.'],
            ['--input', 'Input fills.'],
            ['--input-border', 'Input borders; defaults to `--input`.'],
            ['--ring', 'Focus rings and outlines.'],
        ],
    ],
    [
        'Actions and states',
        [
            ['--primary', 'Primary buttons, links in focus, selected items.'],
            ['--primary-foreground', 'Text on primary.'],
            ['--secondary', 'Secondary fills and hover states.'],
            ['--secondary-foreground', 'Text on secondary.'],
            ['--accent', 'Highlights: the active navigation underline, info alerts, the page progress bar.'],
            ['--accent-foreground', 'Text on accent.'],
            ['--destructive', 'Danger buttons, errors, offline status.'],
            ['--destructive-foreground', 'Text on destructive.'],
            ['--success', 'Success states and running servers.'],
            ['--success-foreground', 'Text on success.'],
            ['--warning', 'Warnings and starting servers.'],
            ['--warning-foreground', 'Text on warning.'],
            ['--caution', 'The orange button colour.'],
            ['--caution-foreground', 'Text on caution.'],
            ['--state-foreground', 'The text colour the success and warning foregrounds default to.'],
            ['--chart-*', 'Five chart series colours (`--chart-1` to `--chart-5`); generated avatars draw from them too.'],
        ],
    ],
    [
        'Sidebar',
        [
            ['--sidebar', 'Sidebar background; defaults to `--card`.'],
            ['--sidebar-foreground', 'Sidebar text.'],
            ['--sidebar-primary', 'The active sidebar item.'],
            ['--sidebar-primary-foreground', 'Text on the active sidebar item.'],
            ['--sidebar-accent', 'Sidebar hover fill.'],
            ['--sidebar-accent-foreground', 'Text on the sidebar hover fill.'],
            ['--sidebar-border', 'Sidebar borders.'],
            ['--sidebar-ring', 'Sidebar focus rings.'],
        ],
    ],
    [
        'Shape, type and layout',
        [
            ['--radius', 'The radius scale `rounded-sm` to `rounded-4xl` is computed from.'],
            ['--spacing', "Tailwind's spacing unit, which every `p-*`, `m-*`, `gap-*` and `w-*` multiplies: density."],
            ['--font-sans', 'Body typeface (`font-sans`).'],
            ['--font-header', 'Heading typeface (`font-header`).'],
            ['--layout-content-width', 'Width of page content (`max-w-panel`).'],
            ['--layout-gutter', 'The least space beside page content, which centres when wider; `page-width` applies both.'],
            ['--layout-page-spacing', "Space above and below a page's content (`1rem`, `1.5rem` from `sm`)."],
            ['--layout-auth-width', 'Width of the sign-in card (`max-w-auth`).'],
            ['--layout-sidebar-width', 'Width of sidebars (`w-sidebar`).'],
            ['--spinner-overlay-opacity', 'Opacity of the background behind a loading overlay.'],
        ],
    ],
    [
        'Shadows',
        [
            ['--shadow-*', "Tailwind's shadow scale (`shadow-2xs` to `shadow-2xl`) that lifts cards, tiles and dialogs; retune it in `@theme`."],
            ['--shadow-navigation-active', 'The underline of the active navigation item (`shadow-navigation-active`).'],
            ['--shadow-progress', 'The glow under the page progress bar (`shadow-progress`).'],
        ],
    ],
    [
        'Browser chrome',
        [
            ['--color-scheme', 'The `color-scheme` of native widgets: `light` or `dark`.'],
            ['--theme-color', 'The `<meta name="theme-color">` the panel keeps in sync.'],
            ['--selection', 'Text selection background; unset keeps the browser default.'],
            ['--selection-foreground', 'Text selection colour.'],
            ['--scrollbar-thumb', 'Scrollbar thumb on hover, and `scrollbar-color`.'],
            ['--scrollbar-thumb-fill', 'Scrollbar thumb at rest.'],
            ['--scrollbar-track', 'Scrollbar track.'],
            ['--brand-logo', 'A `url(…)` the layouts show as the logo, usually written by an image setting.'],
        ],
    ],
    [
        'Console',
        [
            ['--terminal-background', 'Console background (`bg-terminal`).'],
            ['--terminal-foreground', 'Uncoloured console output.'],
            ['--terminal-cursor', 'Console cursor; matches the background to hide it.'],
            ['--terminal-selection', 'Selected console text background.'],
            ['--terminal-selection-foreground', 'Selected console text.'],
            ['--terminal-ansi-*', 'The 16 ANSI colours: `black`, `red`, `green`, `yellow`, `blue`, `magenta`, `cyan`, `white` and their `bright-` forms.'],
        ],
    ],
    [
        'File editor',
        [
            ['--editor-background', 'Editor background.'],
            ['--editor-foreground', 'Editor text.'],
            ['--editor-caret', 'The caret.'],
            ['--editor-selection', 'Selected text background.'],
            ['--editor-selected-foreground', 'Text in selected search matches and completions.'],
            ['--editor-border', 'Editor borders and gutters.'],
            ['--editor-muted', 'Line numbers and other quiet text.'],
            ['--editor-active-line', 'The line with the caret.'],
            ['--editor-search-match', 'Search matches.'],
            ['--editor-search-match-selected', 'The current search match.'],
            ['--editor-tooltip', 'Tooltip and autocomplete background.'],
            ['--editor-tooltip-border', 'Tooltip and autocomplete border.'],
            ['--editor-panel-shadow', 'Shadow of the search panel.'],
            ['--editor-dialog-shadow', 'Shadow of editor dialogs.'],
            ['--editor-syntax-*', 'Syntax colours: `keyword`, `name`, `function`, `constant`, `definition`, `type`, `operator`, `string`, `invalid`.'],
        ],
    ],
];
/** Read by the panel, but declared by Tailwind, by theme-tokens.css, or by no stylesheet until a theme sets them. */
const undeclared = [
    '--spacing',
    '--font-sans',
    '--font-header',
    '--selection',
    '--selection-foreground',
    '--brand-logo',
    '--shadow-*',
    '--shadow-navigation-active',
    '--shadow-progress',
];

const classicRoot = resolve(root, 'resources/scripts/themes/classic');

/** A classic file with the classic files it imports, as one text. */
function classicSource(file, seen = new Set()) {
    if (seen.has(file) || !existsSync(file)) return '';
    seen.add(file);

    const text = readFileSync(file, 'utf8');
    const imports = [...text.matchAll(/from\s+'(\.{1,2}\/[^']+)'/g)].map((match) => resolve(dirname(file), match[1]));
    const files = imports.flatMap((path) => ['.tsx', '.ts'].map((suffix) => path + suffix)).filter(existsSync);

    return [text, ...files.map((path) => classicSource(path, seen))].join('\n');
}

/** What classic draws, by primitive (its skin) and by view (its source), to list where each token is read. */
function classicConsumers() {
    const views = Object.entries(JSON.parse(read('views.json'))).flatMap(([name, view]) =>
        view.classic ? [[code(name), classicSource(resolve(classicRoot, view.classic.file))]] : []
    );

    return [...[...classicSkins(classicRoot)].map(([primitive, source]) => [primitive, source()]), ...views];
}

const colourUtilities =
    'bg|text|border(?:-[trblxyse])?|ring(?:-offset)?|outline|fill|stroke|divide|from|via|to|shadow|accent|caret|decoration|placeholder';

function tokenReference() {
    const css = read('panel.css').replace(/\/\*[\s\S]*?\*\//g, '');
    const found = new Set([...css.matchAll(/(--[\w-]+)\s*:/g)].map((match) => match[1]));
    const matches = (entry, token) => (entry.endsWith('*') ? token.startsWith(entry.slice(0, -1)) : entry === token);
    const entries = tokens.flatMap(([, list]) => list.map(([entry]) => entry));
    const missing = [...found].filter((token) => !entries.some((entry) => matches(entry, token)));
    const stale = entries.filter(
        (entry) => !undeclared.includes(entry) && ![...found].some((token) => matches(entry, token))
    );

    if (missing.length || stale.length) {
        throw new Error(
            `Update the token reference in scripts/generate-sdk-docs.mjs. ` +
                `Undocumented: ${missing.join(', ') || 'none'}. No longer declared: ${stale.join(', ') || 'none'}.`
        );
    }

    // `--color-card: var(--card)` makes `bg-card`, `text-card` and the rest.
    const theme = read('theme-tokens.css');
    const names = (token) => ({
        color: new RegExp(`--color-([\\w-]+):\\s*var\\(${token}\\)`).exec(theme)?.[1],
        container: new RegExp(`--container-([\\w-]+):\\s*var\\(${token}\\)`).exec(theme)?.[1],
    });
    const utility = (token) => {
        const { color, container } = names(token);

        return color || container ? code(`*-${color ?? container}`) : '';
    };
    // The custom utilities of panel.css with the tokens they read, such as `page-width`.
    const custom = [...css.matchAll(/@utility ([\w-]+) \{([^}]*)\}/g)];
    const consumers = classicConsumers();
    // A group of tokens, such as `--chart-*`: their utilities, or Tailwind's shadow scale.
    const groupPattern = (prefix) =>
        prefix === 'shadow-'
            ? '(?<![\\w-])shadow-(?:2xs|xs|sm|md|lg|xl|2xl)(?![\\w-])'
            : `(?<![\\w-])(?:${colourUtilities})-${prefix}[\\w-]+|\\(--${prefix}`;
    const readers = (token) => {
        const { color, container } = names(token);
        const pattern = new RegExp(
            [
                ...(token.endsWith('*') ? [groupPattern(token.slice(2, -1))] : [`\\(${token}\\)`]),
                ...(color ? [`(?<![\\w-])(?:${colourUtilities})-${color}(?![\\w-])`] : []),
                ...(container ? [`(?<![\\w-])(?:max-w|min-w|w)-${container}(?![\\w-])`] : []),
                ...(/^--(font|shadow)-/.test(token) ? [`(?<![\\w-])${token.slice(2)}(?![\\w-])`] : []),
                ...custom
                    .filter(([, , body]) => body.includes(`var(${token})`))
                    .map(([, name]) => `(?<![\\w-])${name}(?![\\w-])`),
            ].join('|')
        );
        const found = consumers.filter(([, source]) => pattern.test(source)).map(([name]) => name);

        return found.length > 6 ? `${found.slice(0, 6).join(', ')} and ${found.length - 6} more` : found.join(', ');
    };

    return tokens
        .map(([group, list]) =>
            [
                `#### ${group}`,
                '',
                '| Token | Role | Utilities | Classic reads it in |',
                '| --- | --- | --- | --- |',
                ...list.map(
                    ([token, role]) =>
                        `| ${code(token)} | ${role} | ${token.endsWith('*') ? '' : utility(token)} | ${readers(token)} |`
                ),
            ].join('\n')
        )
        .join('\n\n');
}

const languages = { '.tsx': 'tsx', '.ts': 'ts', '.css': 'css', '.json': 'json', '.mjs': 'js', '.js': 'js' };

const marker = /^\s*(?:\/\/|\/\*|\{\/\*)\s*#(?:end)?region\b.*$/;

/** The lines of each `#region name` … `#endregion` block in a file, dedented and joined by a blank line. */
function regions(path, lines, name) {
    const blocks = [];

    lines.forEach((line, start) => {
        if (!new RegExp(`#region ${name}\\b`).test(line)) return;

        const end = lines.findIndex((candidate, index) => index > start && /#endregion/.test(candidate));
        const block = lines.slice(start + 1, end);
        const indent = Math.min(...block.filter((entry) => entry.trim()).map((entry) => /^\s*/.exec(entry)[0].length));

        blocks.push(block.map((entry) => entry.slice(indent)).join('\n'));
    });

    if (!blocks.length) throw new Error(`docs-samples/${path} has no region "${name}".`);

    return blocks.join('\n\n').split('\n');
}

/** A sample file, or its regions of one name, without the region markers. */
function sample(reference) {
    const [path, region] = reference.split('#');
    const lines = read(`docs-samples/${path}`).split('\n');
    const body = (region ? regions(path, lines, region) : lines)
        .filter((line) => !marker.test(line))
        .join('\n')
        .trim();

    return `\`\`\`${languages[extname(path)] ?? ''}\n${body}\n\`\`\``;
}

function fill(readme) {
    const generated = { views: viewCatalogue, primitives: primitiveReference, tokens: tokenReference };
    const output = readme.replace(
        /(<!-- generated:(\w+) -->)[\s\S]*?(<!-- \/generated:\2 -->)/g,
        (_, open, name, close) => {
            if (!generated[name]) throw new Error(`Unknown generated section "${name}".`);

            return `${open}\n${generated[name]()}\n${close}`;
        }
    );

    return output.replace(
        /(<!-- sample: (\S+) -->)[\s\S]*?(<!-- \/sample -->)/g,
        (_, open, reference, close) => `${open}\n${sample(reference)}\n${close}`
    );
}

const stale = documents.filter((document) => {
    const path = resolve(sdk, document);
    const text = readFileSync(path, 'utf8');
    const output = fill(text);

    if (output !== text && !process.argv.includes('--check')) {
        writeFileSync(path, output);
    }

    return output !== text;
});

if (stale.length && process.argv.includes('--check')) {
    console.error(`${stale.map((document) => `packages/sdk/${document}`).join(' and ')} stale. Run npm run sdk:generate.`);
    process.exit(1);
}

console.log(`SDK: docs samples and references ${stale.length ? `regenerated in ${stale.join(', ')}` : 'current'}`);
