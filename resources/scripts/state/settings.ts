import { action, Action } from 'easy-peasy';

export interface SiteSettings {
    name: string;
    logo: string;
    favicon: string;
    discordOAuthEnabled: boolean;
    aiAssistantEnabled: boolean;
    aiErrorAnalysisEnabled: boolean;
    aiMaxHistory: number;
    appearanceOptions: {
        themes: Array<'dark' | 'light'>;
        accents: Array<'violet' | 'blue' | 'emerald' | 'rose' | 'amber'>;
        font_sizes: { min: number; max: number; step: number };
    };
    appearanceEnabled: boolean;
    locale: string;
    recaptcha: {
        enabled: boolean;
        siteKey: string;
    };
}

export interface SettingsStore {
    data?: SiteSettings;
    setSettings: Action<SettingsStore, SiteSettings>;
}

const settings: SettingsStore = {
    data: undefined,

    setSettings: action((state, payload) => {
        state.data = payload;
    }),
};

export default settings;
