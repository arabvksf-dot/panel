import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import updateAccountPreferences from '@/api/account/updateAccountPreferences';
import applyPreferences from '@/helpers/preferences';
import { httpErrorToHuman } from '@/api/http';

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data);
    const appearanceEnabled = useStoreState((state: ApplicationStore) => state.settings.data!.appearanceEnabled);
    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const { t } = useTranslation('appearance');
    const [language, setLanguage] = useState(user?.language === 'ar' ? 'ar' : 'en');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    if (!user || user.onboardingCompleted) {
        return null;
    }

    const continueToPanel = () => {
        setSaving(true);
        setError('');

        updateAccountPreferences({
            language,
            appearance: user.appearance,
            onboarding_completed: true,
        })
            .then(() => {
                updateUserData({ language, onboardingCompleted: true });
                applyPreferences(language, user.appearance, appearanceEnabled);
            })
            .catch((requestError) => setError(httpErrorToHuman(requestError) || t('error')))
            .finally(() => setSaving(false));
    };

    return (
        <div className={'language-onboarding-backdrop'}>
            <section
                className={'language-onboarding-panel'}
                role={'dialog'}
                aria-modal={'true'}
                aria-labelledby={'language-onboarding-title'}
                dir={'auto'}
            >
                <h2 id={'language-onboarding-title'}>{t('onboarding_title')}</h2>
                <p>{t('onboarding_description')}</p>
                <div style={{ display: 'grid', gap: '0.65rem', margin: '1.5rem 0' }}>
                    {[
                        { code: 'ar', label: 'العربية' },
                        { code: 'en', label: 'English' },
                    ].map((option) => (
                        <button
                            key={option.code}
                            className={'language-choice'}
                            type={'button'}
                            aria-pressed={language === option.code}
                            onClick={() => setLanguage(option.code)}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
                {error && <p role={'alert'}>{error}</p>}
                <button className={'theme-action'} type={'button'} disabled={saving} onClick={continueToPanel}>
                    {saving ? '…' : t('continue')}
                </button>
            </section>
        </div>
    );
};
