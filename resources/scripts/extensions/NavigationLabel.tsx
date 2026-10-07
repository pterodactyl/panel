import NamedIcon from '@/components/elements/NamedIcon';
import type { ExtensionScreenDefinition, ExtensionScreenRegistration } from './registry';
import { ScreenBadge } from './screenNavigation';

type Props = NonNullable<ExtensionScreenDefinition['nav']> & {
    /** Enables the screen's live badge. */
    screen?: ExtensionScreenRegistration;
};

export default function NavigationLabel({ label, group, badge, icon, screen }: Props) {
    return (
        <span className={'inline-flex items-center gap-2 align-top'} title={group}>
            {icon && <NamedIcon name={icon} size={16} className={'shrink-0'} aria-hidden />}
            <span>{label}</span>
            <ScreenBadge screen={screen} badge={badge} />
        </span>
    );
}
