import http from '@/api/http';

export interface HostingStatus {
    has_active_hosting: boolean;
}

export default (): Promise<HostingStatus> =>
    http.get('/api/client/account/hosting-status').then((response) => response.data);
