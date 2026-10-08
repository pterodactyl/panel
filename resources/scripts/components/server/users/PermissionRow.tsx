import { useStore } from '@tanstack/react-form';
import type { AnyFormApi } from '@tanstack/react-form';
import Label from '@/components/elements/Label';
import Checkbox from '@/components/ui/Checkbox';
import PermissionRowContainer from '@/components/server/users/PermissionRowContainer';

interface Props {
    form: AnyFormApi;
    permission: string;
    label: string;
    description: string;
    disabled: boolean;
}

const PermissionRow = ({ form, permission, label, description, disabled }: Props) => {
    const selected = useStore(form.store, (state) => (state.values.permissions ?? []) as string[]);

    return (
        <PermissionRowContainer htmlFor={`permission_${permission}`} className={disabled ? 'disabled' : undefined}>
            <div className='p-2'>
                <Checkbox
                    id={`permission_${permission}`}
                    checked={selected.includes(permission)}
                    disabled={disabled}
                    onChange={(checked) => {
                        const set = new Set(selected);

                        if (checked) {
                            set.add(permission);
                        } else {
                            set.delete(permission);
                        }

                        form.setFieldValue('permissions', [...set]);
                    }}
                    className='w-5 h-5 mr-2'
                />
            </div>
            <div className='flex-1'>
                <Label as='p' className='font-medium'>
                    {label}
                </Label>
                {description.length > 0 && <p className='text-xs text-muted-foreground mt-1'>{description}</p>}
            </div>
        </PermissionRowContainer>
    );
};

export default PermissionRow;
