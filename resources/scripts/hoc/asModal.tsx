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
    showSpinnerOverlay: boolean;
}

function asModal (modalProps?: ExtendedModalProps) {
    // eslint-disable-next-line @typescript-eslint/ban-types
    return function <T extends object> (Component: React.ComponentType<T>) {
        return class extends React.PureComponent <T & AsModalProps, State> {
            static displayName = `asModal(${Component.displayName})`;

            constructor (props: T & AsModalProps) {
                super(props);

                this.state = {
                    render: props.visible,
                    visible: props.visible,
                    showSpinnerOverlay: modalProps?.showSpinnerOverlay || false,
                };
            }

            componentDidUpdate (prevProps: Readonly<T & AsModalProps>) {
                if (prevProps.visible && !this.props.visible) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ visible: false });
                } else if (!prevProps.visible && this.props.visible) {
                    // noinspection JSPotentiallyInvalidUsageOfThis
                    this.setState({ render: true, visible: true });
                }
            }

            dismiss = () => this.setState({ visible: false });

            toggleSpinner = (value?: boolean) => this.setState({ showSpinnerOverlay: value || false });

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
                                    showSpinnerOverlay={this.state.showSpinnerOverlay}
                                    onDismissed={() => this.setState({ render: false }, () => {
                                        if (typeof this.props.onModalDismissed === 'function') {
                                            this.props.onModalDismissed();
                                        }
                                    })}
                                    {...modalProps}
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
