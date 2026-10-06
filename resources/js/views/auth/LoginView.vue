<template>
  <div class="tf-login-wrapper min-vh-100 d-flex align-items-center justify-content-center py-5 px-3">
    <div class="tf-login-card card shadow-sm border-0 w-100" style="max-width: 440px;">
      <div class="card-body p-4 p-sm-5">
        <!-- Logo y Encabezado de Marca -->
        <div class="text-center mb-4">
          <div class="tf-brand-icon mb-3 d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 64px; height: 64px;">
            <i class="fa-solid fa-compass fs-2" aria-hidden="true"></i>
          </div>
          <h1 class="h3 fw-bold text-slate-800 mb-1">TravelFlow CMS</h1>
          <p class="text-muted small mb-0">Acceso al Panel de Administración</p>
        </div>

        <!-- Alerta de Error Global -->
        <TfAlert
          v-if="errorMessage"
          variant="danger"
          :message="errorMessage"
          dismissible
          @dismiss="errorMessage = ''"
        />

        <!-- Formulario de Inicio de Sesión -->
        <form novalidate @submit.prevent="handleSubmit">
          <!-- Campo Email -->
          <TfInput
            id="login-email"
            v-model="form.email"
            type="email"
            label="Correo electrónico"
            placeholder="usuario@ejemplo.com"
            autocomplete="email"
            required
            prefix-icon="fa-solid fa-envelope"
            :error-message="errors.email"
            :disabled="authStore.loading"
            @blur="validateEmail"
          />

          <!-- Campo Contraseña con Show/Hide integrado -->
          <TfInput
            id="login-password"
            v-model="form.password"
            type="password"
            label="Contraseña"
            placeholder="••••••••"
            autocomplete="current-password"
            required
            prefix-icon="fa-solid fa-lock"
            :error-message="errors.password"
            :disabled="authStore.loading"
            :allow-toggle-password="true"
            @blur="validatePassword"
          />

          <!-- Checkbox Recordarme -->
          <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
              <input
                id="login-remember"
                v-model="form.remember"
                type="checkbox"
                class="form-check-input"
                :disabled="authStore.loading"
              />
              <label for="login-remember" class="form-check-label text-muted small user-select-none">
                Recordarme en este equipo
              </label>
            </div>
          </div>

          <!-- Botón de Envío -->
          <TfButton
            type="submit"
            variant="primary"
            block
            size="lg"
            :loading="authStore.loading"
            icon="fa-solid fa-arrow-right-to-bracket"
          >
            {{ authStore.loading ? 'Verificando acceso...' : 'Ingresar al sistema' }}
          </TfButton>
        </form>
      </div>

      <div class="card-footer bg-light border-0 py-3 text-center text-muted small">
        <span>TravelFlow CMS &copy; {{ new Date().getFullYear() }} — Acceso restringido</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { TfButton, TfInput, TfAlert } from '@/design-system';
import { ApiError } from '@/composables/useApi';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = reactive({
  email: '',
  password: '',
  remember: false,
});

const errors = reactive({
  email: '',
  password: '',
});

const errorMessage = ref('');

function validateEmail(): boolean {
  errors.email = '';
  if (!form.email.trim()) {
    errors.email = 'El correo electrónico es requerido.';
    return false;
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(form.email)) {
    errors.email = 'Ingrese un formato de correo electrónico válido.';
    return false;
  }
  return true;
}

function validatePassword(): boolean {
  errors.password = '';
  if (!form.password) {
    errors.password = 'La contraseña es requerida.';
    return false;
  }
  return true;
}

async function handleSubmit() {
  errorMessage.value = '';

  const isEmailValid = validateEmail();
  const isPasswordValid = validatePassword();

  if (!isEmailValid || !isPasswordValid) {
    return;
  }

  try {
    await authStore.login({
      email: form.email.trim(),
      password: form.password,
      remember: form.remember,
    });

    // Redirección hacia la ruta administrativa o la URL solicitada originalmente
    const redirectPath = (route.query.redirect as string) || '/admin';
    router.push(redirectPath);
  } catch (error: any) {
    if (error instanceof ApiError) {
      if (error.status === 422) {
        // Errores de validación devueltos por el backend
        if (error.data.errors?.email) {
          errorMessage.value = error.data.errors.email[0];
          errors.email = error.data.errors.email[0];
        } else if (error.data.errors?.password) {
          errors.password = error.data.errors.password[0];
        } else {
          errorMessage.value = error.data.message || 'Las credenciales proporcionadas no son válidas.';
        }
      } else if (error.status === 403) {
        errorMessage.value = error.data.message || 'Su cuenta se encuentra inactiva o bloqueada. Comuníquese con el administrador.';
      } else if (error.status === 429) {
        errorMessage.value = error.data.message || 'Demasiados intentos de acceso. Por favor espere antes de reintentar.';
      } else {
        errorMessage.value = error.data.message || 'Ocurrió un error inesperado al procesar la autenticación.';
      }
    } else {
      errorMessage.value = error?.message || 'Error de conexión con el servidor.';
    }
  }
}
</script>

<style scoped>
.tf-login-wrapper {
  background-color: #f1f5f9;
}

.tf-login-card {
  border-radius: 12px;
}

.text-slate-800 {
  color: #1e293b;
}
</style>
