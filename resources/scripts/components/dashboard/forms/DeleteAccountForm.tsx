import React, { FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';
import deleteAccount from '@/api/account/deleteAccount';
import { httpErrorToHuman } from '@/api/http';

export default () => {
    const { t } = useTranslation('appearance');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!window.confirm(t('account_delete_confirm'))) return;

        setSubmitting(true);
        setError('');
        deleteAccount(password)
            .then(() => {
                window.location.assign('/auth/login?account=deleted');
            })
            .catch((requestError) => setError(httpErrorToHuman(requestError)))
            .finally(() => setSubmitting(false));
    };

    return (
        <form onSubmit={submit} style={{ display: 'grid', gap: '1rem' }}>
            <p>{t('account_delete_warning')}</p>
            <label className={'theme-field'}>
                {t('account_delete_password')}
                <input
                    className={'theme-select'}
                    type={'password'}
                    name={'delete-account-password'}
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                    required
                    autoComplete={'current-password'}
                    disabled={submitting}
                />
            </label>
            {error && <p role={'alert'}>{error}</p>}
            <button className={'theme-action theme-action-danger'} type={'submit'} disabled={submitting || !password}>
                {submitting ? '…' : t('account_delete_action')}
            </button>
        </form>
    );
};
