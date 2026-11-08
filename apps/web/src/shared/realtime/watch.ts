import { fetchRealtimeTicket } from './ticket.ts';

const SUBPROTOCOL = 'aws-appsync-event-ws';
const FIRST_RETRY_MS = 1000;
const LONGEST_RETRY_MS = 30_000;

export interface RealtimeWatch {
  /** A notification arrived: the channel it came on and its payload. Treat it as a hint to refetch. */
  onNotification: (channel: string, payload: unknown) => void;
  /** The connection was lost and is subscribed again, so notifications may have been missed. */
  onResync: () => void;
}

interface ProtocolMessage {
  type: string;
  id?: string;
  event?: string;
}

function toBase64Url(value: string): string {
  return btoa(value).replace(/\+/g, '-').replace(/\//g, '_').split('=')[0] ?? '';
}

export function watchRealtime({ onNotification, onResync }: RealtimeWatch): () => void {
  let stopped = false;
  let socket: WebSocket | undefined;
  let timer: ReturnType<typeof setTimeout> | undefined;
  let delay = FIRST_RETRY_MS;
  let connections = 0;

  const retry = () => {
    if (stopped) {
      return;
    }

    timer = setTimeout(() => void connect(), delay);
    delay = Math.min(delay * 2, LONGEST_RETRY_MS);
  };

  const connect = async () => {
    try {
      const { ticket, endpoint, channels } = await fetchRealtimeTicket();
      if (stopped) {
        return;
      }

      const attempt = (connections += 1);
      const host = new URL(endpoint).host;
      const connection = new WebSocket(endpoint, [
        SUBPROTOCOL,
        `header-${toBase64Url(JSON.stringify({ Authorization: ticket, host }))}`,
      ]);
      socket = connection;

      const pending = new Set<string>();
      const subscriptions = new Map<string, string>();

      connection.onopen = () => {
        connection.send(JSON.stringify({ type: 'connection_init' }));
      };

      connection.onmessage = (event: MessageEvent<string>) => {
        const message = JSON.parse(event.data) as ProtocolMessage;

        if (message.type === 'connection_ack') {
          // The connect ticket may be nearly spent by now; subscribing is authorised on its own.
          void (async () => {
            for (const channel of channels) {
              const { ticket: subscribeTicket } = await fetchRealtimeTicket();
              const id = crypto.randomUUID();
              pending.add(id);
              subscriptions.set(id, channel);
              connection.send(
                JSON.stringify({
                  type: 'subscribe',
                  id,
                  channel,
                  authorization: { Authorization: subscribeTicket, host },
                }),
              );
            }
          })().catch(() => {
            connection.close();
          });
        } else if (message.type === 'subscribe_success') {
          delay = FIRST_RETRY_MS;
          if (attempt > 1 && pending.delete(message.id ?? '')) {
            onResync();
          }
        } else if (
          message.type === 'data' &&
          message.id !== undefined &&
          message.event !== undefined
        ) {
          const channel = subscriptions.get(message.id);
          if (channel !== undefined) {
            onNotification(channel, JSON.parse(message.event));
          }
        }
      };

      connection.onclose = () => {
        socket = undefined;
        retry();
      };
    } catch {
      retry();
    }
  };

  void connect();

  return () => {
    stopped = true;
    clearTimeout(timer);
    const connection = socket;
    socket = undefined;
    if (connection !== undefined) {
      connection.onclose = null;
      connection.close();
    }
  };
}
