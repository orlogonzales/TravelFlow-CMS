<template>
  <div class="tf-dashboard">
    <!-- Encabezado de Bienvenida -->
    <div class="card shadow-sm border-0 mb-4 bg-white">
      <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i> Fase 1B Operativa
              </span>
              <span class="text-muted small">&bull; Sesión autenticada</span>
            </div>
            <h1 class="h3 fw-bold text-slate-800 mb-1">Bienvenido, {{ authStore.displayName }}</h1>
            <p class="text-muted mb-0">Shell administrativo inicial de TravelFlow CMS. Autenticación, sesión y RBAC activos.</p>
          </div>
          <div class="d-flex gap-2">
            <TfButton
              variant="outline-secondary"
              size="sm"
              icon="fa-solid fa-rotate"
              :loading="loadingStatus"
              @click="fetchShellStatus"
            >
              Verificar Conexión
            </TfButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Grid de Estado y Verificación -->
    <div class="row g-4">
      <!-- Tarjeta 1: Recurso Protegido de Backend (Permiso admin.acceder) -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
              <i class="fa-solid fa-shield-halved text-primary" aria-hidden="true"></i>
              <span>Autorización Backend Soberana</span>
            </h2>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
              admin.acceder
            </span>
          </div>

          <div class="card-body p-4">
            <!-- Skeleton Loader durante la consulta -->
            <div v-if="loadingStatus" class="py-2">
              <TfSkeleton height="20px" width="col-7" class="mb-3" />
              <TfSkeleton :lines="3" class="mb-2" />
            </div>

            <!-- Estado Exitoso (200 OK con permiso válido) -->
            <div v-else-if="statusData" class="alert alert-success d-flex align-items-start gap-3 mb-0" role="status">
              <i class="fa-solid fa-circle-check fs-4 mt-1 flex-shrink-0 text-success" aria-hidden="true"></i>
              <div>
                <strong class="d-block fw-bold text-success-emphasis mb-1">Permiso Verificado con Éxito</strong>
                <p class="mb-2 small">{{ statusData.message }}</p>
                <div class="font-monospace small text-muted bg-white p-2 rounded border">
                  <div><strong>Entorno:</strong> {{ statusData.data.environment }}</div>
                  <div><strong>PHP:</strong> {{ statusData.data.php_version }}</div>
                  <div><strong>Laravel:</strong> {{ statusData.data.laravel_version }}</div>
                  <div><strong>Motor Persistencia:</strong> MySQL 8.4 LTS</div>
                </div>
              </div>
            </div>

            <!-- Estado de Error / 403 Forbidden -->
            <TfAlert
              v-else-if="statusError"
              variant="danger"
              title="Acceso Denegado por el Backend"
              :message="statusError"
            />
          </div>
        </div>
      </div>

      <!-- Tarjeta 2: Identidad Biográfica y Cuenta (Persona ≠ Usuario ≠ Actor) -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h2 class="h6 fw-bold mb-0 text-slate-800 d-flex align-items-center gap-2">
              <i class="fa-solid fa-id-card text-primary" aria-hidden="true"></i>
              <span>Identidad y RBAC Activos</span>
            </h2>
            <span class="badge bg-light text-dark border">
              ID #{{ authStore.user?.id }}
            </span>
          </div>

          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-sm-6">
                <span class="text-muted small d-block">Identidad Persona Física:</span>
                <strong class="text-slate-800">{{ authStore.user?.persona?.nombre_completo || 'Sin persona asignada' }}</strong>
                <div v-if="authStore.user?.persona" class="text-muted small">
                  {{ authStore.user.persona.tipo_documento }}: {{ authStore.user.persona.numero_documento }}
                </div>
              </div>

              <div class="col-sm-6">
                <span class="text-muted small d-block">Cuenta de Acceso (User):</span>
                <strong class="text-slate-800">{{ authStore.user?.email }}</strong>
                <div class="small">
                  <span class="badge bg-success-subtle text-success">Estado: {{ authStore.user?.status }}</span>
                </div>
              </div>

              <div class="col-12 border-top pt-3">
                <span class="text-muted small d-block mb-1">Roles Asignados (N:M):</span>
                <div class="d-flex flex-wrap gap-1">
                  <span
                    v-for="role in authStore.userRoles"
                    :key="role"
                    class="badge bg-primary px-2 py-1"
                  >
                    <i class="fa-solid fa-user-tag me-1" aria-hidden="true"></i>{{ role }}
                  </span>
                </div>
              </div>

              <div class="col-12 border-top pt-3">
                <span class="text-muted small d-block mb-1">Permisos Efectivos Calculados:</span>
                <div class="d-flex flex-wrap gap-1">
                  <span
                    v-for="perm in authStore.userPermissions"
                    :key="perm"
                    class="badge bg-secondary-subtle text-secondary-emphasis border px-2 py-1 font-monospace"
                    style="font-size: 0.75rem;"
                  >
                    {{ perm }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useApi, ApiError } from '@/composables/useApi';
import { TfButton, TfSkeleton, TfAlert } from '@/design-system';

const authStore = useAuthStore();
const api = useApi();

const loadingStatus = ref(false);
const statusData = ref<any>(null);
const statusError = ref<string | null>(null);

async function fetchShellStatus() {
  loadingStatus.value = true;
  statusError.value = null;

  try {
    const response = await api.get('/api/admin/shell-status');
    statusData.value = response;
  } catch (error: any) {
    if (error instanceof ApiError) {
      statusError.value = error.data.message || `Error HTTP ${error.status}: Acceso no autorizado.`;
    } else {
      statusError.value = error?.message || 'Error de conexión con el recurso protegido.';
    }
  } finally {
    loadingStatus.value = false;
  }
}

onMounted(() => {
  fetchShellStatus();
});
</script>

<style scoped>
.text-slate-800 {
  color: #1e293b;
}
</style>
