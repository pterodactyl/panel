import { Suspense, useCallback, useContext, useMemo, useState, useSyncExternalStore } from 'react';
import { Link } from '@tanstack/react-router';
import { DatabaseZap } from 'lucide-react';
import Spinner from '@/components/elements/Spinner';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { cn } from '@/lib/cn';
import type { WorkbenchSearch, WorkbenchWidth } from '@/router/search';
import { ServerContext } from '@/state/server/context';
import { ViewInspectionContext, type ViewInspection, type ViewProbe } from '@/views/inspect';
import { modelJson } from '@/views/inspector/model';
import { CopyButton, LayerStack, ModelTree, OverrideHelp, Section, SlotList } from '@/views/inspector/parts';
import { renderingLayer } from '@/views/inspector/snippets';
import { OPAQUE_PAGES, pageSlots, VIEW_PAGES, type PageViewName } from '@/views/pages';
import { getViewChain, getViewDefinition, useViewDefinitions, type ViewDefinition } from '@/views/registry';
import { EngineSlots } from '@/views/slotCheck';
import type { ThemeScheme } from '@/views/theme';
import { LooseView, ViewFixtureProvider } from '@/views/View';
import FixtureBoundary from './FixtureBoundary';
import PreviewFrame from './PreviewFrame';
import { frameSize, useWidth } from './stage';

export const ALL_FIXTURES = '*';

const isPageViewName = (name: string): name is PageViewName =>
    Object.hasOwn(VIEW_PAGES, name) || Object.hasOwn(OPAQUE_PAGES, name);

type NestedFixtures = Readonly<Record<string, string>>;

interface FrameOptions {
    definition: ViewDefinition;
    nested: NestedFixtures;
    /** A page view rendered inside a layout preview. */
    content: string | null;
    scheme: ThemeScheme;
    width: WorkbenchWidth;
    available: number;
}

function RoutedContent({ page }: { page: string | null }) {
    if (page) {
        return <LooseView name={page} />;
    }

    return (
        <div className='m-4 flex min-h-48 items-center justify-center rounded-sm border-2 border-dashed border-border p-8 text-sm text-muted-foreground'>
            The routed page renders here.
        </div>
    );
}

function FixtureFrame({
    fixture,
    options,
    focused,
    onFocus,
    onProbe,
}: {
    fixture: string;
    options: FrameOptions;
    focused: boolean;
    onFocus: (() => void) | null;
    onProbe: (fixture: string, probe: ViewProbe) => () => void;
}) {
    const { definition, nested, content, scheme, width, available } = options;
    const parent = useContext(ViewInspectionContext);
    const wrap = parent?.wrap ?? false;
    const inspection = useMemo<ViewInspection>(
        () => ({ wrap, collect: (probe) => onProbe(fixture, probe) }),
        [wrap, onProbe, fixture]
    );
    const fixtures = useMemo(() => ({ ...nested, [definition.name]: fixture }), [nested, definition.name, fixture]);
    const size = frameSize(width, available);
    const rendered = (
        <LooseView name={definition.name}>
            {definition.kind === 'layout' ? <RoutedContent page={content} /> : null}
        </LooseView>
    );
    // Pages and layouts bring their own spacing; a block sits in some, as it would on a page.
    const view = definition.kind === 'block' ? <div className='p-4'>{rendered}</div> : rendered;
    const engineSlots = useMemo(
        () => (isPageViewName(definition.name) ? (pageSlots(definition.name) ?? []) : []),
        [definition.name]
    );

    return (
        <figure className='min-w-0'>
            <figcaption className='mb-1.5 flex items-center gap-2 text-xs text-muted-foreground'>
                {onFocus ? (
                    <button
                        type='button'
                        className={cn('font-mono hover:text-foreground', focused && 'text-foreground underline')}
                        onClick={onFocus}
                    >
                        {fixture}
                    </button>
                ) : (
                    <span className='font-mono text-foreground'>{fixture}</span>
                )}
                <span>
                    {size.width}px{size.scale < 1 && ` at ${Math.round(size.scale * 100)}%`}
                </span>
            </figcaption>
            <div className={cn('rounded-sm ring-1 ring-border', focused && onFocus && 'ring-2 ring-primary')}>
                <PreviewFrame
                    label={`${definition.name}: ${fixture}`}
                    width={size.width}
                    scale={size.scale}
                    scheme={scheme}
                >
                    <ViewInspectionContext.Provider value={inspection}>
                        <ViewFixtureProvider fixtures={fixtures}>
                            <Suspense fallback={<Spinner size='large' centered />}>
                                <FixtureBoundary key={`${definition.name}:${fixture}:${content}`}>
                                    <EngineSlots.Provider value={engineSlots}>
                                        {definition.context === 'server' ? (
                                            <ServerContext.Provider>{view}</ServerContext.Provider>
                                        ) : (
                                            view
                                        )}
                                    </EngineSlots.Provider>
                                </FixtureBoundary>
                            </Suspense>
                        </ViewFixtureProvider>
                    </ViewInspectionContext.Provider>
                </PreviewFrame>
            </div>
        </figure>
    );
}

function NoFixtures({ name }: { name: string }) {
    return (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <DatabaseZap />
                </EmptyMedia>
                <EmptyTitle>No fixtures yet</EmptyTitle>
                <EmptyDescription>
                    <span className='font-mono'>{name}</span> still renders the panel&apos;s own component from live
                    data, so it cannot render here without a backend. You can still override it; its layers and snippet
                    are beside this.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function ProbeDetails({ probe }: { probe: ViewProbe }) {
    const view = useSyncExternalStore(probe.subscribe, probe.current);
    const hasInputs = Object.keys(view.inputs).length > 0;

    return (
        <>
            <Section title='Layers' count={view.layers.length}>
                <LayerStack view={view} />
            </Section>
            <Section title='Model'>
                <div className='mb-2 flex justify-end'>
                    <CopyButton text={modelJson(view.model)} label='Copy JSON' />
                </div>
                <ModelTree value={view.model} />
            </Section>
            {hasInputs && (
                <Section title='Inputs' open={false}>
                    <ModelTree value={view.inputs} />
                </Section>
            )}
        </>
    );
}

function NestedViews({
    probes,
    root,
    nested,
    search,
    onNested,
}: {
    probes: readonly ViewProbe[];
    root: string;
    nested: NestedFixtures;
    search: WorkbenchSearch;
    onNested: (name: string, fixture: string) => void;
}) {
    const names = [...new Set(probes.map((probe) => probe.current().definition.name))].filter((name) => name !== root);

    if (!names.length) {
        return null;
    }

    return (
        <Section title='Nested views' count={names.length}>
            <ul className='space-y-2'>
                {names.map((name) => {
                    const fixtures = Object.keys(getViewDefinition(name)?.fixtures ?? {});

                    return (
                        <li key={name} className='flex items-center gap-2'>
                            <Link
                                to='/panel/theme-workbench'
                                search={{ ...search, ui: undefined, view: name, fixture: undefined }}
                                className='min-w-0 flex-1 truncate font-mono text-xs text-accent hover:underline'
                            >
                                {name}
                            </Link>
                            {fixtures.length > 1 && (
                                <select
                                    aria-label={`Fixture of ${name}`}
                                    className='rounded-sm border border-border bg-background py-0.5 pr-7 pl-2 text-xs text-foreground'
                                    value={nested[name] ?? fixtures[0]}
                                    onChange={(event) => onNested(name, event.target.value)}
                                >
                                    {fixtures.map((fixture) => (
                                        <option key={fixture} value={fixture}>
                                            {fixture}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </li>
                    );
                })}
            </ul>
        </Section>
    );
}

function FixtureTabs({
    fixtures,
    current,
    onSelect,
}: {
    fixtures: readonly string[];
    current: string;
    onSelect: (fixture: string) => void;
}) {
    return (
        <div role='tablist' aria-label='Fixtures' className='flex flex-wrap gap-1'>
            {[...fixtures, ...(fixtures.length > 1 ? [ALL_FIXTURES] : [])].map((fixture) => (
                <button
                    key={fixture}
                    type='button'
                    role='tab'
                    aria-selected={fixture === current}
                    className={cn(
                        'rounded-sm border px-2.5 py-1 font-mono text-xs transition-colors',
                        fixture === current
                            ? 'border-primary bg-primary/10 text-foreground'
                            : 'border-border text-muted-foreground hover:text-foreground'
                    )}
                    onClick={() => onSelect(fixture)}
                >
                    {fixture === ALL_FIXTURES ? 'All side by side' : fixture}
                </button>
            ))}
        </div>
    );
}

function PageInside({ value, onChange }: { value: string | null; onChange: (page: string | null) => void }) {
    const pages = useViewDefinitions().filter((page) => page.kind === 'page' && page.fixtures);

    return (
        <label className='flex items-center gap-2 text-xs text-muted-foreground'>
            Page inside
            <select
                className='rounded-sm border border-border bg-background py-0.5 pr-7 pl-2 text-xs text-foreground'
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value || null)}
            >
                <option value=''>Placeholder</option>
                {pages.map((page) => (
                    <option key={page.name} value={page.name}>
                        {page.name}
                    </option>
                ))}
            </select>
        </label>
    );
}

function PreviewDetails({
    definition,
    probes,
    nested,
    search,
    onNested,
}: {
    definition: ViewDefinition;
    probes: readonly ViewProbe[];
    nested: NestedFixtures;
    search: WorkbenchSearch;
    onNested: (name: string, fixture: string) => void;
}) {
    const root = probes.find((probe) => probe.current().definition.name === definition.name);
    const slots = definition.slots ?? [];

    return (
        <aside
            aria-label='View details'
            className='w-full shrink-0 border-t border-border bg-card xl:w-96 xl:border-t-0 xl:border-l'
        >
            <Section title='Override'>
                <OverrideHelp name={definition.name} />
            </Section>
            {root ? (
                <ProbeDetails probe={root} />
            ) : (
                <Section title='Layers' count={getViewChain(definition.name).length}>
                    <LayerStack view={{ layers: getViewChain(definition.name), failed: [] }} />
                </Section>
            )}
            <Section title='Slots' count={slots.length}>
                <SlotList slots={slots} />
            </Section>
            <NestedViews probes={probes} root={definition.name} nested={nested} search={search} onNested={onNested} />
        </aside>
    );
}

/** Which fixtures show: the chosen one, or all of them side by side. */
function fixtureSelection(definition: ViewDefinition, search: WorkbenchSearch) {
    const fixtures = Object.keys(definition.fixtures ?? {});
    const all = search.fixture === ALL_FIXTURES && fixtures.length > 1;
    const current = fixtures.find((fixture) => fixture === search.fixture) ?? fixtures[0];

    return { fixtures, all, current, shown: all ? fixtures : fixtures.filter((fixture) => fixture === current) };
}

export interface ViewPreviewProps {
    definition: ViewDefinition;
    search: WorkbenchSearch;
    scheme: ThemeScheme;
    width: WorkbenchWidth;
    onFixture: (fixture: string | undefined) => void;
}

/** A view rendered from its fixtures through the full layer chain, with its layers, model and slots. */
export default function ViewPreview({ definition, search, scheme, width, onFixture }: ViewPreviewProps) {
    const { fixtures, all, current, shown } = fixtureSelection(definition, search);
    const [focus, setFocus] = useState<string | undefined>(undefined);
    const focused = shown.find((fixture) => fixture === focus) ?? shown[0];
    const [nested, setNested] = useState<NestedFixtures>({});
    const [content, setContent] = useState<string | null>(null);
    const [probes, setProbes] = useState<ReadonlyMap<string, readonly ViewProbe[]>>(new Map());
    const [stage, setStage] = useState<HTMLElement | null>(null);
    const available = useWidth(stage);
    const collect = useCallback((fixture: string, probe: ViewProbe) => {
        setProbes((previous) => new Map(previous).set(fixture, [...(previous.get(fixture) ?? []), probe]));

        return () =>
            setProbes((previous) =>
                new Map(previous).set(
                    fixture,
                    (previous.get(fixture) ?? []).filter((existing) => existing !== probe)
                )
            );
    }, []);
    const focusedProbes = probes.get(focused ?? '') ?? [];
    const root = focusedProbes.find((probe) => probe.current().definition.name === definition.name);
    const rendered = root ? renderingLayer(root.current()) : getViewChain(definition.name).at(-1);
    const options: FrameOptions = { definition, nested, content, scheme, width, available };

    return (
        <div className='flex min-w-0 flex-1 flex-col xl:flex-row'>
            <section className='min-w-0 flex-1 p-4 lg:p-6'>
                <header className='mb-4 space-y-3'>
                    <div className='flex flex-wrap items-baseline gap-x-3 gap-y-1'>
                        <h2 className='font-mono text-lg font-semibold text-foreground'>{definition.name}</h2>
                        <span className='text-xs text-muted-foreground'>
                            {definition.kind} · {definition.context} context
                            {rendered && ` · rendered by ${rendered.id}`}
                        </span>
                    </div>
                    {fixtures.length > 0 && (
                        <div className='flex flex-wrap items-center gap-3'>
                            <FixtureTabs
                                fixtures={fixtures}
                                current={all ? ALL_FIXTURES : current}
                                onSelect={(fixture) => onFixture(fixture === fixtures[0] ? undefined : fixture)}
                            />
                            {definition.kind === 'layout' && <PageInside value={content} onChange={setContent} />}
                        </div>
                    )}
                </header>
                <div ref={setStage} className='flex flex-wrap items-start gap-6'>
                    {shown.length === 0 && <NoFixtures name={definition.name} />}
                    {available > 0 &&
                        shown.map((fixture) => (
                            <FixtureFrame
                                key={fixture}
                                fixture={fixture}
                                options={options}
                                focused={fixture === focused}
                                onFocus={all ? () => setFocus(fixture) : null}
                                onProbe={collect}
                            />
                        ))}
                </div>
            </section>
            <PreviewDetails
                definition={definition}
                probes={focusedProbes}
                nested={nested}
                search={search}
                onNested={(name, fixture) => setNested((previous) => ({ ...previous, [name]: fixture }))}
            />
        </div>
    );
}
