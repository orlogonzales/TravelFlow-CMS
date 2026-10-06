import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useApi, ApiError } from '@/composables/useApi';

export interface PersonaProfile {
  id: number;
  nombre_completo: string;
  tipo_documento: string | null;
  numero_documento: string | null;
}

export interface UserProfile {
  id: number;
  name: string;
  email: string;
  status: 'active' | 'inactive' | 'blocked';
  persona: PersonaProfile | null;
  roles: string[];
  permissions: string[];
}

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserProfile | null>(null);
  const loading = ref<boolean>(false);
  const initialized = ref<boolean>(false);

  const api = useApi();

  const isAuthenticated = computed(() => !!user.value && user.value.status === 'active');

  const displayName = computed(() => {
    if (!user.value) return '';
    return user.value.persona?.nombre_completo || user.value.name;
  });

  const userRoles = computed(() => user.value?.roles || []);
  const userPermissions = computed(() => user.value?.permissions || []);

  /**
   * Comprueba si el usuario autenticado posee un permiso específico.
   */
  function hasPermission(permissionSlug: string): boolean {
    if (!user.value) return false;
    return user.value.permissions.includes(permissionSlug);
  }

  /**
   * Comprueba si el usuario autenticado tiene un rol específico.
   */
  function hasRole(roleSlug: string): boolean {
    if (!user.value) return false;
    return user.value.roles.includes(roleSlug);
  }

  /**
   * Obtiene la sesión actual desde el backend mediante GET /api/auth/me.
   * Utiliza el flujo stateful de cookies HttpOnly.
   */
  async function fetchCurrentUser(): Promise<UserProfile | null> {
    loading.value = true;
    try {
      const response = await api.get<{ success: boolean; user: UserProfile }>('/api/auth/me');
      if (response && response.user) {
        user.value = response.user;
      } else {
        user.value = null;
      }
    } catch (error) {
      user.value = null;
    } finally {
      loading.value = false;
      initialized.value = true;
    }
    return user.value;
  }

  /**
   * Inicia sesión en el CMS.
   * 1. Solicita cookie CSRF a Sanctum (/sanctum/csrf-cookie).
   * 2. Envía credenciales a /api/auth/login.
   * 3. Establece el usuario en memoria.
   */
  async function login(credentials: LoginCredentials): Promise<UserProfile> {
    loading.value = true;
    try {
      await api.initializeCsrf();

      const response = await api.post<{
        success: boolean;
        message: string;
        user: UserProfile;
      }>('/api/auth/login', credentials);

      if (response && response.user) {
        user.value = response.user;
        initialized.value = true;
        return response.user;
      }

      throw new Error('Respuesta inválida del servidor.');
    } finally {
      loading.value = false;
    }
  }

  /**
   * Cierra la sesión activa.
   * Invoca /api/auth/logout y limpia el estado reactivo en memoria.
   */
  async function logout(): Promise<void> {
    loading.value = true;
    try {
      await api.post('/api/auth/logout');
    } catch (error) {
      // Incluso si falla la red, el estado local se limpia
      console.warn('Error durante el cierre de sesión en backend:', error);
    } finally {
      user.value = null;
      loading.value = false;
    }
  }

  return {
    user,
    loading,
    initialized,
    isAuthenticated,
    displayName,
    userRoles,
    userPermissions,
    hasPermission,
    hasRole,
    fetchCurrentUser,
    login,
    logout,
  };
});
