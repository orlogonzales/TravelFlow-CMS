<template>
  <LteAuthLayout auth-type="login" variant="v2" logo-href="/login">
    <!-- Logo Oficial de TravelFlow CMS en Card Header -->
    <template #logo>
      <div class="d-flex align-items-center justify-content-center gap-2 text-decoration-none py-1">
        <i class="fa-solid fa-compass text-primary fs-3" aria-hidden="true"></i>
        <span class="fs-4 fw-bold text-body">TravelFlow <span class="fw-normal text-primary">CMS</span></span>
      </div>
    </template>

    <!-- Cuerpo del Formulario de Autenticación -->
    <template #default>
      <p class="login-box-msg text-muted small text-center mb-3">
        Ingrese sus credenciales para acceder al panel
      </p>

      <!-- Alerta de Error Global -->
      <TfAlert
        v-if="errorMessage"
        variant="danger"
        :message="errorMessage"
        dismissible
        @dismiss="errorMessage = ''"
      />

      <!-- Formulario Reactivo -->
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

        <!-- Campo Contraseña con Toggle Font Awesome -->
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
        <div class="mb-4">
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

        <!-- Botón de Envío Full Width -->
        <div class="d-grid gap-2">
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
        </div>
      </form>

      <div class="mt-4 pt-3 border-top text-center text-muted small">
        <span>TravelFlow CMS &copy; {{ new Date().getFullYear() }} &bull; Acceso restringido</span>
      </div>
    </template>
  </LteAuthLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { LteAuthLayout } from '@adminlte/vue';
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

    const redirectPath = (route.query.redirect as string) || '/admin';
    router.push(redirectPath);
  } catch (error: any) {
    if (error instanceof ApiError) {
      if (error.status === 422) {
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
