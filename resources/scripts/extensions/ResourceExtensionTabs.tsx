import NavLink from '@/router/NavLink';
import { getExtensionScreens, resolveScreenPath, type ScreenParent } from './registry';
import NavigationLabel from './NavigationLabel';
import { ScreenGate } from './screenNavigation';

export default function ResourceExtensionTabs({ parent, basePath }: { parent: ScreenParent; basePath: string }) {
    return getExtensionScreens('admin', parent).map((screen) =>
        screen.nav ? (
            <ScreenGate key={`${screen.extensionId}:${screen.id}`} screen={screen}>
                <NavLink
                    to={`${basePath}/${resolveScreenPath(screen.path, screen.nav.params)}`}
                    exact={screen.nav.exact}
                >
                    <NavigationLabel {...screen.nav} screen={screen} />
                </NavLink>
            </ScreenGate>
        ) : null
    );
}
