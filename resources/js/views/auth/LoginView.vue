<template>
  <div class="authentication-wrapper authentication-cover">
    <!-- Brand / Logotipo en esquina superior -->
    <router-link to="/login" class="auth-cover-brand d-flex align-items-center gap-2 text-decoration-none">
      <span class="app-brand-logo demo">
        <i class="fa-solid fa-compass text-primary fs-3" aria-hidden="true"></i>
      </span>
      <span class="app-brand-text demo text-heading fw-bold fs-4">
        TravelFlow <span class="fw-normal text-primary">CMS</span>
      </span>
    </router-link>

    <div class="authentication-inner row m-0">
      <!-- Sección Izquierda: Ilustración de Portada Materialize -->
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-12 pb-2 position-relative">
        <img
          :src="illustrationSrc"
          class="auth-cover-illustration w-100"
          alt="TravelFlow CMS Login"
          style="max-height: 560px; object-fit: contain;"
        />
        <img
          :src="maskSrc"
          class="authentication-image d-none d-lg-block"
          alt="Decoración de fondo"
        />
      </div>

      <!-- Sección Derecha: Formulario de Autenticación Seguro -->
      <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-12 px-6 px-sm-12 py-6">
        <div class="w-px-400 mx-auto pt-5 pt-lg-0">
          <h4 class="mb-1 fw-bold text-heading">¡Bienvenido a TravelFlow! 👋</h4>
          <p class="mb-4 text-muted small">
            Ingrese sus credenciales de acceso para gestionar la plataforma turística
          </p>

          <!-- Alerta de Error Global -->
          <TfAlert
            v-if="errorMessage"
            variant="danger"
            :message="errorMessage"
            dismissible
            @dismiss="errorMessage = ''"
          />

          <!-- Formulario Reactivo de Login -->
          <form novalidate @submit.prevent="handleSubmit">
            <!-- Campo Correo Electrónico -->
            <div class="mb-3">
              <label for="login-email" class="form-label fw-semibold">
                Correo electrónico <span class="text-danger" aria-hidden="true">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-body text-muted">
                  <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                </span>
                <input
                  id="login-email"
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  :class="{ 'is-invalid': !!errors.email }"
                  placeholder="usuario@travelflow.pe"
                  autocomplete="email"
                  required
                  :disabled="authStore.loading"
                  :aria-invalid="!!errors.email"
                  :aria-describedby="errors.email ? 'login-email-error' : undefined"
                  @blur="validateEmail"
                />
              </div>
              <div v-if="errors.email" id="login-email-error" class="invalid-feedback d-block" role="alert">
                {{ errors.email }}
              </div>
            </div>

            <!-- Campo Contraseña con Toggle de Visibilidad -->
            <div class="mb-3">
              <label for="login-password" class="form-label fw-semibold">
                Contraseña <span class="text-danger" aria-hidden="true">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text bg-body text-muted">
                  <i class="fa-solid fa-lock" aria-hidden="true"></i>
                </span>
                <input
                  id="login-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-control"
                  :class="{ 'is-invalid': !!errors.password }"
                  placeholder="••••••••••••"
                  autocomplete="current-password"
                  required
                  :disabled="authStore.loading"
                  :aria-invalid="!!errors.password"
                  :aria-describedby="errors.password ? 'login-password-error' : undefined"
                  @blur="validatePassword"
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary bg-body border-start-0"
                  :aria-label="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
                  :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
                  tabindex="-1"
                  @click="showPassword = !showPassword"
                >
                  <i
                    :class="showPassword ? 'fa-solid fa-eye-slash text-muted' : 'fa-solid fa-eye text-muted'"
                    aria-hidden="true"
                  ></i>
                </button>
              </div>
              <div v-if="errors.password" id="login-password-error" class="invalid-feedback d-block" role="alert">
                {{ errors.password }}
              </div>
            </div>

            <!-- Checkbox Recordarme -->
            <div class="mb-4 d-flex justify-content-between align-items-center">
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
            <div class="d-grid mb-3">
              <TfButton
                type="submit"
                variant="primary"
                block
                size="lg"
                :loading="authStore.loading"
                icon="fa-solid fa-arrow-right-to-bracket"
              >
                {{ authStore.loading ? 'Verificando credenciales...' : 'Ingresar al sistema' }}
              </TfButton>
            </div>
          </form>

          <div class="mt-4 pt-3 border-top text-center text-muted small">
            <span>TravelFlow CMS &copy; {{ currentYear }} &bull; Acceso administrativo restringido</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTheme } from '@/composables/useTheme';
import { TfButton, TfAlert } from '@/design-system';
import { ApiError } from '@/composables/useApi';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { isDark } = useTheme();

const currentYear = new Date().getFullYear();
const showPassword = ref(false);

const illustrationSrc = computed(() => {
  return isDark.value
    ? '/assets/img/illustrations/auth-login-illustration-dark.png'
    : '/assets/img/illustrations/auth-login-illustration-light.png';
});

const maskSrc = computed(() => {
  return isDark.value
    ? '/assets/img/illustrations/auth-basic-login-mask-dark.png'
    : '/assets/img/illustrations/auth-basic-login-mask-light.png';
});

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

<style scoped>
.auth-cover-brand {
  position: absolute;
  top: 2rem;
  left: 2.5rem;
  z-index: 10;
}

.w-px-400 {
  width: 100%;
  max-width: 400px;
}
</style>
