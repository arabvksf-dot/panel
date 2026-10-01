import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import getHostingStatus from '@/api/account/getHostingStatus';
import { httpErrorToHuman } from '@/api/http';

export default () => {
    const { t } = useTranslation('appearance');
    const [hasHosting, setHasHosting] = useState<boolean | null>(null);
    const [error, setError] = useState('');
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        let cancelled = false;
        setError('');

        getHostingStatus()
            .then((status) => {
                if (!cancelled) setHasHosting(status.has_active_hosting);
            })
            .catch((requestError) => {
                if (!cancelled) setError(httpErrorToHuman(requestError));
            });

        return () => {
            cancelled = true;
        };
    }, [attempt]);

    if (hasHosting === null && !error) {
        return <div className={'status-skeleton'} role={'status'} aria-label={t('hosting_loading')} />;
    }

    if (error) {
        return (
            <div role={'alert'}>
                <p>{t('hosting_error')}</p>
                <button className={'theme-action'} type={'button'} onClick={() => setAttempt(attempt + 1)}>
                    {t('retry')}
                </button>
            </div>
        );
    }

    return (
        <p role={'status'} aria-live={'polite'}>
            {hasHosting ? t('hosting_active') : t('hosting_none')}
        </p>
    );
};
