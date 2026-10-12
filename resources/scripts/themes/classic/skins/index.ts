import type { CompleteSkins } from '@/sdk/theme';
import { button, newButton } from './buttons';
import { avatar, code, copyOnClick, pagination, table } from './data';
import { alert, badge, empty, progressBar, spinner, spinnerOverlay } from './feedback';
import { checkbox, fileInput, input, label, numberInput, select, switchSkin, textArea } from './forms';
import { dialog, dropdownMenu, dropdownMenuItem, tooltip } from './overlays';
import { card, listRow, pageHeading } from './surfaces';

/** The panel's own look: a recipe for every part of every primitive. */
export const classicSkins = {
    Alert: alert,
    Avatar: avatar,
    Badge: badge,
    Button: button,
    Card: card,
    Checkbox: checkbox,
    Code: code,
    CopyOnClick: copyOnClick,
    Dialog: dialog,
    DropdownMenu: dropdownMenu,
    DropdownMenuItem: dropdownMenuItem,
    Empty: empty,
    FileInput: fileInput,
    Input: input,
    Label: label,
    ListRow: listRow,
    NewButton: newButton,
    NumberInput: numberInput,
    PageHeading: pageHeading,
    Pagination: pagination,
    ProgressBar: progressBar,
    Select: select,
    Spinner: spinner,
    SpinnerOverlay: spinnerOverlay,
    Switch: switchSkin,
    Table: table,
    TextArea: textArea,
    Tooltip: tooltip,
} satisfies CompleteSkins;
