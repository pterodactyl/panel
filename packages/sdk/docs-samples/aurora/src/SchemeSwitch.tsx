import { Monitor, Moon, Sun } from 'lucide-react';
import { useTheme, type ThemeSchemePreference } from '@pterodactyl/sdk/theme';

const options = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] satisfies { value: ThemeSchemePreference; label: string; icon: typeof Sun }[];

/** Light, dark or the system's: stored in a cookie and applied without a reload. */
export default function SchemeSwitch() {
    const { preference, schemes, setScheme } = useTheme();

    if (schemes.length < 2) {
        return null;
    }

    return (
        <div role='group' aria-label='Colour scheme' className='glass inline-flex rounded-full p-0.5'>
            {options.map(({ value, label, icon: Glyph }) => (
                <button
                    key={value}
                    type='button'
                    aria-label={label}
                    aria-pressed={value === preference}
                    onClick={() => setScheme(value)}
                    className='rounded-full p-1.5 text-muted-foreground aria-pressed:bg-primary aria-pressed:text-primary-foreground'
                >
                    <Glyph className='size-4' aria-hidden />
                </button>
            ))}
        </div>
    );
}
