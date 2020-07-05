import React, { useEffect, useRef, useState } from 'react';
import { CSSTransition } from 'react-transition-group';
import styled from 'styled-components/macro';
import tw from 'twin.macro';
import Fade from '@/components/elements/Fade';

interface Props {
    children: React.ReactNode;
    renderToggle: (onClick: (e: React.MouseEvent<any, MouseEvent>) => void) => React.ReactChild;
}

export const DropdownButtonRow = styled.button<{ danger?: boolean }>`
    ${tw`p-2 flex items-center rounded w-full text-neutral-500`};
    transition: 150ms all ease;

    &:hover {
        ${props => props.danger ? tw`text-red-700 bg-red-100` : tw`text-neutral-700 bg-neutral-100`};
    }
`;

const DropdownMenu = ({ renderToggle, children }: Props) => {
    const menu = useRef<HTMLDivElement>(null);
    const [ posX, setPosX ] = useState(0);
    const [ visible, setVisible ] = useState(false);

    const onClickHandler = (e: React.MouseEvent<any, MouseEvent>) => {
        e.preventDefault();

        !visible && setPosX(e.clientX);
        setVisible(s => !s);
    };

    const windowListener = (e: MouseEvent) => {
        if (e.button === 2 || !visible || !menu.current) {
            return;
        }

        if (e.target === menu.current || menu.current.contains(e.target as Node)) {
            return;
        }

        if (e.target !== menu.current && !menu.current.contains(e.target as Node)) {
            setVisible(false);
        }
    };

    useEffect(() => {
        if (!visible || !menu.current) {
            return;
        }

        document.addEventListener('click', windowListener);
        menu.current.setAttribute(
            'style', `left: ${Math.round(posX - menu.current.clientWidth)}px`,
        );

        return () => {
            document.removeEventListener('click', windowListener);
        };
    }, [ visible ]);

    return (
        <div>
            {renderToggle(onClickHandler)}
            <Fade timeout={250} in={visible} unmountOnExit>
                <div
                    ref={menu}
                    onClick={e => {
                        e.stopPropagation();
                        setVisible(false);
                    }}
                    css={tw`absolute bg-white p-2 rounded border border-neutral-700 shadow-lg text-neutral-500 min-w-48`}
                >
                    {children}
                </div>
            </Fade>
        </div>
    );
};

export default DropdownMenu;
