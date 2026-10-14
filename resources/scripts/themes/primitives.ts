import type { AlertProps, AlertType } from '@/components/elements/alert/Alert';
import type { AvatarProps, AvatarVariant } from '@/components/Avatar';
import type { BadgeProps, BadgeSize, BadgeVariant } from '@/components/elements/Badge';
import type { ButtonColor, ButtonProps, ButtonSize } from '@/components/elements/Button';
import type { CardProps } from '@/components/elements/Card';
import type { ChartCardProps } from '@/components/elements/ChartCard';
import type { DialogIconProps } from '@/components/elements/dialog/types';
import type { EmptyMediaVariant, EmptyProps, EmptySize } from '@/components/ui/empty';
import type { ListRowProps } from '@/components/elements/ListRow';
import type { PageFooterProps } from '@/components/elements/PageFooter';
import type { PageHeadingProps } from '@/components/elements/PageHeading';
import type { ProgressBarProps, ProgressBarVariant } from '@/components/elements/ProgressBar';
import type { SpinnerProps, SpinnerSize } from '@/components/elements/Spinner';
import type { StatProps, StatVariant } from '@/components/elements/Stat';

/** The colour roles shared by tone variants (Badge, ProgressBar, Stat, ListRow's status). */
export type UiTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';
type ControlState = { light: boolean; error: boolean };
/** A primitive whose recipes read no state. */
export type NoState = { readonly [key: string]: never };

/**
 * Every UI primitive: the state its recipes read (rendered as `data-*` on the root, or on a part
 * given its own state), and for presentational primitives the props a replacement component receives.
 */
export interface UiPrimitives {
    Alert: {
        state: { type: AlertType };
        props: AlertProps;
    };
    Avatar: { state: { variant: AvatarVariant }; props: AvatarProps };
    Badge: {
        state: { tone: UiTone; size: BadgeSize; variant: BadgeVariant };
        props: BadgeProps;
    };
    /**
     * `label` wraps the children in a row with a gap, so an icon beside the text needs no margin.
     * `Button.Text` is `color: grey` and `Button.Danger` is `color: red`.
     */
    Button: {
        state: { color: ButtonColor; size: ButtonSize; secondary: boolean; loading: boolean };
        props: ButtonProps;
    };
    Card: {
        state: { titled: boolean; actions: boolean; loading: boolean };
        props: CardProps;
    };
    /** The frame of a chart: `legend` sits at the end of the header, `body` holds the chart. */
    ChartCard: { state: NoState; props: ChartCardProps };
    Checkbox: { state: NoState };
    Code: { state: { dark: boolean } };
    /** `target` is added to the clicked child; the other parts are the status toast. */
    CopyOnClick: { state: { status?: 'copied' | 'failed' } };
    Dialog: {
        /** `type` is the state of the `icon` part. */
        state: { described: boolean; type?: DialogIconProps['type'] };
    };
    DropdownMenu: { state: { context: boolean } };
    /** `checked` is set on an item given `checked`, such as the scheme in use. */
    DropdownMenuItem: {
        state: { danger: boolean; described: boolean; checked: boolean };
    };
    Empty: {
        /** `variant` is the state of the `media` part. */
        state: { size: EmptySize; variant?: EmptyMediaVariant };
        props: EmptyProps;
    };
    FileInput: { state: NoState };
    Input: { state: ControlState };
    Label: { state: { light: boolean } };
    /**
     * `icon`, `title` and `description` are `ListRow.Icon`, `ListRow.Title` and `ListRow.Description`,
     * placed by the row's content; `status` is drawn when the row has a `status`.
     */
    ListRow: {
        state: { interactive: boolean; status?: UiTone };
        props: ListRowProps;
    };
    /** `root` is added to the Button it renders, `content` wraps the icon and label. */
    NewButton: { state: { link: boolean } };
    NumberInput: { state: ControlState };
    /** The copyright line below each page; a replacement that renders nothing drops it. */
    PageFooter: { state: NoState; props: PageFooterProps };
    PageHeading: {
        state: NoState;
        props: PageHeadingProps;
    };
    /** `page` is added to each page Button. */
    Pagination: { state: { current?: boolean } };
    ProgressBar: {
        state: { variant: ProgressBarVariant; tone: UiTone };
        props: ProgressBarProps;
    };
    Select: {
        state: { error: boolean; multiple: boolean; selected: boolean; richLabel: boolean; searching: boolean };
    };
    /**
     * `root` is the spinning element, an empty `span` with no text or role: the region that loads
     * announces it. `container` centres it when `centered`. A skin can animate anything, such as the
     * `content` of `::before`.
     */
    Spinner: {
        state: { size: SpinnerSize; blue: boolean; centered: boolean };
        props: SpinnerProps;
    };
    SpinnerOverlay: { state: { fixed: boolean } };
    /**
     * `glyph` is the icon inside `icon`; `indicator` is a bar for the tone, and `limit` follows `value`.
     * `unavailable` means the value says why there is no reading, such as "Offline".
     */
    Stat: {
        state: { variant: StatVariant; tone: UiTone; unavailable: boolean };
        props: StatProps;
    };
    /** `label` is added to the Label it renders. */
    Switch: {
        state: { described: boolean; position: 'start' | 'end' };
    };
    Table: {
        state: { fetching: boolean };
    };
    TextArea: { state: ControlState };
    Tooltip: { state: { side: 'top' | 'right' | 'bottom' | 'left'; arrow: boolean } };
}

/** The parts each UI primitive's skin styles, in the order they render. */
export const UI_PRIMITIVE_PARTS = {
    Alert: ['root', 'icon', 'body', 'title', 'content'],
    Avatar: ['root'],
    Badge: ['root'],
    Button: ['root', 'spinner', 'label'],
    Card: ['root', 'header', 'title', 'icon', 'actions', 'body'],
    ChartCard: ['root', 'header', 'title', 'legend', 'body'],
    Checkbox: ['root', 'indicator'],
    Code: ['root'],
    CopyOnClick: ['root', 'status', 'message', 'target'],
    Dialog: [
        'backdrop',
        'viewport',
        'container',
        'root',
        'body',
        'content',
        'header',
        'heading',
        'title',
        'description',
        'spacer',
        'footer',
        'close',
        'closeButton',
        'closeIcon',
        'icon',
        'iconGlyph',
    ],
    DropdownMenu: ['trigger', 'positioner', 'root'],
    DropdownMenuItem: ['root', 'icon', 'glyph', 'text', 'label', 'description'],
    Empty: ['root', 'header', 'media', 'title', 'description', 'content'],
    FileInput: ['root'],
    Input: ['root'],
    Label: ['root'],
    ListRow: ['root', 'icon', 'title', 'description', 'status'],
    NewButton: ['root', 'content', 'icon'],
    NumberInput: ['root', 'input'],
    PageFooter: ['root', 'text', 'link'],
    PageHeading: ['root', 'text', 'title', 'description', 'actions'],
    Pagination: ['root', 'page'],
    ProgressBar: ['root', 'indicator'],
    Select: [
        'root',
        'input',
        'trigger',
        'positioner',
        'popup',
        'list',
        'item',
        'itemLabel',
        'indicator',
        'empty',
        'group',
        'groupLabel',
        'summary',
        'preview',
    ],
    Spinner: ['root', 'container'],
    SpinnerOverlay: ['root', 'text'],
    Stat: ['root', 'indicator', 'icon', 'glyph', 'body', 'label', 'value', 'limit'],
    Switch: ['root', 'control', 'thumb', 'text', 'label', 'description'],
    Table: ['root', 'empty', 'scroller', 'table', 'head', 'headerRow', 'headerCell', 'body', 'row', 'cell'],
    TextArea: ['root'],
    Tooltip: ['root', 'arrow'],
} as const satisfies { readonly [N in keyof UiPrimitives]: readonly string[] };

export type PrimitiveName = keyof UiPrimitives;
export type SkinPart<N extends PrimitiveName> = (typeof UI_PRIMITIVE_PARTS)[N][number];
export type PrimitiveState<N extends PrimitiveName> = UiPrimitives[N]['state'];
/** Presentational primitives, which a theme can also replace with a component. */
export type ReplaceableName = {
    [N in PrimitiveName]: 'props' extends keyof UiPrimitives[N] ? N : never;
}[PrimitiveName];
export type PrimitiveProps<N extends ReplaceableName> = UiPrimitives[N] extends { props: infer P extends object }
    ? P
    : never;

/** `data-ui` of a primitive: `DropdownMenuItem` → `dropdown-menu-item`. */
export function primitiveAttribute(name: PrimitiveName): string {
    return name.replaceAll(/[A-Z]/g, (letter, index: number) => `${index ? '-' : ''}${letter.toLowerCase()}`);
}
