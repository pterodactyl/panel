import { useEffect } from 'react';
import { Link, useNavigate, useSearch } from '@tanstack/react-router';
import { LayoutTemplate } from 'lucide-react';
import { getBootstrapTheme } from '@/bootstrap';
import { parseWorkbenchSearch, type WorkbenchSearch, type WorkbenchWidth } from '@/router/search';
import { getUiPrimitive, uiPrimitives } from '@/themes/gallery';
import '@/views/catalog';
import { CodeLine } from '@/views/inspector/parts';
import { useViewDefinitions } from '@/views/registry';
import { useTheme, type ThemeScheme } from '@/views/theme';
import PrimitivePreview from './PrimitivePreview';
import { Segmented } from './stage';
import ViewPreview from './ViewPreview';
import WorkbenchSidebar from './WorkbenchSidebar';

const widths: { value: WorkbenchWidth; label: string }[] = [
    { value: 'phone', label: 'Phone 390' },
    { value: 'tablet', label: 'Tablet 820' },
    { value: 'desktop', label: 'Desktop 1280' },
    { value: 'full', label: 'Full' },
];

function Welcome() {
    const definitions = useViewDefinitions();
    const { inspect } = getBootstrapTheme();
    const withFixtures = definitions.filter((definition) => Object.keys(definition.fixtures ?? {}).length > 0);

    return (
        <div className='mx-auto max-w-2xl space-y-6 p-6 lg:p-10'>
            <div>
                <h2 className='text-xl font-semibold text-foreground'>Design every view without a backend</h2>
                <p className='mt-2 text-sm leading-relaxed text-foreground/80'>
                    Pick a view to render it from its fixtures through classic, the active theme and every extension
                    layer, at a phone, tablet or desktop width and in either colour scheme. The UI kit shows each
                    primitive in all its variants.
                </p>
            </div>
            <dl className='grid grid-cols-3 gap-3 text-center'>
                {[
                    { label: 'Views', value: definitions.length },
                    { label: 'With fixtures', value: withFixtures.length },
                    { label: 'Primitives', value: Object.keys(uiPrimitives).length },
                ].map((stat) => (
                    <div key={stat.label} className='rounded-sm border border-border bg-card p-3'>
                        <dt className='text-xs text-muted-foreground'>{stat.label}</dt>
                        <dd className='text-2xl font-semibold tabular-nums text-foreground'>{stat.value}</dd>
                    </div>
                ))}
            </dl>
            <ul className='space-y-2 text-sm text-foreground/80'>
                <li>
                    <kbd className='rounded-xs border border-border px-1 font-mono text-xs'>/</kbd> searches views.
                </li>
                <li>
                    {inspect ? (
                        <>
                            <kbd className='rounded-xs border border-border px-1 font-mono text-xs'>Alt+Shift+I</kbd>{' '}
                            inspects any view on any page, previews here included.
                        </>
                    ) : (
                        <>
                            <a href='/panel/theme-workbench?inspect=1' className='text-accent hover:underline'>
                                Turn on the inspector
                            </a>{' '}
                            to pick any view on any page and see its layers, model and tokens.
                        </>
                    )}
                </li>
            </ul>
            <div className='space-y-2'>
                <p className='text-sm text-foreground/80'>
                    In a theme project, list views and eject classic&apos;s source:
                </p>
                <CodeLine code='npx pterodactyl-theme list' />
                <CodeLine code='npx pterodactyl-theme eject dashboard.serverCard' />
            </div>
        </div>
    );
}

function Missing({ name }: { name: string }) {
    return (
        <div className='p-6 text-sm text-muted-foreground lg:p-10'>
            No view or primitive is named <span className='font-mono text-foreground'>{name}</span>. Extension views
            appear once their extension has loaded.
        </div>
    );
}

/** `/panel/theme-workbench`: views and the UI kit rendered from fixtures, for theme authors. */
export default function ThemeWorkbench() {
    const search = parseWorkbenchSearch(useSearch({ strict: false }));
    const navigate = useNavigate();
    const theme = useTheme();
    const { preview, safeMode } = getBootstrapTheme();
    const definitions = useViewDefinitions();
    const scheme: ThemeScheme = search.scheme && theme.schemes.includes(search.scheme) ? search.scheme : theme.scheme;
    const width = search.width ?? 'full';
    const definition = search.view ? definitions.find((candidate) => candidate.name === search.view) : undefined;
    const primitive = search.ui ? getUiPrimitive(search.ui) : undefined;
    const update = (next: Partial<WorkbenchSearch>) =>
        void navigate({ to: '.', search: { ...search, ...next }, replace: true, viewTransition: false });

    useEffect(() => {
        document.title = 'Admin · Theme workbench';
    }, []);

    const content = () => {
        if (definition) {
            return (
                <ViewPreview
                    key={definition.name}
                    definition={definition}
                    search={search}
                    scheme={scheme}
                    width={width}
                    onFixture={(fixture) => update({ fixture })}
                />
            );
        }

        if (search.ui && primitive) {
            return (
                <PrimitivePreview
                    key={search.ui}
                    name={search.ui}
                    primitive={primitive}
                    scheme={scheme}
                    width={width}
                />
            );
        }

        const missing = search.view ?? search.ui;

        return missing ? <Missing name={missing} /> : <Welcome />;
    };

    return (
        // The inspector reaches into the previews and leaves the workbench's own controls usable.
        <div data-inspector className='flex flex-col lg:h-[calc(100dvh-3.5rem)]'>
            <div className='flex flex-wrap items-center gap-3 border-b border-border bg-card px-4 py-2'>
                <div className='flex min-w-0 flex-wrap items-center gap-2'>
                    <LayoutTemplate className='h-4 w-4 text-muted-foreground' aria-hidden />
                    <h1 className='text-sm font-semibold text-foreground'>Theme workbench</h1>
                    <span className='font-mono text-xs text-muted-foreground' title='The layer chain, bottom first'>
                        {['classic', ...theme.chain.map((entry) => entry.id)].join(' → ')}
                    </span>
                    {preview && (
                        <span className='rounded-sm bg-accent/15 px-1.5 py-0.5 text-xs text-accent'>Previewing</span>
                    )}
                    {safeMode && (
                        <span className='rounded-sm bg-warning/15 px-1.5 py-0.5 text-xs text-warning'>Safe mode</span>
                    )}
                </div>
                <div className='ml-auto flex flex-wrap items-center gap-2'>
                    <Segmented
                        label='Colour scheme'
                        value={scheme}
                        onChange={(value) => update({ scheme: value })}
                        options={(['dark', 'light'] as const).map((value) => ({
                            value,
                            label: value === 'dark' ? 'Dark' : 'Light',
                            disabled: !theme.schemes.includes(value),
                            title: theme.schemes.includes(value) ? undefined : `${theme.id} does not offer ${value}`,
                        }))}
                    />
                    <Segmented
                        label='Viewport'
                        value={width}
                        onChange={(value) => update({ width: value === 'full' ? undefined : value })}
                        options={widths}
                    />
                    <Link to='/panel/themes' className='text-xs text-accent hover:underline'>
                        Themes
                    </Link>
                </div>
            </div>
            <div className='flex min-h-0 flex-1 flex-col lg:flex-row'>
                <aside className='h-72 shrink-0 border-b border-border bg-card lg:h-auto lg:w-72 lg:border-r lg:border-b-0'>
                    <WorkbenchSidebar search={search} />
                </aside>
                <div className='flex min-w-0 flex-1 overflow-y-auto'>{content()}</div>
            </div>
        </div>
    );
}
