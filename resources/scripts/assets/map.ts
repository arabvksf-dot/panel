const assetRoot = '/assets';

export const assetMap = {
    logo: `${assetRoot}/logo/logo.png`,
    favicon: `${assetRoot}/logo/favicon.png`,
    images: `${assetRoot}/images`,
    icons: `${assetRoot}/icons`,
    fonts: {
        arabic: `${assetRoot}/fonts/Cairo-arabic.woff2`,
        latin: `${assetRoot}/fonts/Cairo-latin.woff2`,
    },
} as const;