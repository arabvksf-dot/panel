import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components/macro';

export default createGlobalStyle`
    @font-face {
        font-family: 'Cairo';
        font-style: normal;
        font-display: swap;
        font-weight: 400 700;
        src: url('/assets/fonts/Cairo-arabic.woff2') format('woff2');
        unicode-range: U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC;
    }

    @font-face {
        font-family: 'Cairo';
        font-style: normal;
        font-display: swap;
        font-weight: 400 700;
        src: url('/assets/fonts/Cairo-latin.woff2') format('woff2');
        unicode-range: U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD;
    }

    body {
        ${tw`font-sans`};
        background: var(--gradient-page);
        background-attachment: fixed;
        color: var(--color-text);
        font-family: var(--font-main);
        font-size: calc(1rem * var(--font-size-scale));
        letter-spacing: 0;
    }

    html[dir='rtl'] {
        direction: rtl;
    }

    button,
    input,
    select,
    textarea {
        font: inherit;
    }

    button {
        font-weight: 600;
        transition: color var(--motion-normal) ease, background-color var(--motion-normal) ease,
            border-color var(--motion-normal) ease, box-shadow var(--motion-normal) ease,
            transform var(--motion-normal) ease;
    }

    :root[data-motion='off'] *,
    :root[data-motion='off'] *::before,
    :root[data-motion='off'] *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }

    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-normal font-header`};
    }

    p {
        ${tw`text-neutral-200 leading-snug font-sans`};
    }

    form {
        ${tw`m-0`};
    }

    textarea, select, input, button, button:focus, button:focus-visible {
        ${tw`outline-none`};
    }

    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield !important;
    }

     
    ::-webkit-scrollbar {
        background: none;
        width: 16px;
        height: 16px;
    }

    ::-webkit-scrollbar-thumb {
        border: 0 solid transparent;
        border-right-width: 4px;
        border-left-width: 4px;
        -webkit-border-radius: 9px 4px;
        -webkit-box-shadow: inset 0 0 0 1px var(--color-text-muted), inset 0 0 0 4px var(--color-surface-raised);
    }

    ::-webkit-scrollbar-track-piece {
        margin: 4px 0;
    }

    ::-webkit-scrollbar-thumb:horizontal {
        border-right-width: 0;
        border-left-width: 0;
        border-top-width: 4px;
        border-bottom-width: 4px;
        -webkit-border-radius: 4px 9px;
    }

    ::-webkit-scrollbar-corner {
        background: transparent;
    }
`;
