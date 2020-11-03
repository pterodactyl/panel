import React from 'react';
import Modal, { ModalProps } from '@/components/elements/Modal';
import ModalContext from '@/context/ModalContext';

export interface AsModalProps {
    visible: boolean;
    onModalDismissed?: () => void;
}

type ExtendedModalProps = Omit<ModalProps, 'appear' | 'visible' | 'onDismissed'>;

interface State {
    render: boolean;
    visible: boolean;
    showSpinnerOverlay?: boolean;
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
                    showSpinnerOverlay: undefined,
                };
            }

            get modalProps () {
                return {
                    ...(typeof modalProps === 'function' ? modalProps(this.props) : modalProps),
                    showSpinnerOverlay: this.state.showSpinnerOverlay,
                };
            }

            componentDidUpdate (prevProps: Readonly<P & AsModalProps>) {
                if (prevProps.visible && !this.props.visible) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ visible: false, showSpinnerOverlay: false });
                } else if (!prevProps.visible && this.props.visible) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ render: true, visible: true });
                }
            }

            dismiss = () => this.setState({ visible: false });

            toggleSpinner = (value?: boolean) => this.setState({ showSpinnerOverlay: value });

            render () {
                return (
                    this.state.render ?
                        <Modal
                            appear
                            visible={this.state.visible}
                            onDismissed={() => this.setState({ render: false }, () => {
                                if (typeof this.props.onModalDismissed === 'function') {
                                    this.props.onModalDismissed();
                                }
                            })}
                            {...this.modalProps}
                        >
                            <ModalContext.Provider
                                value={{
                                    dismiss: this.dismiss.bind(this),
                                    toggleSpinner: this.toggleSpinner.bind(this),
                                }}
                            >
                                <Component {...this.props}/>
                            </ModalContext.Provider>
                        </Modal>
                        :
                        null
                );
            }
        };
    };
}

export default asModal;
