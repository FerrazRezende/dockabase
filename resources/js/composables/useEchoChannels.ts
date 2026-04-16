import { ref } from 'vue';
import { getEcho, destroyEcho } from './echo';
import type { UserStatusChangedEvent } from '../types/user-status';

export type StatusUpdateCallback = (event: UserStatusChangedEvent) => void;

export type ConnectionState = 'connecting' | 'connected' | 'disconnected' | 'error';

interface ChannelCallbacks {
  onStatusUpdated?: StatusUpdateCallback;
  onError?: (error: Error) => void;
}

/**
 * Echo Channels Composable
 *
 * Manages Laravel Echo/Reverb WebSocket connections for user status updates.
 * Uses the shared Echo singleton from echo.ts.
 */
export function useEchoChannels() {
  const isConnected = ref<boolean>(false);
  const connectionState = ref<ConnectionState>('disconnected');
  const connectionError = ref<Error | null>(null);

  const activeChannels = new Map<string, ReturnType<ReturnType<typeof getEcho>['private']>>();
  const activeListeners = new Map<string, string[]>();

  /**
   * Connect to Echo with proper authentication
   */
  async function connect(): Promise<void> {
    if (isConnected.value) {
      return;
    }

    connectionState.value = 'connecting';
    connectionError.value = null;

    try {
      const echo = getEcho();

      // Set up connection state monitoring
      echo.connector.pusher.connection.bind('connected', () => {
        isConnected.value = true;
        connectionState.value = 'connected';
        connectionError.value = null;
      });

      echo.connector.pusher.connection.bind('disconnected', () => {
        isConnected.value = false;
        connectionState.value = 'disconnected';
      });

      echo.connector.pusher.connection.bind('connecting', () => {
        connectionState.value = 'connecting';
      });

      echo.connector.pusher.connection.bind('error', (error: unknown) => {
        connectionState.value = 'error';
        connectionError.value = error instanceof Error ? error : new Error('Connection error');
        isConnected.value = false;
      });

      // Wait for connection to be established
      return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          reject(new Error('Connection timeout'));
        }, 10000);

        echo.connector.pusher.connection.bind('connected', () => {
          clearTimeout(timeout);
          resolve();
        });

        echo.connector.pusher.connection.bind('error', (error: unknown) => {
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
    activeChannels.clear();
    activeListeners.clear();
    destroyEcho();
    isConnected.value = false;
    connectionState.value = 'disconnected';
  }

  /**
   * Listen to private user channel for status updates
   */
  function listenToUserChannel(userId: string | number, callbacks: ChannelCallbacks): (() => void) | null {
    const channelName = `private-users.${userId}`;

    if (activeChannels.has(channelName)) {
      return null;
    }

    try {
      const echo = getEcho();
      const channel = echo.private(channelName);

      activeListeners.set(channelName, []);

      if (callbacks.onStatusUpdated) {
        channel.listen('.UserStatusChanged', callbacks.onStatusUpdated);
        activeListeners.get(channelName)?.push('.UserStatusChanged');
      }

      activeChannels.set(channelName, channel);

      return () => {
        try {
          echo.leave(channelName);
          activeChannels.delete(channelName);
          activeListeners.delete(channelName);
        } catch (error) {
          console.error('[useEchoChannels] Error unsubscribing:', error);
        }
      };
    } catch (error) {
      console.error('[useEchoChannels] Error subscribing to channel:', error);
      return null;
    }
  }

  /**
   * Convenience method that wraps listenToUserChannel
   */
  function listenToStatusUpdates(userId: string | number, callback: StatusUpdateCallback): (() => void) | null {
    return listenToUserChannel(userId, {
      onStatusUpdated: callback,
    });
  }

  /**
   * Leave a specific channel
   */
  function leaveUserChannel(userId: string | number): void {
    const channelName = `private-users.${userId}`;

    if (activeChannels.has(channelName)) {
      try {
        getEcho().leave(channelName);
        activeChannels.delete(channelName);
        activeListeners.delete(channelName);
      } catch (error) {
        console.error('[useEchoChannels] Error leaving channel:', error);
      }
    }
  }

  /**
   * Check if currently subscribed to a user's channel
   */
  function isSubscribedToUser(userId: string | number): boolean {
    return activeChannels.has(`private-users.${userId}`);
  }

  /**
   * Get list of actively subscribed channel names
   */
  function getActiveChannels(): string[] {
    return Array.from(activeChannels.keys());
  }

  /**
   * Reconnect to Echo with existing channels
   */
  async function reconnect(): Promise<void> {
    disconnect();
    await connect();
  }

  /**
   * Listen to the global presence channel for status updates from any user.
   */
  function listenToPresenceChannel(callback: StatusUpdateCallback): (() => void) | null {
    const channelName = 'presence';

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
    isConnected,
    connectionState,
    connectionError,
    connect,
    disconnect,
    reconnect,
    listenToUserChannel,
    listenToStatusUpdates,
    listenToPresenceChannel,
    leaveUserChannel,
    isSubscribedToUser,
    getActiveChannels,
  };
}
