import React from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import * as Yup from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import updateAccountPassword from '@/api/account/updateAccountPassword';
import { httpErrorToHuman } from '@/api/http';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

interface Values {
    current: string;
    password: string;
    confirmPassword: string;
}

const schema = Yup.object().shape({
    current: Yup.string().min(1).required('您必须提供当前密码。'),
    password: Yup.string().min(8).required(),
    confirmPassword: Yup.string().test('password', '密码确认与您输入的密码不匹配。', function (value) {
        return value === this.parent.password;
    }),
});

export default () => {
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    if (!user) {
        return null;
    }

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('account:password');
        updateAccountPassword({ ...values })
            .then(() => {
                // @ts-ignore
                window.location = '/auth/login';
            })
            .catch(error => addFlash({
                key: 'account:password',
                type: 'error',
                title: '错误',
                message: httpErrorToHuman(error),
            }))
            .then(() => setSubmitting(false));
    };

    return (
        <React.Fragment>
            <Formik
                onSubmit={submit}
                validationSchema={schema}
                initialValues={{ current: '', password: '', confirmPassword: '' }}
            >
                {
                    ({ isSubmitting, isValid }) => (
                        <React.Fragment>
                            <SpinnerOverlay size={'large'} visible={isSubmitting}/>
                            <Form css={tw`m-0`}>
                                <Field
                                    id={'current_password'}
                                    type={'password'}
                                    name={'current'}
                                    label={'当前密码'}
                                />
                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'new_password'}
                                        type={'password'}
                                        name={'password'}
                                        label={'新密码'}
                                        description={'您的新密码长度应至少为 8 个字符。'}
                                    />
                                </div>
                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'confirm_new_password'}
                                        type={'password'}
                                        name={'confirmPassword'}
                                        label={'确认新密码'}
                                    />
                                </div>
                                <div css={tw`mt-6`}>
                                    <Button size={'small'} disabled={isSubmitting || !isValid}>
                                        更新密码
                                    </Button>
                                </div>
                            </Form>
                        </React.Fragment>
                    )
                }
            </Formik>
        </React.Fragment>
    );
};
