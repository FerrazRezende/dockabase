import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { UserStatus, UserStatusWithMeta } from '../types/user-status';
import { __ } from './useLang';

/**
 * User Status Composable
 *
 * Provides reactive user status management with API integration.
 * Handles status updates, loading states, and translations.
 */
export function useUserStatus() {
  const currentStatus = ref<UserStatus>('online');
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  /**
   * Get translated label for the current status
   */
  const statusLabel = computed(() => {
    return __('user_status.' + currentStatus.value);
  });

  /**
   * Get CSS color class for the current status
   */
  const statusColor = computed(() => {
    const colors: Record<UserStatus, string> = {
      online: 'text-green-500',
      away: 'text-yellow-500',
      busy: 'text-red-500',
      offline: 'text-gray-400',
    };
    return colors[currentStatus.value] || colors.online;
  });

  /**
   * Get background color class for the current status
   */
  const statusBgColor = computed(() => {
    const colors: Record<UserStatus, string> = {
      online: 'bg-green-500',
      away: 'bg-yellow-500',
      busy: 'bg-red-500',
      offline: 'bg-gray-400',
    };
    return colors[currentStatus.value] || colors.online;
  });

  /**
   * Set user status with optional message
   * @param status - The status to set
   * @param message - Optional status message
   */
  const setStatus = async (status: UserStatus, message?: string): Promise<boolean> => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await fetch('/api/user/status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ status, message }),
      });

      if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || __('Failed to update status'));
      }

      const data = await response.json();
      currentStatus.value = data.status || status;
      return true;
    } catch (err) {
      error.value = err instanceof Error ? err.message : __('An error occurred');
      return false;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Clear user status (set to online)
   */
  const clearStatus = async (): Promise<boolean> => {
    return setStatus('online');
  };

  /**
   * Fetch current status from API
   */
  const refreshStatus = async (): Promise<void> => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await fetch('/api/user/status', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(__('Failed to fetch status'));
      }

      const data: UserStatusWithMeta = await response.json();
      currentStatus.value = data.status;
    } catch (err) {
      error.value = err instanceof Error ? err.message : __('An error occurred');
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Initialize status from page props if available
   */
  const initializeStatus = (initialStatus?: UserStatus) => {
    if (initialStatus) {
      currentStatus.value = initialStatus;
    }
  };

  return {
    // State
    currentStatus,
    isLoading,
    error,

    // Computed
    statusLabel,
    statusColor,
    statusBgColor,

    // Methods
    setStatus,
    clearStatus,
    refreshStatus,
    initializeStatus,
  };
}
