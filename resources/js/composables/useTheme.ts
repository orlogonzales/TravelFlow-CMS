import { computed } from 'vue';
import { usePreferencesStore } from '@/stores/preferences';

export type ThemeMode = 'light' | 'dark' | 'semi-dark' | 'system';

export function useTheme() {
  const store = usePreferencesStore();

  const themeMode = computed<ThemeMode>(() => {
    if (store.theme === 'light' && store.semiDark) {
      return 'semi-dark';
    }
    return store.theme;
  });

  const isDark = computed(() => store.isDark);

  function setTheme(mode: ThemeMode) {
    if (mode === 'semi-dark') {
      store.setTheme('light');
      store.setSemiDark(true);
    } else {
      store.setTheme(mode);
    }
  }

  function initTheme() {
    store.initPreferences();
  }

  function applyTheme() {
    store.applyToDom();
  }

  return {
    themeMode,
    isDark,
    setTheme,
    initTheme,
    applyTheme,
  };
}
