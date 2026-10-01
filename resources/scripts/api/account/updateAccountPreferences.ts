import http from '@/api/http';

export interface AccountAppearance {
    theme: 'dark' | 'light';
    accent: 'violet' | 'blue' | 'emerald' | 'rose' | 'amber';
    motion: boolean;
    font_size: number;
}

export interface AccountPreferences {
    language: string;
    appearance: AccountAppearance;
    onboarding_completed: boolean;
}

export default (preferences: AccountPreferences): Promise<void> => {
    return http.put('/api/client/account/preferences', preferences);
};
