import { apiResource } from '@/shared/api';

export interface RealtimeTicket {
  id: string;
  ticket: string;
  expiresAt: string;
  endpoint: string;
  channels: string[];
}

/** ADR-0024: a ticket lasts a minute, so every connect and every subscribe asks for its own. */
export function fetchRealtimeTicket(): Promise<RealtimeTicket> {
  return apiResource<RealtimeTicket>('/api/realtime-tickets', { method: 'POST' });
}
