import { useState } from 'react';
import { Copy, Eye, EyeOff } from 'lucide-react';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import Icon from '@/components/elements/Icon';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { TextInput } from '@/components/form/controls';

interface SecretInputProps {
    id?: string;
    value: string | null | undefined;
    label: string;
}

const SecretInput = ({ id, value, label }: SecretInputProps) => {
    const [visible, setVisible] = useState(false);
    const toggleLabel = `${visible ? 'Hide' : 'Show'} ${label.toLowerCase()}`;
    const copyLabel = `Copy ${label.toLowerCase()}`;

    return (
        <div className={'relative'}>
            <TextInput
                id={id}
                type={visible ? 'text' : 'password'}
                readOnly
                autoComplete={'off'}
                spellCheck={false}
                value={value ?? ''}
                className={'pr-20 font-mono'}
            />
            <div className={'absolute inset-y-0 right-2 flex items-center gap-1'}>
                <Tooltip content={toggleLabel}>
                    <span className={'inline-flex'}>
                        <Button.Text
                            type={'button'}
                            size={'xsmall'}
                            isSecondary
                            aria-label={toggleLabel}
                            aria-pressed={visible}
                            disabled={!value}
                            onClick={() => setVisible((current) => !current)}
                        >
                            <Icon icon={visible ? EyeOff : Eye} className={'h-3.5 w-3.5'} />
                        </Button.Text>
                    </span>
                </Tooltip>
                <Tooltip content={copyLabel}>
                    <span className={'inline-flex'}>
                        <CopyOnClick text={value} showInNotification={false}>
                            <Button.Text
                                type={'button'}
                                size={'xsmall'}
                                isSecondary
                                aria-label={copyLabel}
                                disabled={!value}
                            >
                                <Icon icon={Copy} className={'h-3.5 w-3.5'} />
                            </Button.Text>
                        </CopyOnClick>
                    </span>
                </Tooltip>
            </div>
        </div>
    );
};

export default SecretInput;
