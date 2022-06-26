import React, { CSSProperties } from 'react';
import { IconDefinition } from '@fortawesome/fontawesome-svg-core';
import tw from 'twin.macro';

interface Props {
    icon: IconDefinition;
    className?: string;
    style?: CSSProperties;
}

const Icon = ({ icon, className, style }: Props) => {
    const [width, height, , , paths] = icon.icon;

    return (
        <svg
            xmlns={'http://www.w3.org/2000/svg'}
            viewBox={`0 0 ${width} ${height}`}
            css={tw`fill-current inline-block`}
            className={className}
            style={style}
        >
            {(Array.isArray(paths) ? paths : [paths]).map((path, index) => (
                <path key={`svg_path_${index}`} d={path} />
            ))}
        </svg>
    );
};

export default Icon;
