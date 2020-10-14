import React from 'react';
import Modal, { ModalProps } from '@/components/elements/Modal';
import ModalContext from '@/context/ModalContext';
import isEqual from 'react-fast-compare';

export interface AsModalProps {
    visible: boolean;
    onModalDismissed?: () => void;
}

type ExtendedModalProps = Omit<ModalProps, 'appear' | 'visible' | 'onDismissed'>;

interface State {
    render: boolean;
    visible: boolean;
    modalProps: ExtendedModalProps | undefined;
}

type ExtendedComponentType<T> = (C: React.ComponentType<T>) => React.ComponentType<T & AsModalProps>;

// eslint-disable-next-line @typescript-eslint/ban-types
function asModal<P extends object> (modalProps?: ExtendedModalProps | ((props: P) => ExtendedModalProps)): ExtendedComponentType<P> {
    return function (Component) {
        return class extends React.PureComponent <P & AsModalProps, State> {
            static displayName = `asModal(${Component.displayName})`;

            constructor (props: P & AsModalProps) {
                super(props);

                this.state = {
                    render: props.visible,
                    visible: props.visible,
                    modalProps: typeof modalProps === 'function' ? modalProps(this.props) : modalProps,
                };
            }

            componentDidUpdate (prevProps: Readonly<P & AsModalProps>) {
                const mapped = typeof modalProps === 'function' ? modalProps(this.props) : modalProps;
                if (!isEqual(this.state.modalProps, mapped)) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ modalProps: mapped });
                }

                if (prevProps.visible && !this.props.visible) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ visible: false });
                } else if (!prevProps.visible && this.props.visible) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ render: true, visible: true });
                }
            }

            dismiss = () => this.setState({ visible: false });

            toggleSpinner = (value?: boolean) => this.setState(s => ({
                modalProps: {
                    ...s.modalProps,
                    showSpinnerOverlay: value || false,
                },
            }));

            render () {
                return (
                    <ModalContext.Provider
                        value={{
                            dismiss: this.dismiss.bind(this),
                            toggleSpinner: this.toggleSpinner.bind(this),
                        }}
                    >
                        {
                            this.state.render ?
                                <Modal
                                    appear
                                    visible={this.state.visible}
                                    onDismissed={() => this.setState({ render: false }, () => {
                                        if (typeof this.props.onModalDismissed === 'function') {
                                            this.props.onModalDismissed();
                                        }
                                    })}
                                    {...this.state.modalProps}
                                >
                                    <Component {...this.props}/>
                                </Modal>
                                :
                                null
                        }
                    </ModalContext.Provider>
                );
            }
        };
    };
}

export default asModal;
