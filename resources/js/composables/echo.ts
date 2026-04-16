import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Echo: Echo;
    Pusher: typeof Pusher;
  }
}

// Singleton instance — created once, shared by all composables
let echoInstance: Echo | null = null;

function createEcho(): Echo {
  window.Pusher = Pusher;

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'app-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
  });

  window.Echo = echoInstance;
  return echoInstance;
}

/**
 * Get the shared Echo instance. Creates it lazily on first call.
 */
export function getEcho(): Echo {
  if (!echoInstance) {
    createEcho();
  }
  return echoInstance!;
}

/**
 * Disconnect and destroy the Echo instance.
 */
export function destroyEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
    window.Echo = null as any;
  }
}

export default getEcho;
