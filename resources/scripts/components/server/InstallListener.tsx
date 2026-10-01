import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import { mutate } from 'swr';
import { getDirectorySwrKey } from '@/plugins/useFileManagerSwr';

const InstallListener = () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const getServer = ServerContext.useStoreActions((actions) => actions.server.getServer);
    const setServerFromState = ServerContext.useStoreActions((actions) => actions.server.setServerFromState);

    useWebsocketEvent(SocketEvent.BACKUP_RESTORE_COMPLETED, () => {
        mutate(getDirectorySwrKey(uuid, '/'), undefined);
        setServerFromState((s) => ({ ...s, status: null }));
    });

     
     
     
    useWebsocketEvent(SocketEvent.INSTALL_COMPLETED, () => {
        getServer(uuid).catch((error) => console.error(error));
    });

     
     
    useWebsocketEvent(SocketEvent.INSTALL_STARTED, () => {
        setServerFromState((s) => ({ ...s, status: 'installing' }));
    });

    return null;
};

export default InstallListener;
