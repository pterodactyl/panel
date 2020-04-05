import React, { useEffect, useRef, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Field, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { object, string } from 'yup';
import { debounce } from 'lodash-es';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import InputSpinner from '@/components/elements/InputSpinner';
import getServers from '@/api/getServers';
import { Server } from '@/api/server/getServer';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { Link } from 'react-router-dom';

type Props = RequiredModalProps;

interface Values {
    term: string;
}

const SearchWatcher = () => {
    const { values, submitForm } = useFormikContext<Values>();

    useEffect(() => {
        if (values.term.length >= 3) {
            submitForm();
        }
    }, [ values.term ]);

    return null;
};

export default ({ ...props }: Props) => {
    const ref = useRef<HTMLInputElement>(null);
    const [ loading, setLoading ] = useState(false);
    const [ servers, setServers ] = useState<Server[]>([]);
    const isAdmin = useStoreState(state => state.user.data!.rootAdmin);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const search = debounce(({ term }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        setLoading(true);
        setSubmitting(false);
        clearFlashes('search');
        getServers(term)
            .then(servers => setServers(servers.items.filter((_, index) => index < 5)))
            .catch(error => {
                console.error(error);
                addError({ key: 'search', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, 500);

    useEffect(() => {
        if (props.visible) {
            setTimeout(() => ref.current?.focus(), 250);
        }
    }, [ props.visible ]);

    return (
        <Formik
            onSubmit={search}
            validationSchema={object().shape({
                term: string()
                    .min(3, 'Please enter at least three characters to begin searching.')
                    .required('A search term must be provided.'),
            })}
            initialValues={{ term: '' } as Values}
        >
            <Modal {...props}>
                <Form>
                    <FormikFieldWrapper
                        name={'term'}
                        label={'Search term'}
                        description={
                            isAdmin
                                ? 'Enter a server name, user email, or uuid to begin searching.'
                                : 'Enter a server name to begin searching.'
                        }
                    >
                        <SearchWatcher/>
                        <InputSpinner visible={loading}>
                            <Field
                                innerRef={ref}
                                name={'term'}
                                className={'input-dark'}
                            />
                        </InputSpinner>
                    </FormikFieldWrapper>
                </Form>
                {servers.length > 0 &&
                <div className={'mt-6'}>
                    {
                        servers.map(server => (
                            <Link
                                key={server.uuid}
                                to={`/server/${server.id}`}
                                className={'flex items-center block bg-neutral-900 p-4 rounded border-l-4 border-neutral-900 no-underline hover:shadow hover:border-cyan-500 transition-colors duration-250'}
                                onClick={() => props.onDismissed()}
                            >
                                <div>
                                    <p className={'text-sm'}>{server.name}</p>
                                    <p className={'mt-1 text-xs text-neutral-400'}>
                                        {
                                            server.allocations.filter(alloc => alloc.default).map(allocation => (
                                                <span key={allocation.ip + allocation.port.toString()}>{allocation.alias || allocation.ip}:{allocation.port}</span>
                                            ))
                                        }
                                    </p>
                                </div>
                                <div className={'flex-1 text-right'}>
                                    <span className={'text-xs py-1 px-2 bg-cyan-800 text-cyan-100 rounded'}>
                                        {server.node}
                                    </span>
                                </div>
                            </Link>
                        ))
                    }
                </div>
                }
            </Modal>
        </Formik>
    );
};
