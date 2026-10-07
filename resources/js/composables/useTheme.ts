import { ref, computed } from 'vue';

export type ThemeMode = 'light' | 'dark' | 'semi-dark' | 'system';

const THEME_KEY = 'tf-theme-preference';
const themeMode = ref<ThemeMode>('semi-dark');

function applyTheme(mode: ThemeMode) {
  const root = document.documentElement;
  const menu = document.getElementById('layout-menu');

  let resolvedTheme = mode;
  if (mode === 'system') {
    resolvedTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  if (mode === 'semi-dark') {
    root.setAttribute('data-bs-theme', 'light');
    if (menu) {
      menu.setAttribute('data-bs-theme', 'dark');
    }
  } else {
    root.setAttribute('data-bs-theme', resolvedTheme);
    if (menu) {
      menu.removeAttribute('data-bs-theme');
    }
  }
}

export function useTheme() {
  const isDark = computed(() => {
    if (themeMode.value === 'dark') return true;
    if (themeMode.value === 'system') {
      return typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    return false;
  });

  function setTheme(mode: ThemeMode) {
    themeMode.value = mode;
    localStorage.setItem(THEME_KEY, mode);
    applyTheme(mode);
  }

  function initTheme() {
    const saved = localStorage.getItem(THEME_KEY) as ThemeMode | null;
    if (saved && ['light', 'dark', 'semi-dark', 'system'].includes(saved)) {
      themeMode.value = saved;
    } else {
      themeMode.value = 'semi-dark';
    }
    applyTheme(themeMode.value);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (themeMode.value === 'system') {
        applyTheme('system');
      }
    });
  }

  return {
    themeMode,
    isDark,
    setTheme,
    initTheme,
    applyTheme,
  };
}
