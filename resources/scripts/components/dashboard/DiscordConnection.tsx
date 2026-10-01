import React from 'react';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { useTranslation } from 'react-i18next';
import { useLocation } from 'react-router-dom';

export default () => {
    const user = useStoreState((state: ApplicationStore) => state.user.data);
    const enabled = useStoreState((state: ApplicationStore) => state.settings.data!.discordOAuthEnabled);
    const { t } = useTranslation('appearance');
    const { search } = useLocation();
    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const status = new URLSearchParams(search).get('discord');

    React.useEffect(() => {
        if (status === 'linked') {
            updateUserData({ discordLinked: true });
        }
    }, [status]);

    return (
        <div style={{ display: 'grid', gap: '0.75rem' }}>
            <p role={status === 'already-linked' || status === 'link-session-expired' ? 'alert' : 'status'}>
                {status === 'link-session-expired'
                    ? t('discord_link_expired')
                    : status === 'already-linked'
                    ? t('discord_already_linked')
                    : user!.discordLinked || status === 'linked'
                    ? t('discord_connected')
                    : t('discord_not_connected')}
            </p>
            {enabled && !user!.discordLinked && (
                <form method={'post'} action={'/account/discord/link'}>
                    <input
                        type={'hidden'}
                        name={'_token'}
                        value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || ''}
                    />
                    <button className={'theme-action'} type={'submit'} style={{ width: '100%' }}>
                        {t('discord_connect')}
                    </button>
                </form>
            )}
        </div>
    );
};
