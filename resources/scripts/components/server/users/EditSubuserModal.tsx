import React, { forwardRef, memo, useCallback, useEffect, useRef } from 'react';
import { Subuser } from '@/state/server/subusers';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { array, object, string } from 'yup';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Checkbox from '@/components/elements/Checkbox';
import styled from 'styled-components/macro';
import createOrUpdateSubuser from '@/api/server/users/createOrUpdateSubuser';
import { ServerContext } from '@/state/server';
import FlashMessageRender from '@/components/FlashMessageRender';
import Can from '@/components/elements/Can';
import { usePermissions } from '@/plugins/usePermissions';
import { useDeepCompareMemo } from '@/plugins/useDeepCompareMemo';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Label from '@/components/elements/Label';
import Input from '@/components/elements/Input';
import isEqual from 'react-fast-compare';

type Props = {
    subuser?: Subuser;
} & RequiredModalProps;

interface Values {
    email: string;
    permissions: string[];
}

const PermissionLabel = styled.label`
  ${tw`flex items-center border border-transparent rounded md:p-2 transition-colors duration-75`};
  text-transform: none;

  &:not(.disabled) {
      ${tw`cursor-pointer`};

      &:hover {
        ${tw`border-neutral-500 bg-neutral-800`};
      }
  }
  
  &:not(:first-of-type) {
      ${tw`mt-4 sm:mt-2`};
  }

  &.disabled {
      ${tw`opacity-50`};

      & input[type="checkbox"]:not(:checked) {
          ${tw`border-0`};
      }
  }
`;

interface TitleProps {
    isEditable: boolean;
    permission: string;
    permissions: string[];
    children: React.ReactNode;
    className?: string;
}

const PermissionTitledBox = memo(({ isEditable, permission, permissions, className, children }: TitleProps) => {
    const { values, setFieldValue } = useFormikContext<Values>();

    const onCheckboxClicked = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        console.log(e.currentTarget.checked, [
            ...values.permissions,
            ...permissions.filter(p => !values.permissions.includes(p)),
        ]);

        if (e.currentTarget.checked) {
            setFieldValue('permissions', [
                ...values.permissions,
                ...permissions.filter(p => !values.permissions.includes(p)),
            ]);
        } else {
            setFieldValue('permissions', [
                ...values.permissions.filter(p => !permissions.includes(p)),
            ]);
        }
    }, [ permissions, values.permissions ]);

    return (
        <TitledGreyBox
            title={
                <div css={tw`flex items-center`}>
                    <p css={tw`text-sm uppercase flex-1`}>{permission}</p>
                    {isEditable &&
                    <Input
                        type={'checkbox'}
                        checked={permissions.every(p => values.permissions.includes(p))}
                        onChange={onCheckboxClicked}
                    />
                    }
                </div>
            }
            className={className}
        >
            {children}
        </TitledGreyBox>
    );
}, isEqual);

const EditSubuserModal = forwardRef<HTMLHeadingElement, Props>(({ subuser, ...props }, ref) => {
    const { isSubmitting } = useFormikContext<Values>();
    const [ canEditUser ] = usePermissions(subuser ? [ 'user.update' ] : [ 'user.create' ]);
    const permissions = useStoreState(state => state.permissions.data);

    const user = useStoreState(state => state.user.data!);

    // The currently logged in user's permissions. We're going to filter out any permissions
    // that they should not need.
    const loggedInPermissions = ServerContext.useStoreState(state => state.server.permissions);

    // The permissions that can be modified by this user.
    const editablePermissions = useDeepCompareMemo(() => {
        const cleaned = Object.keys(permissions)
            .map(key => Object.keys(permissions[key].keys).map(pkey => `${key}.${pkey}`));

        const list: string[] = ([] as string[]).concat.apply([], Object.values(cleaned));

        if (user.rootAdmin || (loggedInPermissions.length === 1 && loggedInPermissions[0] === '*')) {
            return list;
        }

        return list.filter(key => loggedInPermissions.indexOf(key) >= 0);
    }, [ permissions, loggedInPermissions ]);

    return (
        <Modal {...props} top={false} showSpinnerOverlay={isSubmitting}>
            <h2 css={tw`text-2xl`} ref={ref}>
                {subuser ?
                    `${canEditUser ? 'Modify' : 'View'} permissions for ${subuser.email}`
                    :
                    'Create new subuser'
                }
            </h2>
            <FlashMessageRender byKey={'user:edit'} css={tw`mt-4`}/>
            {(!user.rootAdmin && loggedInPermissions[0] !== '*') &&
            <div css={tw`mt-4 pl-4 py-2 border-l-4 border-cyan-400`}>
                <p css={tw`text-sm text-neutral-300`}>
                    Only permissions which your account is currently assigned may be selected when creating or
                    modifying other users.
                </p>
            </div>
            }
            {!subuser &&
            <div css={tw`mt-6`}>
                <Field
                    name={'email'}
                    label={'User Email'}
                    description={'Enter the email address of the user you wish to invite as a subuser for this server.'}
                />
            </div>
            }
            <div css={tw`my-6`}>
                {Object.keys(permissions).filter(key => key !== 'websocket').map((key, index) => {
                    const group = Object.keys(permissions[key].keys).map(pkey => `${key}.${pkey}`);

                    return (
                        <PermissionTitledBox
                            key={`permission_${key}`}
                            isEditable={canEditUser}
                            permission={key}
                            permissions={group}
                            css={index > 0 ? tw`mt-4` : undefined}
                        >
                            <p css={tw`text-sm text-neutral-400 mb-4`}>
                                {permissions[key].description}
                            </p>
                            {Object.keys(permissions[key].keys).map(pkey => (
                                <PermissionLabel
                                    key={`permission_${key}_${pkey}`}
                                    htmlFor={`permission_${key}_${pkey}`}
                                    className={(!canEditUser || editablePermissions.indexOf(`${key}.${pkey}`) < 0) ? 'disabled' : undefined}
                                >
                                    <div css={tw`p-2`}>
                                        <Checkbox
                                            id={`permission_${key}_${pkey}`}
                                            name={'permissions'}
                                            value={`${key}.${pkey}`}
                                            css={tw`w-5 h-5 mr-2`}
                                            disabled={!canEditUser || editablePermissions.indexOf(`${key}.${pkey}`) < 0}
                                        />
                                    </div>
                                    <div css={tw`flex-1`}>
                                        <Label as={'p'} css={tw`font-medium`}>{pkey}</Label>
                                        {permissions[key].keys[pkey].length > 0 &&
                                        <p css={tw`text-xs text-neutral-400 mt-1`}>
                                            {permissions[key].keys[pkey]}
                                        </p>
                                        }
                                    </div>
                                </PermissionLabel>
                            ))}
                        </PermissionTitledBox>
                    );
                })}
            </div>
            <Can action={subuser ? 'user.update' : 'user.create'}>
                <div css={tw`pb-6 flex justify-end`}>
                    <Button type={'submit'} css={tw`w-full sm:w-auto`}>
                        {subuser ? 'Save' : 'Invite User'}
                    </Button>
                </div>
            </Can>
        </Modal>
    );
});

export default ({ subuser, ...props }: Props) => {
    const ref = useRef<HTMLHeadingElement>(null);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSubuser = ServerContext.useStoreActions(actions => actions.subusers.appendSubuser);
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('user:edit');
        createOrUpdateSubuser(uuid, values, subuser)
            .then(subuser => {
                appendSubuser(subuser);
                props.onDismissed();
            })
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ key: 'user:edit', error });

                if (ref.current) {
                    ref.current.scrollIntoView();
                }
            });
    };

    useEffect(() => {
        return () => {
            clearFlashes('user:edit');
        };
    }, []);

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                email: subuser?.email || '',
                permissions: subuser?.permissions || [],
            } as Values}
            validationSchema={object().shape({
                email: string().email('A valid email address must be provided.').required('A valid email address must be provided.'),
                permissions: array().of(string()),
            })}
        >
            <Form>
                <EditSubuserModal ref={ref} subuser={subuser} {...props}/>
            </Form>
        </Formik>
    );
};
