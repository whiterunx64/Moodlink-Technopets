import { supabase } from '@/utils/supabase';
import type { RealtimeChannel, RealtimePostgresChangesPayload } from '@supabase/supabase-js';

type RealtimeEvent = 'INSERT' | 'UPDATE' | 'DELETE' | '*';
type ChangeCallback<T extends Record<string, unknown> = Record<string, unknown>> =
  (payload: RealtimePostgresChangesPayload<T>) => void;

let channelCounter = 0;
// Manages all active realtime channels for cleanup tracking
export function useRealtime() {
  const channels = new Map<string, RealtimeChannel>();

  // Creates a realtime subscription to a database table
  function subscribe<T extends Record<string, unknown>>(
    table: string,
    callback: ChangeCallback<T>,
    event: RealtimeEvent = '*',
  ): () => void {
    const key = `${table}:${++channelCounter}`;

    const channel = supabase
      .channel(key)
      .on('postgres_changes', { event, schema: 'public', table }, callback)
      .subscribe((status) => {
        if (status === 'CHANNEL_ERROR') {
          console.error(`[useRealtime] Live updates unavailable for "${table}". ` +
            `You may need to refresh the page or check Supabase Realtime for this table.`);
        }
      });

    channels.set(key, channel);

    return () => {
      supabase.removeChannel(channel);
      channels.delete(key);
    };
  }

  // Unsubscribes all active realtime subscriptions
  function unsubscribeAll() {
    channels.forEach((channel) => supabase.removeChannel(channel));
    channels.clear();
  }

  return { subscribe, unsubscribeAll };
}
