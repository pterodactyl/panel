import { cn } from '@/lib/cn';

interface Props {
    active: boolean;
    onClick: () => void;
    children: string;
}

export default function SettingsTabButton({ active, onClick, children }: Props) {
    return (
        <button
            type='button'
            onClick={onClick}
            className={cn(
                'px-4 py-2 text-sm uppercase tracking-wide border-b-2 transition-colors duration-150',
                active ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'
            )}
        >
            {children}
        </button>
    );
}
