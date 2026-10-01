import http from '@/api/http';

export default (password: string): Promise<void> => http.delete('/api/client/account', { data: { password } });
