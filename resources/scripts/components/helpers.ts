import type { StyledComponent } from 'styled-components';

export const withSubComponents = <C extends StyledComponent<any, any>, P extends Record<string, any>>(
    component: C,
    properties: P,
): C & P => {
    Object.keys(properties).forEach((key: keyof P) => {
        (component as any)[key] = properties[key];
    });

    return component as C & P;
};
