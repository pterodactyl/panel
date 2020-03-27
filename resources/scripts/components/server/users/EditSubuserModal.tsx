import React, { forwardRef, useRef } from 'react';
import { Subuser } from '@/state/server/subusers';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { array, object, string } from 'yup';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Checkbox from '@/components/elements/Checkbox';
import styled from 'styled-components';
import classNames from 'classnames';
import createOrUpdateSubuser from '@/api/server/users/createOrUpdateSubuser';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';

type Props = {
    subuser?: Subuser;
} & RequiredModalProps;

interface Values {
    email: string;
    permissions: string[];
}

const PermissionLabel = styled.label`
  ${tw`flex items-center border border-transparent rounded p-2 cursor-pointer`};
  text-transform: none;

  &:hover {
    ${tw`border-neutral-500 bg-neutral-800`};
  }
`;

const EditSubuserModal = forwardRef<HTMLHeadingElement, Props>(({ subuser, ...props }, ref) => {
    const { values, isSubmitting, setFieldValue } = useFormikContext<Values>();
    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <h3 ref={ref}>{subuser ? `Modify permissions for ${subuser.email}` : 'Create new subuser'}</h3>
            <FlashMessageRender byKey={'user:edit'} className={'mt-4'}/>
            {!subuser &&
            <div className={'mt-6'}>
                <Field
                    name={'email'}
                    label={'User Email'}
                    description={'Enter the email address of the user you wish to invite as a subuser for this server.'}
                />
            </div>
            }
            <div className={'mt-6'}>
                {Object.keys(permissions).filter(key => key !== 'websocket').map((key, index) => (
                    <TitledGreyBox
                        key={key}
                        title={
                            <div className={'flex items-center'}>
                                <p className={'text-sm uppercase flex-1'}>{key}</p>
                                <input
                                    type={'checkbox'}
                                    onClick={e => {
                                        if (e.currentTarget.checked) {
                                            setFieldValue('permissions', [
                                                ...values.permissions,
                                                ...Object.keys(permissions[key].keys)
                                                    .map(pkey => `${key}.${pkey}`)
                                                    .filter(permission => values.permissions.indexOf(permission) === -1),
                                            ]);
                                        } else {
                                            setFieldValue('permissions', [
                                                ...values.permissions.filter(
                                                    permission => Object.keys(permissions[key].keys)
                                                        .map(pkey => `${key}.${pkey}`)
                                                        .indexOf(permission) < 0,
                                                ),
                                            ]);
                                        }
                                    }}
                                />
                            </div>
                        }
                        className={index !== 0 ? 'mt-4' : undefined}
                    >
                        <p className={'text-sm text-neutral-400 mb-4'}>
                            {permissions[key].description}
                        </p>
                        {Object.keys(permissions[key].keys).map((pkey, index) => (
                            <PermissionLabel
                                htmlFor={`permission_${key}_${pkey}`}
                                className={classNames('transition-colors duration-75', {
                                    'mt-2': index !== 0,
                                })}
                            >
                                <div className={'p-2'}>
                                    <Checkbox
                                        id={`permission_${key}_${pkey}`}
                                        name={'permissions'}
                                        value={`${key}.${pkey}`}
                                        className={'w-5 h-5 mr-2'}
                                    />
                                </div>
                                <div className={'flex-1'}>
                                    <span className={'input-dark-label font-medium'}>
                                        {pkey}
                                    </span>
                                    {permissions[key].keys[pkey].length > 0 &&
                                    <p className={'text-xs text-neutral-400 mt-1'}>
                                        {permissions[key].keys[pkey]}
                                    </p>
                                    }
                                </div>
                            </PermissionLabel>
                        ))}
                    </TitledGreyBox>
                ))}
            </div>
            <div className={'mt-6 pb-6 flex justify-end'}>
                <button className={'btn btn-primary btn-sm'} type={'submit'}>
                    {subuser ? 'Save' : 'Invite User'}
                </button>
            </div>
        </Modal>
    );
});

export default ({ subuser, ...props }: Props) => {
    const ref = useRef<HTMLHeadingElement>(null);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSubuser = ServerContext.useStoreActions(actions => actions.subusers.appendSubuser);

    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

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
                addError({ key: 'user:edit', message: httpErrorToHuman(error) });

                if (ref.current) {
                    ref.current.scrollIntoView();
                }
            });
    };

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
