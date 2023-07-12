import tw, { TwStyle } from 'twin.macro';
import styled from 'styled-components';

export type FlashMessageType = 'success' | 'info' | 'warning' | 'error';

interface Props {
    title?: string;
    children: string;
    type?: FlashMessageType;
}

const styling = (type?: FlashMessageType): TwStyle | string => {
    switch (type) {
        case 'error':
            return tw`bg-red-600 border-red-800`;
        case 'info':
            return tw`bg-primary-600 border-primary-800`;
        case 'success':
            return tw`bg-green-600 border-green-800`;
        case 'warning':
            return tw`bg-yellow-600 border-yellow-800`;
        default:
            return '';
    }
};

const getBackground = (type?: FlashMessageType): TwStyle | string => {
    switch (type) {
        case 'error':
            return tw`bg-red-500`;
        case 'info':
            return tw`bg-primary-500`;
        case 'success':
            return tw`bg-green-500`;
        case 'warning':
            return tw`bg-yellow-500`;
        default:
            return '';
    }
};

const Container = styled.div<{ $type?: FlashMessageType }>`
    ${tw`p-2 border items-center leading-normal rounded flex w-full text-sm text-white`};
    ${props => styling(props.$type)};
`;
Container.displayName = 'MessageBox.Container';

const MessageBox = ({ title, children, type }: Props) => (
    <Container css={tw`lg:inline-flex`} $type={type} role={'alert'}>
        {title && (
            <span
                className={'title'}
                css={[
                    tw`flex rounded-full uppercase px-2 py-1 text-xs font-bold mr-3 leading-none`,
                    getBackground(type),
                ]}
            >
                {title}
            </span>
        )}
        <span css={tw`mr-2 text-left flex-auto`}>{children}</span>
    </Container>
);
MessageBox.displayName = 'MessageBox';

export default MessageBox;
