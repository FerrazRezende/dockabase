import { ref } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { UserStatusChangedEvent } from '../types/user-status';

declare global {
  interface Window {
    Echo: Echo;
    Pusher: typeof Pusher;
  }
}

/**
 * User status update callback
 */
export type StatusUpdateCallback = (event: UserStatusChangedEvent) => void;

/**
 * Connection state of Echo
 */
export type ConnectionState = 'connecting' | 'connected' | 'disconnected' | 'error';

/**
 * Channel subscription callbacks
 */
interface ChannelCallbacks {
  onStatusUpdated?: StatusUpdateCallback;
  onError?: (error: Error) => void;
}

/**
 * Echo Channels Composable
 *
 * Manages Laravel Echo/Reverb WebSocket connections for user status updates.
 * Provides connection management, channel subscription, and automatic cleanup.
 *
 * @example
 * ```vue
 * <script setup lang="ts">
 * import { useEchoChannels } from '@/composables/useEchoChannels';
 *
 * const { connect, listenToUserChannel, isConnected } = useEchoChannels();
 *
 * onMounted(async () => {
 *   await connect();
 *   listenToUserChannel('user-123', (event) => {
 *     console.log(`User ${event.user_id} is now ${event.status}`);
 *   });
 * });
 * </script>
 * ```
 */
export function useEchoChannels() {
  const isConnected = ref<boolean>(false);
  const connectionState = ref<ConnectionState>('disconnected');
  const connectionError = ref<Error | null>(null);

  // Lazy Echo instance initialization
  let echoInstance: Echo | null = null;
  const activeChannels = new Map<string, ReturnType<Echo['private']>>();
  const activeListeners = new Map<string, string[]>();

  /**
   * Get or create Echo instance
   */
  function getEcho(): Echo {
    if (!echoInstance) {
      try {
        window.Pusher = Pusher;
        echoInstance = new Echo({
          broadcaster: 'reverb',
          key: import.meta.env.VITE_REVERB_APP_KEY || 'app-key',
          wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
          wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
          wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
          forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
          enabledTransports: ['ws', 'wss'],
          authorizer: (channel: any) => {
            return {
              authorize: (socketId: string, callback: Function) => {
                fetch('/broadcasting/auth', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Socket-ID': socketId,
                    'Accept': 'application/json',
                  },
                  body: JSON.stringify({
                    socket_id: socketId,
                    channel_name: channel.name,
                  }),
                })
                  .then((response) => response.json())
                  .then((data) => {
                    callback(null, data);
                  })
                  .catch((error) => {
                    callback(error, null);
                  });
              },
            };
          },
        });

        // Set up connection state monitoring
        echoInstance.connector.pusher.connection.bind('connected', () => {
          isConnected.value = true;
          connectionState.value = 'connected';
          connectionError.value = null;
        });

        echoInstance.connector.pusher.connection.bind('disconnected', () => {
          isConnected.value = false;
          connectionState.value = 'disconnected';
        });

        echoInstance.connector.pusher.connection.bind('connecting', () => {
          connectionState.value = 'connecting';
        });

        echoInstance.connector.pusher.connection.bind('error', (error: any) => {
          connectionState.value = 'error';
          connectionError.value = error instanceof Error ? error : new Error('Connection error');
          isConnected.value = false;
        });
      } catch (error) {
        connectionState.value = 'error';
        connectionError.value = error instanceof Error ? error : new Error('Failed to initialize Echo');
        isConnected.value = false;
        throw error;
      }
    }

    return echoInstance;
  }

  /**
   * Connect to Echo with proper authentication
   * @returns Promise that resolves when connected or rejects on error
   */
  async function connect(): Promise<void> {
    if (isConnected.value) {
      return;
    }

    connectionState.value = 'connecting';
    connectionError.value = null;

    try {
      const echo = getEcho();

      // Wait for connection to be established
      return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          reject(new Error('Connection timeout'));
        }, 10000); // 10 second timeout

        echo.connector.pusher.connection.bind('connected', () => {
          clearTimeout(timeout);
          resolve();
        });

        echo.connector.pusher.connection.bind('error', (error: any) => {
          clearTimeout(timeout);
          reject(error);
        });
      });
    } catch (error) {
      connectionState.value = 'error';
      connectionError.value = error instanceof Error ? error : new Error('Connection failed');
      isConnected.value = false;
      throw error;
    }
  }

  /**
   * Disconnect from Echo and cleanup all channels
   */
  function disconnect(): void {
    if (echoInstance) {
      // Unsubscribe from all channels
      activeChannels.forEach((channel, channelName) => {
        echoInstance!.leave(channelName);
      });

      activeChannels.clear();
      activeListeners.clear();

      // Disconnect Echo
      echoInstance.disconnect();
      echoInstance = null;
      isConnected.value = false;
      connectionState.value = 'disconnected';
    }
  }

  /**
   * Listen to private user channel for status updates
   * @param userId - User ID to listen for status updates
   * @param callbacks - Callback functions for events
   * @returns Unsubscribe function
   */
  function listenToUserChannel(userId: string | number, callbacks: ChannelCallbacks): (() => void) | null {
    const channelName = `private-users.${userId}`;

    // Avoid duplicate subscriptions
    if (activeChannels.has(channelName)) {
      console.warn(`[useEchoChannels] Already subscribed to ${channelName}`);
      return null;
    }

    try {
      const echo = getEcho();
      const channel = echo.private(channelName);

      // Track listeners for cleanup
      activeListeners.set(channelName, []);

      // Listen for status updated events
      if (callbacks.onStatusUpdated) {
        channel.listen('.UserStatusChanged', callbacks.onStatusUpdated);
        activeListeners.get(channelName)?.push('.UserStatusChanged');
      }

      // Listen for connection errors
      if (callbacks.onError) {
        channel.listen_for_whisper('error', (error: any) => {
          callbacks.onError!(error instanceof Error ? error : new Error(error.message || 'Channel error'));
        });
      }

      activeChannels.set(channelName, channel);

      // Return unsubscribe function
      return () => {
        try {
          // Use window.Echo directly to avoid closure capturing Echo instance
          if (window.Echo) {
            window.Echo.leave(channelName);
            activeChannels.delete(channelName);
            activeListeners.delete(channelName);
          }
        } catch (error) {
          console.error('[useEchoChannels] Error unsubscribing:', error);
        }
      };
    } catch (error) {
      console.error('[useEchoChannels] Error subscribing to channel:', error);
      if (callbacks.onError) {
        callbacks.onError(error instanceof Error ? error : new Error('Failed to subscribe to channel'));
      }
      return null;
    }
  }

  /**
   * Listen to status updates for a specific user
   * Convenience method that wraps listenToUserChannel
   * @param userId - User ID to listen for
   * @param callback - Status update callback
   * @returns Unsubscribe function or null
   */
  function listenToStatusUpdates(userId: string | number, callback: StatusUpdateCallback): (() => void) | null {
    return listenToUserChannel(userId, {
      onStatusUpdated: callback,
    });
  }

  /**
   * Leave a specific channel
   * @param userId - User ID for the channel to leave
   */
  function leaveUserChannel(userId: string | number): void {
    const channelName = `private-users.${userId}`;

    if (echoInstance && activeChannels.has(channelName)) {
      try {
        echoInstance.leave(channelName);
        activeChannels.delete(channelName);
        activeListeners.delete(channelName);
      } catch (error) {
        console.error('[useEchoChannels] Error leaving channel:', error);
      }
    }
  }

  /**
   * Check if currently subscribed to a user's channel
   * @param userId - User ID to check
   * @returns True if subscribed
   */
  function isSubscribedToUser(userId: string | number): boolean {
    const channelName = `private-users.${userId}`;
    return activeChannels.has(channelName);
  }

  /**
   * Get list of actively subscribed channel names
   * @returns Array of channel names
   */
  function getActiveChannels(): string[] {
    return Array.from(activeChannels.keys());
  }

  /**
   * Reconnect to Echo with existing channels
   * Useful for handling temporary disconnections
   */
  async function reconnect(): Promise<void> {
    disconnect();
    await connect();
  }

  /**
   * Listen to the global presence channel for status updates from any user.
   * @param callback - Called with the status update event data
   * @returns Unsubscribe function
   */
  function listenToPresenceChannel(callback: StatusUpdateCallback): (() => void) | null {
    const channelName = 'private-presence';

    if (activeChannels.has(channelName)) {
      return null;
    }

    try {
      const echo = getEcho();
      const channel = echo.private(channelName);

      channel.listen('.status.updated', callback);

      activeChannels.set(channelName, channel);
      activeListeners.set(channelName, ['.status.updated']);

      return () => {
        try {
          echo.leave(channelName);
          activeChannels.delete(channelName);
          activeListeners.delete(channelName);
        } catch (error) {
          console.error('[useEchoChannels] Error leaving presence channel:', error);
        }
      };
    } catch (error) {
      console.error('[useEchoChannels] Error subscribing to presence channel:', error);
      return null;
    }
  }

  return {
    // State
    isConnected,
    connectionState,
    connectionError,

    // Connection methods
    connect,
    disconnect,
    reconnect,

    // Channel methods
    listenToUserChannel,
    listenToStatusUpdates,
    listenToPresenceChannel,
    leaveUserChannel,
    isSubscribedToUser,
    getActiveChannels,
  };
}
