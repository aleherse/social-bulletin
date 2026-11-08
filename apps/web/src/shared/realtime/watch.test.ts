import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { fetchRealtimeTicket } from './ticket.ts';
import { watchRealtime } from './watch.ts';

vi.mock('./ticket.ts', () => ({ fetchRealtimeTicket: vi.fn() }));

const ENDPOINT = 'wss://dev.realtime.social.aleherse.com/event/realtime';
const CHANNEL = '/users/u-1/movements';

class FakeSocket {
  static readonly instances: FakeSocket[] = [];

  readonly sent: Record<string, unknown>[] = [];
  closed = false;
  onopen: (() => void) | null = null;
  onmessage: ((event: { data: string }) => void) | null = null;
  onclose: (() => void) | null = null;

  readonly url: string;
  readonly protocols: string[];

  constructor(url: string, protocols: string[]) {
    this.url = url;
    this.protocols = protocols;
    FakeSocket.instances.push(this);
  }

  send(data: string) {
    this.sent.push(JSON.parse(data) as Record<string, unknown>);
  }

  close() {
    this.closed = true;
    this.onclose?.();
  }

  open() {
    this.onopen?.();
  }

  receive(message: Record<string, unknown>) {
    this.onmessage?.({ data: JSON.stringify(message) });
  }

  drop() {
    this.onclose?.();
  }
}

let counter = 0;

function issueTickets() {
  vi.mocked(fetchRealtimeTicket).mockImplementation(() => {
    counter += 1;

    return Promise.resolve({
      id: `t-${String(counter)}`,
      ticket: `ticket-${String(counter)}`,
      expiresAt: '2026-11-08T10:01:00+00:00',
      endpoint: ENDPOINT,
      channels: [CHANNEL],
    });
  });
}

const latest = () => FakeSocket.instances[FakeSocket.instances.length - 1] as FakeSocket;

async function connected(): Promise<FakeSocket> {
  await vi.advanceTimersByTimeAsync(0);
  const socket = latest();
  socket.open();
  socket.receive({ type: 'connection_ack', connectionTimeoutMs: 300000 });
  await vi.advanceTimersByTimeAsync(0);

  return socket;
}

describe('watchRealtime', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    counter = 0;
    FakeSocket.instances.length = 0;
    vi.stubGlobal('WebSocket', FakeSocket);
    issueTickets();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    vi.resetAllMocks();
  });

  it('connects with the ticket as an AppSync subprotocol and starts the protocol', async () => {
    watchRealtime({ onNotification: vi.fn(), onResync: vi.fn() });
    await vi.advanceTimersByTimeAsync(0);

    const socket = latest();
    expect(socket.url).toBe(ENDPOINT);
    expect(socket.protocols[0]).toBe('aws-appsync-event-ws');
    expect(
      JSON.parse(
        atob(
          socket.protocols[1]?.replace('header-', '').replace(/-/g, '+').replace(/_/g, '/') ?? '',
        ),
      ),
    ).toEqual({ Authorization: 'ticket-1', host: 'dev.realtime.social.aleherse.com' });

    socket.open();
    expect(socket.sent).toEqual([{ type: 'connection_init' }]);
  });

  it('subscribes to every channel of the ticket with a ticket of its own', async () => {
    watchRealtime({ onNotification: vi.fn(), onResync: vi.fn() });
    const socket = await connected();

    expect(socket.sent[1]).toMatchObject({
      type: 'subscribe',
      channel: CHANNEL,
      authorization: { Authorization: 'ticket-2' },
    });
  });

  it('hands over the notifications it receives, parsed', async () => {
    const onNotification = vi.fn();
    watchRealtime({ onNotification, onResync: vi.fn() });
    const socket = await connected();
    const id = (socket.sent[1] as { id: string }).id;

    socket.receive({ type: 'subscribe_success', id });
    socket.receive({ type: 'data', id, event: '{"change":"created","movementId":"m-1"}' });
    socket.receive({ type: 'ka' });

    expect(onNotification).toHaveBeenCalledOnce();
    expect(onNotification).toHaveBeenCalledWith(CHANNEL, { change: 'created', movementId: 'm-1' });
  });

  it('asks for a resync only once it is subscribed again after losing the connection', async () => {
    const onResync = vi.fn();
    watchRealtime({ onNotification: vi.fn(), onResync });
    const first = await connected();
    first.receive({ type: 'subscribe_success', id: (first.sent[1] as { id: string }).id });
    expect(onResync).not.toHaveBeenCalled();

    first.drop();
    await vi.advanceTimersByTimeAsync(1000);
    const second = await connected();
    expect(onResync).not.toHaveBeenCalled();
    second.receive({ type: 'subscribe_success', id: (second.sent[1] as { id: string }).id });

    expect(FakeSocket.instances).toHaveLength(2);
    expect(onResync).toHaveBeenCalledOnce();
    expect(second.sent[1]).toMatchObject({ authorization: { Authorization: 'ticket-4' } });
  });

  it('keeps retrying, backing off, while no ticket can be issued', async () => {
    vi.mocked(fetchRealtimeTicket).mockRejectedValue(new Error('offline'));
    watchRealtime({ onNotification: vi.fn(), onResync: vi.fn() });

    await vi.advanceTimersByTimeAsync(0);
    expect(fetchRealtimeTicket).toHaveBeenCalledTimes(1);
    await vi.advanceTimersByTimeAsync(1000);
    expect(fetchRealtimeTicket).toHaveBeenCalledTimes(2);
    await vi.advanceTimersByTimeAsync(1999);
    expect(fetchRealtimeTicket).toHaveBeenCalledTimes(2);
    await vi.advanceTimersByTimeAsync(1);
    expect(fetchRealtimeTicket).toHaveBeenCalledTimes(3);
  });

  it('closes the socket and stops reconnecting once stopped', async () => {
    const stop = watchRealtime({ onNotification: vi.fn(), onResync: vi.fn() });
    const socket = await connected();

    stop();

    expect(socket.closed).toBe(true);
    await vi.advanceTimersByTimeAsync(60_000);
    expect(FakeSocket.instances).toHaveLength(1);
  });

  it('does not connect at all when stopped before the ticket arrives', async () => {
    const stop = watchRealtime({ onNotification: vi.fn(), onResync: vi.fn() });
    stop();
    await vi.advanceTimersByTimeAsync(60_000);

    expect(FakeSocket.instances).toHaveLength(0);
  });
});
