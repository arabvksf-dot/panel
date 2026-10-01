import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';
import { SocketEvent } from '@/components/server/events';

const TransferListener = () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const getServer = ServerContext.useStoreActions((actions) => actions.server.getServer);
    const setServerFromState = ServerContext.useStoreActions((actions) => actions.server.setServerFromState);

     
    useWebsocketEvent(SocketEvent.TRANSFER_STATUS, (status: string) => {
        if (status === 'pending' || status === 'processing') {
            setServerFromState((s) => ({ ...s, isTransferring: true }));
            return;
        }

        if (status === 'failed') {
            setServerFromState((s) => ({ ...s, isTransferring: false }));
            return;
        }

        if (status !== 'completed') {
            return;
        }

         
        getServer(uuid).catch((error) => console.error(error));
    });

    return null;
};

export default TransferListener;
