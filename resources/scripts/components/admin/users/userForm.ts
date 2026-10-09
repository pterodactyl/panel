import type { AdminUser, UserValues } from '@/api/admin/users/queries';
import { requiredWithMaxLength } from '@/components/form/validators';
import { initialExtensionValues } from '@/extensions/forms';

export const userFormValues = (user: AdminUser): UserValues => ({
    email: user.attributes.email,
    username: user.attributes.username,
    nameFirst: user.attributes.first_name ?? '',
    nameLast: user.attributes.last_name ?? '',
    password: '',
    rootAdmin: user.attributes.root_admin,
    language: user.attributes.language,
    extensions: initialExtensionValues(),
});

export const validateUserPassword = ({ value }: { value: string }): string | undefined => {
    if (value.length === 0) {
        return undefined;
    }

    if (value.length < 8) {
        return 'Password must be at least 8 characters.';
    }

    if (value.length > 191) {
        return 'Password must not exceed 191 characters.';
    }

    return undefined;
};

export const validateUserEmail = ({ value }: { value: string }): string | undefined => {
    if (value.length < 1) {
        return 'An email address must be provided.';
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        return 'A valid email address must be provided.';
    }

    return undefined;
};

export const validateUsername = requiredWithMaxLength(
    191,
    'A username must be provided.',
    'A username must not exceed 191 characters.'
);

export const validateFirstName = requiredWithMaxLength(
    191,
    'A first name must be provided.',
    'First name must not exceed 191 characters.'
);

export const validateLastName = requiredWithMaxLength(
    191,
    'A last name must be provided.',
    'Last name must not exceed 191 characters.'
);
