import React, { useContext, useEffect, useRef } from 'react';
import { Subuser } from '@/state/server/subusers';
import { Form, Formik } from 'formik';
import { array, object, string } from 'yup';
import Field from '@/components/elements/Field';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import createOrUpdateSubuser from '@/api/server/users/createOrUpdateSubuser';
import { ServerContext } from '@/state/server';
import FlashMessageRender from '@/components/FlashMessageRender';
import Can from '@/components/elements/Can';
import { usePermissions } from '@/plugins/usePermissions';
import { useDeepCompareMemo } from '@/plugins/useDeepCompareMemo';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import PermissionTitleBox from '@/components/server/users/PermissionTitleBox';
import asModal from '@/hoc/asModal';
import PermissionRow from '@/components/server/users/PermissionRow';
import ModalContext from '@/context/ModalContext';

type Props = {
    subuser?: Subuser;
};

interface Values {
    email: string;
    permissions: string[];
}

const EditSubuserModal = ({ subuser }: Props) => {
    const ref = useRef<HTMLHeadingElement>(null);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSubuser = ServerContext.useStoreActions(actions => actions.subusers.appendSubuser);
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const { dismiss, setPropOverrides } = useContext(ModalContext);

    const isRootAdmin = useStoreState(state => state.user.data!.rootAdmin);
    const permissions = useStoreState(state => state.permissions.data);
    // The currently logged in user's permissions. We're going to filter out any permissions
    // that they should not need.
    const loggedInPermissions = ServerContext.useStoreState(state => state.server.permissions);
    const [ canEditUser ] = usePermissions(subuser ? [ 'user.update' ] : [ 'user.create' ]);

    // The permissions that can be modified by this user.
    const editablePermissions = useDeepCompareMemo(() => {
        const cleaned = Object.keys(permissions)
            .map(key => Object.keys(permissions[key].keys).map(pkey => `${key}.${pkey}`));

        const list: string[] = ([] as string[]).concat.apply([], Object.values(cleaned));

        if (isRootAdmin || (loggedInPermissions.length === 1 && loggedInPermissions[0] === '*')) {
            return list;
        }

        return list.filter(key => loggedInPermissions.indexOf(key) >= 0);
    }, [ isRootAdmin, permissions, loggedInPermissions ]);

    const submit = (values: Values) => {
        setPropOverrides({ showSpinnerOverlay: true });
        clearFlashes('user:edit');

        createOrUpdateSubuser(uuid, values, subuser)
            .then(subuser => {
                appendSubuser(subuser);
                dismiss();
            })
            .catch(error => {
                console.error(error);
                setPropOverrides(null);
                clearAndAddHttpError({ key: 'user:edit', error });

                if (ref.current) {
                    ref.current.scrollIntoView();
                }
            });
    };

    useEffect(() => () => {
        clearFlashes('user:edit');
    }, []);

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                email: subuser?.email || '',
                permissions: subuser?.permissions || [],
            } as Values}
            validationSchema={object().shape({
                email: string()
                    .max(191, '电子邮件地址不得超过 191 个字符。')
                    .email('必须提供有效的电子邮件地址。')
                    .required('必须提供有效的电子邮件地址。'),
                permissions: array().of(string()),
            })}
        >
            <Form>
                <div css={tw`flex justify-between`}>
                    <h2 css={tw`text-2xl`} ref={ref}>
                        {subuser ? `${canEditUser ? '可更改' : '不可更改'} 权限于用户 ${subuser.email}` : '创建新子用户'}
                    </h2>
                    <div>
                        <Button type={'submit'} css={tw`w-full sm:w-auto`}>
                            {subuser ? '保存' : '邀请用户'}
                        </Button>
                    </div>
                </div>
                <FlashMessageRender byKey={'user:edit'} css={tw`mt-4`} />
                {(!isRootAdmin && loggedInPermissions[0] !== '*') &&
                    <div css={tw`mt-4 pl-4 py-2 border-l-4 border-cyan-400`}>
                        <p css={tw`text-sm text-neutral-300`}>
                            创建或修改其他用户时，只能选择您帐户当前分配的权限。
                        </p>
                    </div>
                }
                {!subuser &&
                    <div css={tw`mt-6`}>
                        <Field
                            name={'email'}
                            label={'用户邮箱地址'}
                            description={'输入您希望邀请为该服务器子用户的用户的电子邮件地址。'}
                        />
                    </div>
                }
                <div css={tw`my-6`}>
                    {Object.keys(permissions).filter(key => key !== 'websocket').map((key, index) => (
                        <PermissionTitleBox
                            key={`permission_${key}`}
                            title={key}
                            isEditable={canEditUser}
                            permissions={Object.keys(permissions[key].keys).map(pkey => `${key}.${pkey}`)}
                            css={index > 0 ? tw`mt-4` : undefined}
                        >
                            <p css={tw`text-sm text-neutral-400 mb-4`}>
                                {permissions[key].description}
                            </p>
                            {Object.keys(permissions[key].keys).map(pkey => (
                                <PermissionRow
                                    key={`permission_${key}.${pkey}`}
                                    permission={`${key}.${pkey}`}
                                    disabled={!canEditUser || editablePermissions.indexOf(`${key}.${pkey}`) < 0}
                                />
                            ))}
                        </PermissionTitleBox>
                    ))}
                </div>
                <Can action={subuser ? 'user.update' : 'user.create'}>
                    <div css={tw`pb-6 flex justify-end`}>
                        <Button type={'submit'} css={tw`w-full sm:w-auto`}>
                            {subuser ? '保存' : '邀请用户'}
                        </Button>
                    </div>
                </Can>
            </Form>
        </Formik>
    );
};

export default asModal<Props>({
    top: false,
})(EditSubuserModal);
