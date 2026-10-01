import React, { FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import updateAccountPreferences from '@/api/account/updateAccountPreferences';
import applyPreferences from '@/helpers/preferences';
import { httpErrorToHuman } from '@/api/http';

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data);
    const options = useStoreState((state: ApplicationStore) => state.settings.data!.appearanceOptions);
    const appearanceEnabled = useStoreState((state: ApplicationStore) => state.settings.data!.appearanceEnabled);
    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const { t } = useTranslation('appearance');
    const [appearance, setAppearance] = useState(user!.appearance);
    const [language, setLanguage] = useState(user!.language);
    const [saving, setSaving] = useState(false);
    const [status, setStatus] = useState('');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setStatus('');

        updateAccountPreferences({
            language,
            appearance,
            onboarding_completed: user!.onboardingCompleted,
        })
            .then(() => {
                updateUserData({
                    language,
                    appearance: appearanceEnabled ? appearance : user!.appearance,
                });
                applyPreferences(language, appearanceEnabled ? appearance : user!.appearance, appearanceEnabled);
                setStatus(t('saved'));
            })
            .catch((error) => setStatus(httpErrorToHuman(error) || t('error')))
            .finally(() => setSaving(false));
    };

    return (
        <form onSubmit={submit} style={{ display: 'grid', gap: '1rem' }}>
            {appearanceEnabled && (
                <>
                    <label className={'theme-field'}>
                        {t('theme')}
                        <select
                            className={'theme-select'}
                            value={appearance.theme}
                            onChange={(event) =>
                                setAppearance({ ...appearance, theme: event.target.value as 'dark' | 'light' })
                            }
                        >
                            {options.themes.map((theme) => (
                                <option key={theme} value={theme}>
                                    {t(theme)}
                                </option>
                            ))}
                        </select>
                    </label>
                    <fieldset style={{ border: 0, margin: 0, padding: 0 }}>
                        <legend className={'theme-field'}>{t('accent')}</legend>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.65rem', marginTop: '0.5rem' }}>
                            {options.accents.map((accent) => (
                                <button
                                    key={accent}
                                    type={'button'}
                                    aria-label={t(`accent_${accent}`)}
                                    aria-pressed={appearance.accent === accent}
                                    onClick={() => setAppearance({ ...appearance, accent })}
                                    style={{
                                        width: '2rem',
                                        height: '2rem',
                                        borderRadius: '50%',
                                        border: `2px solid ${
                                            appearance.accent === accent ? 'var(--color-text)' : 'transparent'
                                        }`,
                                        backgroundColor: `var(--color-accent-${accent})`,
                                        cursor: 'pointer',
                                    }}
                                />
                            ))}
                        </div>
                    </fieldset>
                    <label style={{ display: 'flex', alignItems: 'center', gap: '0.65rem' }}>
                        <input
                            type={'checkbox'}
                            checked={appearance.motion}
                            onChange={(event) => setAppearance({ ...appearance, motion: event.target.checked })}
                        />
                        {t('motion')}
                    </label>
                    <label className={'theme-field'}>
                        {t('font_size')}: {appearance.font_size}px
                        <input
                            type={'range'}
                            min={options.font_sizes.min}
                            max={options.font_sizes.max}
                            step={options.font_sizes.step}
                            value={appearance.font_size}
                            onChange={(event) =>
                                setAppearance({ ...appearance, font_size: Number(event.target.value) })
                            }
                        />
                    </label>
                </>
            )}
            <label className={'theme-field'}>
                {t('language')}
                <select
                    className={'theme-select'}
                    value={language}
                    onChange={(event) => setLanguage(event.target.value)}
                >
                    <option value={'ar'}>العربية</option>
                    <option value={'en'}>English</option>
                </select>
            </label>
            <button className={'theme-action'} type={'submit'} disabled={saving}>
                {saving ? '…' : t('save')}
            </button>
            <p className={'theme-status'} role={'status'} aria-live={'polite'}>
                {status}
            </p>
        </form>
    );
};
