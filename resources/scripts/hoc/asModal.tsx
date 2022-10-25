import * as React from 'react';
import PortaledModal, { ModalProps } from '@/components/elements/Modal';
import ModalContext, { ModalContextValues } from '@/context/ModalContext';
import isEqual from 'react-fast-compare';

export interface AsModalProps {
    visible: boolean;
    onModalDismissed?: () => void;
}

export type SettableModalProps = Omit<ModalProps, 'appear' | 'visible' | 'onDismissed'>;

interface State {
    render: boolean;
    visible: boolean;
    propOverrides: Partial<SettableModalProps>;
}

type ExtendedComponentType<T> = (C: React.ComponentType<T>) => React.ComponentType<T & AsModalProps>;

// eslint-disable-next-line @typescript-eslint/ban-types
function asModal<P extends {}>(
    modalProps?: SettableModalProps | ((props: P) => SettableModalProps)
): ExtendedComponentType<P> {
    return function (Component) {
        return class extends React.PureComponent<P & AsModalProps, State> {
            static displayName = `asModal(${Component.displayName})`;

            constructor(props: P & AsModalProps) {
                super(props);

                this.state = {
                    render: props.visible,
                    visible: props.visible,
                    propOverrides: {},
                };
            }

            get computedModalProps(): Readonly<SettableModalProps & { visible: boolean }> {
                return {
                    ...(typeof modalProps === 'function' ? modalProps(this.props) : modalProps),
                    ...this.state.propOverrides,
                    visible: this.state.visible,
                };
            }

            /**
             * @this {React.PureComponent<P & AsModalProps, State>}
             */
            componentDidUpdate(prevProps: Readonly<P & AsModalProps>, prevState: Readonly<State>) {
                if (prevProps.visible && !this.props.visible) {
                    this.setState({ visible: false, propOverrides: {} });
                } else if (!prevProps.visible && this.props.visible) {
                    this.setState({ render: true, visible: true });
                }
                if (!this.state.render && !isEqual(prevState.propOverrides, this.state.propOverrides)) {
                    this.setState({ propOverrides: {} });
                }
            }

            dismiss = () => this.setState({ visible: false });

            setPropOverrides: ModalContextValues['setPropOverrides'] = (value) =>
                this.setState((state) => ({
                    propOverrides: !value ? {} : typeof value === 'function' ? value(state.propOverrides) : value,
                }));

            /**
             * @this {React.PureComponent<P & AsModalProps, State>}
             */
            render() {
                if (!this.state.render) return null;

                return (
                    <PortaledModal
                        appear
                        onDismissed={() =>
                            this.setState({ render: false }, () => {
                                if (typeof this.props.onModalDismissed === 'function') {
                                    this.props.onModalDismissed();
                                }
                            })
                        }
                        {...this.computedModalProps}
                    >
                        <ModalContext.Provider
                            value={{
                                dismiss: this.dismiss.bind(this),
                                setPropOverrides: this.setPropOverrides.bind(this),
                            }}
                        >
                            <Component {...this.props} />
                        </ModalContext.Provider>
                    </PortaledModal>
                );
            }
        };
    };
}

export default asModal;
