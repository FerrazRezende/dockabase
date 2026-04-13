import { getEcho } from './echo';

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

const activeChannels = new Set<string>();

export function useEcho() {
    const subscribeToDatabase = (databaseId: string, callbacks: DatabaseCallbacks) => {
        const channelName = `database.${databaseId}`;

        if (activeChannels.has(channelName)) {
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

            return {
                unsubscribe: () => {
                    echo.leave(channelName);
                    activeChannels.delete(channelName);
                },
            };
        } catch (error) {
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
            return null;
        }
    };

    return {
        subscribeToDatabase,
        subscribeToNotifications,
    };
}
