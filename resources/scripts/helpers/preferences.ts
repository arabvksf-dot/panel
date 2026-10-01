import i18n from '@/i18n';
import { AccountAppearance } from '@/api/account/updateAccountPreferences';

export default (language: string, appearance: AccountAppearance, appearanceEnabled = true): void => {
    const root = document.documentElement;
    if (appearanceEnabled) {
        root.dataset.theme = appearance.theme;
        root.dataset.accent = appearance.accent;
        root.dataset.motion = appearance.motion ? 'on' : 'off';
        root.style.setProperty('--font-size-scale', String(appearance.font_size / 16));
    } else {
        root.dataset.theme = 'dark';
        root.dataset.accent = 'violet';
        root.dataset.motion = 'on';
        root.style.removeProperty('--font-size-scale');
    }
    root.lang = language;
    root.dir = language === 'ar' ? 'rtl' : 'ltr';
    void i18n.changeLanguage(language);
};
