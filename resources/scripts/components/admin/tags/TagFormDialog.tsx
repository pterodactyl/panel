import { useStore } from '@tanstack/react-form';
import {
    createAdminTagInput,
    type AdminTag,
    type AdminTagValues,
    updateAdminTagInput,
    useCreateAdminTag,
    useUpdateAdminTag,
} from '@/api/admin/tags/queries';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, useAppForm } from '@/components/form';

interface Props {
    open: boolean;
    onClose: () => void;
    tag?: AdminTag;
}

interface TagFormValues {
    name: string;
    slug: string;
    color: string;
}

const tagValues = (tag?: AdminTag): TagFormValues => ({
    name: tag?.attributes.name ?? '',
    slug: tag?.attributes.slug ?? '',
    color: tag?.attributes.color ?? '',
});

const toRequestValues = (values: TagFormValues): AdminTagValues => ({
    name: values.name.trim(),
    slug: values.slug.trim(),
    color: values.color.trim() || null,
});

const validateRequired = (label: string, value: string): string | undefined => {
    const length = value.trim().length;

    if (length === 0) return `${label} is required.`;
    if (length > 191) return `${label} must not exceed 191 characters.`;

    return undefined;
};

const validateSlug = (value: string): string | undefined => {
    const requiredError = validateRequired('Slug', value);
    if (requiredError) return requiredError;
    if (/^\d+$/.test(value.trim())) return 'A slug cannot contain only numbers.';

    return undefined;
};

const validateColor = (value: string): string | undefined => {
    const color = value.trim();
    if (!color) return undefined;
    const digits = color.slice(1);
    const hasValidLength = [3, 4, 6, 8].includes(digits.length);
    const isHexadecimal = [...digits].every((character) => Number.isFinite(Number.parseInt(character, 16)));

    return color.startsWith('#') && hasValidLength && isHexadecimal
        ? undefined
        : 'Use a hexadecimal color with 3, 4, 6, or 8 digits.';
};

export default function TagFormDialog({ open, onClose, tag }: Props) {
    const createTag = useCreateAdminTag();
    const updateTag = useUpdateAdminTag();
    const form = useAppForm({
        defaultValues: tagValues(tag),
        onSubmit: async ({ value }) => {
            try {
                if (tag) {
                    await updateTag.mutateAsync(updateAdminTagInput(tag.attributes.id, toRequestValues(value)));
                } else {
                    await createTag.mutateAsync(createAdminTagInput(toRequestValues(value)));
                }
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open={open}
            title={tag ? 'Update tag' : 'Create tag'}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <Form form={form} className={'m-0'}>
                <div className={'grid gap-5'}>
                    <form.AppField
                        name={'name'}
                        validators={{ onChange: ({ value }) => validateRequired('Name', value) }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                label={'Name'}
                                description={'The readable label shown throughout the panel.'}
                                autoComplete={'off'}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name={'slug'} validators={{ onChange: ({ value }) => validateSlug(value) }}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                label={'Slug'}
                                description={'A stable identifier used by deployments and API integrations.'}
                                autoComplete={'off'}
                                spellCheck={false}
                            />
                        )}
                    </form.AppField>
                    <form.AppField name={'color'} validators={{ onChange: ({ value }) => validateColor(value) }}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                label={'Color'}
                                description={'Optional hexadecimal color used for the tag indicator.'}
                                placeholder={'Hex color'}
                                autoComplete={'off'}
                                spellCheck={false}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end'}>
                    <Button.Text type={'button'} isSecondary onClick={onClose}>
                        Cancel
                    </Button.Text>
                    <form.AppForm>
                        <form.SubmitButton>{tag ? 'Save changes' : 'Create tag'}</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}
