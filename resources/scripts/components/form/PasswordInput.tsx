import { useState } from 'react';
import { Clipboard, Eye, EyeOff, RotateCcwKey } from 'lucide-react';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import Icon from '@/components/elements/Icon';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { TextInput, type TextInputProps } from '@/components/form/controls';
import { cn } from '@/lib/cn';
import { generateSecurePassword } from '@/lib/passwords';

export type PasswordInputProps = Omit<TextInputProps, 'type' | 'value' | 'onValueChange' | 'onChange'> & {
    value: string;
    onValueChange: (value: string) => void;
    /** Called with a generated password after it is set, e.g. to fill a confirmation field. */
    onGenerate?: (value: string) => void;
    /** What the show and hide button names, e.g. "new password". */
    noun?: string;
};

/** A password input with buttons to generate a password, show or hide it, and copy one it generated. */
export function PasswordInput({
    value,
    onValueChange,
    onGenerate,
    noun = 'password',
    className,
    ...props
}: PasswordInputProps) {
    const [visible, setVisible] = useState(false);
    const [generated, setGenerated] = useState(false);
    const toggleLabel = `${visible ? 'Hide' : 'Show'} ${noun}`;

    const generate = () => {
        const password = generateSecurePassword();

        onValueChange(password);
        onGenerate?.(password);
        setGenerated(true);
        setVisible(true);
    };

    return (
        <div className='relative'>
            <TextInput
                {...props}
                type={visible ? 'text' : 'password'}
                value={value}
                onValueChange={(next: string) => {
                    onValueChange(next);
                    setGenerated(false);
                }}
                className={cn('pr-24', className)}
            />
            <div className='absolute inset-y-0 right-2 flex items-center gap-1'>
                <Tooltip content='Generate secure password'>
                    <span className='inline-flex'>
                        <Button.Text
                            type='button'
                            size='xsmall'
                            isSecondary
                            aria-label='Generate secure password'
                            onClick={generate}
                        >
                            <Icon icon={RotateCcwKey} className='h-3.5 w-3.5' />
                        </Button.Text>
                    </span>
                </Tooltip>
                <Tooltip content={toggleLabel}>
                    <span className='inline-flex'>
                        <Button.Text
                            type='button'
                            size='xsmall'
                            isSecondary
                            aria-label={toggleLabel}
                            aria-pressed={visible}
                            disabled={!value}
                            onClick={() => setVisible((current) => !current)}
                        >
                            <Icon icon={visible ? EyeOff : Eye} className='h-3.5 w-3.5' />
                        </Button.Text>
                    </span>
                </Tooltip>
                {generated && (
                    <Tooltip content='Copy password'>
                        <span className='inline-flex'>
                            <CopyOnClick text={value} showInNotification={false}>
                                <Button.Text type='button' size='xsmall' isSecondary aria-label='Copy password'>
                                    <Icon icon={Clipboard} className='h-3.5 w-3.5' />
                                </Button.Text>
                            </CopyOnClick>
                        </span>
                    </Tooltip>
                )}
            </div>
        </div>
    );
}
