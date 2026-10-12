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
 * Every UI primitive: the parts a skin styles, the state its recipes read (rendered as `data-*` on
 * the root, or on a part given its own state), and for presentational primitives the props a
 * replacement component receives.
 */
export interface UiPrimitives {
    Alert: {
        parts: 'root' | 'icon' | 'body' | 'title' | 'content';
        state: { type: AlertType };
        props: AlertProps;
    };
    Avatar: { parts: 'root'; state: { variant: AvatarVariant }; props: AvatarProps };
    Badge: {
        parts: 'root';
        state: { tone: UiTone; size: BadgeSize; variant: BadgeVariant };
        props: BadgeProps;
    };
    /**
     * `label` wraps the children in a row with a gap, so an icon beside the text needs no margin.
     * `Button.Text` is `color: grey` and `Button.Danger` is `color: red`.
     */
    Button: {
        parts: 'root' | 'spinner' | 'label';
        state: { color: ButtonColor; size: ButtonSize; secondary: boolean; loading: boolean };
        props: ButtonProps;
    };
    Card: {
        parts: 'root' | 'header' | 'title' | 'icon' | 'actions' | 'body';
        state: { titled: boolean; actions: boolean; loading: boolean };
        props: CardProps;
    };
    /** The frame of a chart: `legend` sits at the end of the header, `body` holds the chart. */
    ChartCard: { parts: 'root' | 'header' | 'title' | 'legend' | 'body'; state: NoState; props: ChartCardProps };
    Checkbox: { parts: 'root' | 'indicator'; state: NoState };
    Code: { parts: 'root'; state: { dark: boolean } };
    /** `target` is added to the clicked child; the other parts are the status toast. */
    CopyOnClick: { parts: 'root' | 'status' | 'message' | 'target'; state: { status?: 'copied' | 'failed' } };
    Dialog: {
        parts:
            | 'backdrop'
            | 'viewport'
            | 'container'
            | 'root'
            | 'body'
            | 'content'
            | 'header'
            | 'heading'
            | 'title'
            | 'description'
            | 'spacer'
            | 'footer'
            | 'close'
            | 'closeButton'
            | 'closeIcon'
            | 'icon'
            | 'iconGlyph';
        /** `type` is the state of the `icon` part. */
        state: { described: boolean; type?: DialogIconProps['type'] };
    };
    DropdownMenu: { parts: 'trigger' | 'positioner' | 'root'; state: { context: boolean } };
    DropdownMenuItem: {
        parts: 'root' | 'icon' | 'glyph' | 'text' | 'label' | 'description';
        state: { danger: boolean; described: boolean };
    };
    Empty: {
        parts: 'root' | 'header' | 'media' | 'title' | 'description' | 'content';
        /** `variant` is the state of the `media` part. */
        state: { size: EmptySize; variant?: EmptyMediaVariant };
        props: EmptyProps;
    };
    FileInput: { parts: 'root'; state: NoState };
    Input: { parts: 'root'; state: ControlState };
    Label: { parts: 'root'; state: { light: boolean } };
    /** `icon` is `ListRow.Icon`, placed by the row's content; `status` is drawn when the row has a `status`. */
    ListRow: {
        parts: 'root' | 'icon' | 'status';
        state: { interactive: boolean; status?: UiTone };
        props: ListRowProps;
    };
    /** `root` is added to the Button it renders, `content` wraps the icon and label. */
    NewButton: { parts: 'root' | 'content' | 'icon'; state: { link: boolean } };
    NumberInput: { parts: 'root' | 'input'; state: ControlState };
    /** The copyright line below each page; a replacement that renders nothing drops it. */
    PageFooter: { parts: 'root' | 'text' | 'link'; state: NoState; props: PageFooterProps };
    PageHeading: {
        parts: 'root' | 'text' | 'title' | 'description' | 'actions';
        state: NoState;
        props: PageHeadingProps;
    };
    /** `page` is added to each page Button. */
    Pagination: { parts: 'root' | 'page'; state: { current?: boolean } };
    ProgressBar: {
        parts: 'root' | 'indicator';
        state: { variant: ProgressBarVariant; tone: UiTone };
        props: ProgressBarProps;
    };
    Select: {
        parts:
            | 'root'
            | 'input'
            | 'trigger'
            | 'positioner'
            | 'popup'
            | 'list'
            | 'item'
            | 'itemLabel'
            | 'indicator'
            | 'empty'
            | 'group'
            | 'groupLabel'
            | 'summary'
            | 'preview';
        state: { error: boolean; multiple: boolean; selected: boolean; richLabel: boolean; searching: boolean };
    };
    /**
     * `root` is the spinning element, an empty `span` with no text or role: the region that loads
     * announces it. `container` centres it when `centered`. A skin can animate anything, such as the
     * `content` of `::before`.
     */
    Spinner: {
        parts: 'root' | 'container';
        state: { size: SpinnerSize; blue: boolean; centered: boolean };
        props: SpinnerProps;
    };
    SpinnerOverlay: { parts: 'root' | 'text'; state: { fixed: boolean } };
    /** `glyph` is the icon inside `icon`; `indicator` is a bar for the tone, and `limit` follows `value`. */
    Stat: {
        parts: 'root' | 'indicator' | 'icon' | 'glyph' | 'body' | 'label' | 'value' | 'limit';
        state: { variant: StatVariant; tone: UiTone };
        props: StatProps;
    };
    /** `label` is added to the Label it renders. */
    Switch: {
        parts: 'root' | 'control' | 'thumb' | 'text' | 'label' | 'description';
        state: { described: boolean; position: 'start' | 'end' };
    };
    Table: {
        parts: 'root' | 'empty' | 'scroller' | 'table' | 'head' | 'headerRow' | 'headerCell' | 'body' | 'row' | 'cell';
        state: { fetching: boolean };
    };
    TextArea: { parts: 'root'; state: ControlState };
    Tooltip: { parts: 'root' | 'arrow'; state: { side: 'top' | 'right' | 'bottom' | 'left'; arrow: boolean } };
}

export type PrimitiveName = keyof UiPrimitives;
export type SkinPart<N extends PrimitiveName> = UiPrimitives[N]['parts'];
export type PrimitiveState<N extends PrimitiveName> = UiPrimitives[N]['state'];
/** Presentational primitives, which a theme can also replace with a component. */
export type ReplaceableName = {
    [N in PrimitiveName]: 'props' extends keyof UiPrimitives[N] ? N : never;
}[PrimitiveName];
export type PrimitiveProps<N extends ReplaceableName> = UiPrimitives[N] extends { props: infer P extends object }
    ? P
    : never;

export interface PrimitiveMeta<N extends PrimitiveName> {
    readonly parts: readonly SkinPart<N>[];
    readonly replaceable: N extends ReplaceableName ? true : false;
}

/** The primitives and their parts at runtime, for the registry, the tests and the docs. */
export const UI_PRIMITIVES = {
    Alert: { parts: ['root', 'icon', 'body', 'title', 'content'], replaceable: true },
    Avatar: { parts: ['root'], replaceable: true },
    Badge: { parts: ['root'], replaceable: true },
    Button: { parts: ['root', 'spinner', 'label'], replaceable: true },
    Card: { parts: ['root', 'header', 'title', 'icon', 'actions', 'body'], replaceable: true },
    ChartCard: { parts: ['root', 'header', 'title', 'legend', 'body'], replaceable: true },
    Checkbox: { parts: ['root', 'indicator'], replaceable: false },
    Code: { parts: ['root'], replaceable: false },
    CopyOnClick: { parts: ['root', 'status', 'message', 'target'], replaceable: false },
    Dialog: {
        parts: [
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
        replaceable: false,
    },
    DropdownMenu: { parts: ['trigger', 'positioner', 'root'], replaceable: false },
    DropdownMenuItem: { parts: ['root', 'icon', 'glyph', 'text', 'label', 'description'], replaceable: false },
    Empty: { parts: ['root', 'header', 'media', 'title', 'description', 'content'], replaceable: true },
    FileInput: { parts: ['root'], replaceable: false },
    Input: { parts: ['root'], replaceable: false },
    Label: { parts: ['root'], replaceable: false },
    ListRow: { parts: ['root', 'icon', 'status'], replaceable: true },
    NewButton: { parts: ['root', 'content', 'icon'], replaceable: false },
    NumberInput: { parts: ['root', 'input'], replaceable: false },
    PageFooter: { parts: ['root', 'text', 'link'], replaceable: true },
    PageHeading: { parts: ['root', 'text', 'title', 'description', 'actions'], replaceable: true },
    Pagination: { parts: ['root', 'page'], replaceable: false },
    ProgressBar: { parts: ['root', 'indicator'], replaceable: true },
    Select: {
        parts: [
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
        replaceable: false,
    },
    Spinner: { parts: ['root', 'container'], replaceable: true },
    SpinnerOverlay: { parts: ['root', 'text'], replaceable: false },
    Stat: { parts: ['root', 'indicator', 'icon', 'glyph', 'body', 'label', 'value', 'limit'], replaceable: true },
    Switch: { parts: ['root', 'control', 'thumb', 'text', 'label', 'description'], replaceable: false },
    Table: {
        parts: ['root', 'empty', 'scroller', 'table', 'head', 'headerRow', 'headerCell', 'body', 'row', 'cell'],
        replaceable: false,
    },
    TextArea: { parts: ['root'], replaceable: false },
    Tooltip: { parts: ['root', 'arrow'], replaceable: false },
} satisfies { readonly [N in PrimitiveName]: PrimitiveMeta<N> };

export function isPrimitiveName(name: string): name is PrimitiveName {
    return Object.hasOwn(UI_PRIMITIVES, name);
}

/** `data-ui` of a primitive: `DropdownMenuItem` → `dropdown-menu-item`. */
export function primitiveAttribute(name: PrimitiveName): string {
    return name.replaceAll(/[A-Z]/g, (letter, index: number) => `${index ? '-' : ''}${letter.toLowerCase()}`);
}
