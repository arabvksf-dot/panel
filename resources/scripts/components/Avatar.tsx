import React, { useMemo } from 'react';
import BoringAvatar, { AvatarProps } from 'boring-avatars';
import { useStoreState } from '@/state/hooks';

const useThemePalette = () => {
    const appearance = useStoreState((state) => state.user.data?.appearance);

    return useMemo(() => {
        const styles = getComputedStyle(document.documentElement);
        return [1, 2, 3, 4, 5].map((index) => styles.getPropertyValue(`--color-avatar-${index}`).trim());
    }, [appearance?.theme, appearance?.accent]);
};

type Props = Omit<AvatarProps, 'colors'>;

const _Avatar = ({ variant = 'beam', ...props }: AvatarProps) => {
    const palette = useThemePalette();

    return <BoringAvatar colors={palette} variant={variant} {...props} />;
};

const _UserAvatar = ({ variant = 'beam', ...props }: Omit<Props, 'name'>) => {
    const uuid = useStoreState((state) => state.user.data?.uuid);
    const palette = useThemePalette();

    return <BoringAvatar colors={palette} name={uuid || 'system'} variant={variant} {...props} />;
};

_Avatar.displayName = 'Avatar';
_UserAvatar.displayName = 'Avatar.User';

const Avatar = Object.assign(_Avatar, {
    User: _UserAvatar,
});

export default Avatar;
