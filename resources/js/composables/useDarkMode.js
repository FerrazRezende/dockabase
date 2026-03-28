import { ref, watch } from 'vue';

const isDark = ref(false);

// Initialize immediately
if (typeof window !== 'undefined') {
    const stored = localStorage.getItem('theme');
    if (stored === 'dark') {
        isDark.value = true;
    } else if (stored === 'light') {
        isDark.value = false;
    } else {
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    // Apply initial state
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Watch for changes
watch(isDark, (value) => {
    if (value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
});

export function useDarkMode() {
    const toggleDark = () => {
        isDark.value = !isDark.value;
    };

    return {
        isDark,
        toggleDark,
    };
}
