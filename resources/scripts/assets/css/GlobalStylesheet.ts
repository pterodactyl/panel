import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components/macro';

export default createGlobalStyle`
    @import url('//fonts.googleapis.com/css?family=Rubik:300,400,500&display=swap');
    @import url('//fonts.googleapis.com/css?family=IBM+Plex+Mono|IBM+Plex+Sans:500&display=swap');

    body {
        ${tw`font-sans bg-neutral-800 text-neutral-200`};
        letter-spacing: 0.015em;
    }
   
    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-normal font-header`};
    }
    
    p {
        ${tw`text-neutral-200 leading-snug font-sans`};
    }
`;
