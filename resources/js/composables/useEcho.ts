import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Echo: Echo;
    Pusher: typeof Pusher;
  }
}

interface DatabaseStepUpdated {
    database: {
        id: string;
        [key: string]: unknown;
    };
    step: string;
    progress: number;
}

interface DatabaseCreated {
    database: {
        id: string;
        [key: string]: unknown;
    };
}

interface DatabaseFailed {
    database: {
        id: string;
        [key: string]: unknown;
    };
    status: string;
    error: string;
}

interface DatabaseCallbacks {
    onStepUpdated?: (data: DatabaseStepUpdated) => void;
    onDatabaseCreated?: (data: DatabaseCreated) => void;
    onDatabaseFailed?: (data: DatabaseFailed) => void;
}

// Initialize Echo lazily
let echoInstance: Echo | null = null;

function getEcho(): Echo {
    if (!echoInstance) {
        window.Pusher = Pusher;
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY || 'app-key',
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
            wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
        window.Echo = echoInstance;
    }
    return echoInstance;
}

const activeChannels = new Set<string>();

export function useEcho() {
    const subscribeToDatabase = (databaseId: string, callbacks: DatabaseCallbacks) => {
        const channelName = `database.${databaseId}`;

        // Avoid duplicate subscriptions
        if (activeChannels.has(channelName)) {
            console.log(`Already subscribed to ${channelName}`);
            return null;
        }

        try {
            const echo = getEcho();
            const channel = echo.private(channelName);

            if (callbacks.onStepUpdated) {
                channel.listen('.step.updated', callbacks.onStepUpdated);
            }

            if (callbacks.onDatabaseCreated) {
                channel.listen('.database.created', callbacks.onDatabaseCreated);
            }

            if (callbacks.onDatabaseFailed) {
                channel.listen('.database.failed', callbacks.onDatabaseFailed);
            }

            activeChannels.add(channelName);

            console.log(`Subscribed to ${channelName}`);

            return {
                unsubscribe: () => {
                    echo.leave(channelName);
                    activeChannels.delete(channelName);
                    console.log(`Unsubscribed from ${channelName}`);
                },
            };
        } catch (error) {
            console.error('Failed to subscribe to database channel:', error);
            return null;
        }
    };

    const subscribeToNotifications = (userId: number | string, callbacks: {
        onNotification?: (data: unknown) => void;
    }) => {
        const channelName = `App.Models.User.${userId}`;

        try {
            const echo = getEcho();
            const channel = echo.private(channelName);

            if (callbacks.onNotification) {
                channel.notification(callbacks.onNotification);
            }

            return {
                unsubscribe: () => {
                    echo.leave(channelName);
                },
            };
        } catch (error) {
            console.error('Failed to subscribe to notifications:', error);
            return null;
        }
    };

    return {
        subscribeToDatabase,
        subscribeToNotifications,
    };
}
