import { useState } from 'react';
import { Eye, EyeOff, LayoutTemplate, LifeBuoy, Palette, RotateCcw, Settings2, Sparkles } from 'lucide-react';
import {
    activateAdminThemeInput,
    previewAdminThemeInput,
    type AdminTheme,
    type AdminThemesMeta,
    useActivateAdminTheme,
    useAdminThemes,
    usePreviewAdminTheme,
    useResetAdminTheme,
    useStopAdminThemePreview,
} from '@/api/admin/themes/queries';
import { httpErrorToHuman } from '@/api/http';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { LinkButton } from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import ListToolbar from '@/components/elements/ListToolbar';
import NamedIcon from '@/components/elements/NamedIcon';
import { NewButton } from '@/components/elements/NewButton';
import Spinner from '@/components/elements/Spinner';
import { Alert } from '@/components/elements/alert';
import { Dialog } from '@/components/elements/dialog';
import { ServerError } from '@/components/elements/ScreenBlock';
import { cn } from '@/lib/cn';
import ThemeSettingsDialog from './ThemeSettingsDialog';

const actionClass = 'h-8 px-2.5 text-sm';

function ThemeMarkContent({ theme }: { theme: AdminTheme }) {
    const [failed, setFailed] = useState(false);

    if (theme.icon_url && !failed) {
        return (
            <img src={theme.icon_url} alt='' className='h-full w-full object-contain' onError={() => setFailed(true)} />
        );
    }

    return theme.icon ? <NamedIcon name={theme.icon} size={20} /> : <Icon icon={Palette} />;
}

function ThemeMark({ theme }: { theme: AdminTheme }) {
    return (
        <div
            className='flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-secondary text-secondary-foreground'
            aria-hidden='true'
        >
            <ThemeMarkContent theme={theme} />
        </div>
    );
}

const badges = (theme: AdminTheme): { label: string; className: string }[] =>
    [
        theme.active && { label: 'Active', className: 'bg-success/15 text-success' },
        theme.previewing && { label: 'Previewing', className: 'bg-accent/15 text-accent' },
        theme.builtin && { label: 'Built in', className: 'bg-secondary text-secondary-foreground' },
        !theme.enabled && { label: 'Disabled', className: 'bg-warning/15 text-warning' },
    ].filter((badge) => badge !== false);

function ThemeCard({ theme }: { theme: AdminTheme }) {
    const activate = useActivateAdminTheme();
    const preview = usePreviewAdminTheme();
    const usable = theme.problem === null;

    return (
        <article className='flex h-full flex-col rounded-sm border border-border bg-card p-4'>
            <div className='flex min-w-0 items-start gap-3'>
                <ThemeMark theme={theme} />
                <div className='min-w-0 flex-1'>
                    <div className='flex flex-wrap items-center gap-2'>
                        <h2 className='truncate text-base font-semibold text-foreground'>{theme.name}</h2>
                        {badges(theme).map((badge) => (
                            <span
                                key={badge.label}
                                className={cn('rounded-sm px-1.5 py-0.5 text-xs font-medium', badge.className)}
                            >
                                {badge.label}
                            </span>
                        ))}
                    </div>
                    <p className='mt-0.5 truncate text-xs text-muted-foreground'>
                        {[theme.version && `v${theme.version}`, theme.author && `by ${theme.author}`, theme.id]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>
            </div>
            <p className='mt-3 line-clamp-3 text-sm leading-relaxed text-card-foreground/80'>
                {theme.description || 'No description provided.'}
            </p>
            {theme.extends && (
                <p className='mt-2 text-xs text-muted-foreground'>
                    Built on <span className='font-mono'>{theme.extends}</span>
                </p>
            )}
            {theme.problem && (
                <div className='mt-3 rounded-sm border border-destructive/30 bg-destructive/10 p-3'>
                    <p className='break-words text-sm text-destructive'>{theme.problem}</p>
                </div>
            )}
            <div className='mt-auto pt-4'>
                <div className='flex flex-wrap items-center gap-2 border-t border-border pt-4'>
                    <NewButton
                        icon={Sparkles}
                        className={actionClass}
                        disabled={theme.active || !usable}
                        isLoading={activate.isPending}
                        onClick={() => activate.mutate(activateAdminThemeInput(theme.id, theme.name))}
                    >
                        {theme.active ? 'Active' : 'Activate'}
                    </NewButton>
                    <NewButton
                        icon={Eye}
                        color='grey'
                        isSecondary
                        className={actionClass}
                        disabled={theme.previewing || !usable}
                        isLoading={preview.isPending}
                        onClick={() => preview.mutate(previewAdminThemeInput(theme.id, theme.name))}
                    >
                        Preview for me
                    </NewButton>
                    {theme.settings_count > 0 && (
                        <Dialog.Trigger
                            trigger={({ onClick }) => (
                                <NewButton
                                    icon={Settings2}
                                    color='grey'
                                    isSecondary
                                    className={actionClass}
                                    onClick={onClick}
                                >
                                    Settings
                                </NewButton>
                            )}
                        >
                            {({ open, onClose }) => <ThemeSettingsDialog theme={theme} open={open} onClose={onClose} />}
                        </Dialog.Trigger>
                    )}
                </div>
            </div>
        </article>
    );
}

function ThemeToolbar({ meta, themes }: { meta?: AdminThemesMeta; themes: AdminTheme[] }) {
    const reset = useResetAdminTheme();
    const stopPreview = useStopAdminThemePreview();
    const classicActive = themes.some((theme) => theme.builtin && theme.active);

    return (
        <ListToolbar>
            <div className='flex flex-wrap items-center gap-2'>
                {meta?.preview && (
                    <NewButton
                        icon={EyeOff}
                        color='grey'
                        isSecondary
                        isLoading={stopPreview.isPending}
                        onClick={() => stopPreview.mutate({})}
                    >
                        Stop preview
                    </NewButton>
                )}
                <NewButton
                    icon={RotateCcw}
                    color='grey'
                    isSecondary
                    disabled={classicActive}
                    isLoading={reset.isPending}
                    onClick={() => reset.mutate({})}
                >
                    Reset to classic
                </NewButton>
                <LinkButton href='/panel/theme-workbench' isSecondary className='inline-flex h-9 items-center gap-2'>
                    <Icon icon={LayoutTemplate} className='h-4 w-4' />
                    Workbench
                </LinkButton>
                <LinkButton
                    href={meta?.safe_mode ? '/panel/themes?safe_mode=0' : '/?safe_mode=1'}
                    isSecondary
                    className='inline-flex h-9 items-center gap-2'
                >
                    <Icon icon={LifeBuoy} className='h-4 w-4' />
                    {meta?.safe_mode ? 'Leave safe mode' : 'Safe mode'}
                </LinkButton>
            </div>
        </ListToolbar>
    );
}

export default function ThemesContainer() {
    const { data, error, refetch } = useAdminThemes();
    const themes = (data?.data ?? []).map((item) => item.attributes);

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title='Admin · Themes'
            heading='Themes'
            description='Choose the theme every visitor sees, try one on yourself first, and adjust its settings.'
        >
            {data?.meta?.safe_mode && (
                <Alert type='warning' className='mb-4'>
                    Safe mode is on for your session: you see the classic theme without any extension bundles.
                </Alert>
            )}
            {data ? (
                <>
                    <ThemeToolbar meta={data.meta} themes={themes} />
                    <div className='grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3'>
                        {themes.map((theme) => (
                            <ThemeCard key={theme.id} theme={theme} />
                        ))}
                    </div>
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
