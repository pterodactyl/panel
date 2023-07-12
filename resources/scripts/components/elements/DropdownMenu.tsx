import type { MouseEvent as ReactMouseEvent, ReactNode } from 'react';
import { createRef, PureComponent } from 'react';
import styled from 'styled-components';
import tw from 'twin.macro';

import FadeTransition from '@/components/elements/transitions/FadeTransition';

interface Props {
    children: ReactNode;
    renderToggle: (onClick: (e: ReactMouseEvent<unknown>) => void) => any;
}

export const DropdownButtonRow = styled.button<{ danger?: boolean }>`
    ${tw`p-2 flex items-center rounded w-full text-neutral-500`};
    transition: 150ms all ease;

    &:hover {
        ${props => (props.danger ? tw`text-red-700 bg-red-100` : tw`text-neutral-700 bg-neutral-100`)};
    }
`;

interface State {
    posX: number;
    visible: boolean;
}

class DropdownMenu extends PureComponent<Props, State> {
    menu = createRef<HTMLDivElement>();

    override state: State = {
        posX: 0,
        visible: false,
    };

    override componentWillUnmount() {
        this.removeListeners();
    }

    override componentDidUpdate(_prevProps: Readonly<Props>, prevState: Readonly<State>) {
        const menu = this.menu.current;

        if (this.state.visible && !prevState.visible && menu) {
            document.addEventListener('click', this.windowListener);
            document.addEventListener('contextmenu', this.contextMenuListener);
            menu.style.left = `${Math.round(this.state.posX - menu.clientWidth)}px`;
        }

        if (!this.state.visible && prevState.visible) {
            this.removeListeners();
        }
    }

    removeListeners() {
        document.removeEventListener('click', this.windowListener);
        document.removeEventListener('contextmenu', this.contextMenuListener);
    }

    onClickHandler(e: ReactMouseEvent<unknown>) {
        e.preventDefault();
        this.triggerMenu(e.clientX);
    }

    contextMenuListener() {
        this.setState({ visible: false });
    }

    windowListener(e: MouseEvent): any {
        const menu = this.menu.current;

        if (e.button === 2 || !this.state.visible || !menu) {
            return;
        }

        if (e.target === menu || menu.contains(e.target as Node)) {
            return;
        }

        if (e.target !== menu && !menu.contains(e.target as Node)) {
            this.setState({ visible: false });
        }
    }

    triggerMenu(posX: number) {
        this.setState(s => ({
            posX: !s.visible ? posX : s.posX,
            visible: !s.visible,
        }));
    }

    override render() {
        return (
            <div>
                {this.props.renderToggle(this.onClickHandler)}

                <FadeTransition duration="duration-150" show={this.state.visible} appear unmount>
                    <div
                        ref={this.menu}
                        onClick={e => {
                            e.stopPropagation();
                            this.setState({ visible: false });
                        }}
                        style={{ width: '12rem' }}
                        css={tw`absolute bg-white p-2 rounded border border-neutral-700 shadow-lg text-neutral-500 z-50`}
                    >
                        {this.props.children}
                    </div>
                </FadeTransition>
            </div>
        );
    }
}

export default DropdownMenu;
