import React from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import login from '@/api/auth/login';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { FormikProps, withFormik } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import { httpErrorToHuman } from '@/api/http';

interface Values {
    username: string;
    password: string;
}

type OwnProps = RouteComponentProps & {
    clearFlashes: any;
    addFlash: any;
}

const LoginContainer = ({ isSubmitting }: OwnProps & FormikProps<Values>) => (
    <React.Fragment>
        <h2 className={'text-center text-neutral-100 font-medium py-4'}>
            Login to Continue
        </h2>
        <FlashMessageRender className={'mb-2'}/>
        <LoginFormContainer>
            <label htmlFor={'username'}>Username or Email</label>
            <Field
                type={'text'}
                id={'username'}
                name={'username'}
                className={'input'}
            />
            <div className={'mt-6'}>
                <label htmlFor={'password'}>Password</label>
                <Field
                    type={'password'}
                    id={'password'}
                    name={'password'}
                    className={'input'}
                />
            </div>
            <div className={'mt-6'}>
                <button
                    type={'submit'}
                    className={'btn btn-primary btn-jumbo'}
                >
                    {isSubmitting ?
                        <span className={'spinner white'}>&nbsp;</span>
                        :
                        'Login'
                    }
                </button>
            </div>
            <div className={'mt-6 text-center'}>
                <Link
                    to={'/auth/password'}
                    className={'text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600'}
                >
                    Forgot password?
                </Link>
            </div>
        </LoginFormContainer>
    </React.Fragment>
);

const EnhancedForm = withFormik<OwnProps, Values>({
    displayName: 'LoginContainerForm',

    mapPropsToValues: (props) => ({
        username: '',
        password: '',
    }),

    validationSchema: () => object().shape({
        username: string().required('A username or email must be provided.'),
        password: string().required('Please enter your account password.'),
    }),

    handleSubmit: ({ username, password }, { props, setSubmitting }) => {
        props.clearFlashes();
        login(username, password)
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    window.location = response.intended || '/';
                    return;
                }

                props.history.replace('/auth/login/checkpoint', { token: response.confirmationToken });
            })
            .catch(error => {
                console.error(error);

                setSubmitting(false);
                props.addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            });
    },
})(LoginContainer);

export default (props: RouteComponentProps) => {
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    return (
        <EnhancedForm
            {...props}
            addFlash={addFlash}
            clearFlashes={clearFlashes}
        />
    );
};
