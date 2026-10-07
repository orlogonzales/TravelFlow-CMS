<template>
  <div class="tf-dashboard">
    <!-- Fila 1: Grid CRM Oficial Materialize (dashboards-crm.html) -->
    <div class="row g-6 mb-6">
      <!-- Congratulations / Welcome Card -->
      <div class="col-xxl-4 col-12">
        <div class="card h-100">
          <div class="card-body text-nowrap d-flex flex-column justify-content-between">
            <div>
              <h5 class="card-title mb-1 text-heading">
                ¡Bienvenido, <span class="fw-bold">{{ authStore.displayName }}</span>! 🎉
              </h5>
              <p class="card-subtitle mb-3 text-muted">Panel central de contenidos turísticos</p>
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-label-primary font-monospace">{{ primaryRole }}</span>
                <span class="badge bg-label-success">Activo</span>
              </div>
            </div>
            <div>
              <router-link
                v-if="authStore.hasPermission('personas.ver')"
                to="/admin/personas"
                class="btn btn-sm btn-primary"
              >
                Explorar registros
              </router-link>
              <button
                v-else
                type="button"
                class="btn btn-sm btn-label-secondary"
                @click="preferencesStore.openCustomizer"
              >
                Preferencias
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI 1: Personas -->
      <div v-if="authStore.hasPermission('personas.ver')" class="col-xxl-2 col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-info rounded-3">
                  <i class="fa-solid fa-address-card fs-4 text-info" aria-hidden="true"></i>
                </div>
              </div>
              <router-link to="/admin/personas" class="btn btn-xs btn-label-info py-1 px-2 small">
                Ver
              </router-link>
            </div>
            <div class="card-info mt-4">
              <div v-if="loadingMetrics">
                <TfSkeleton height="28px" width="50%" class="mb-1" />
                <TfSkeleton height="14px" width="80%" />
              </div>
              <div v-else>
                <h5 class="mb-1 fw-bold text-heading">{{ metrics.personas }}</h5>
                <p class="mb-1 text-muted small">Personas</p>
                <div class="badge bg-label-secondary rounded-pill">Identidades</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI 2: Usuarios -->
      <div v-if="authStore.hasPermission('usuarios.ver')" class="col-xxl-2 col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-primary rounded-3">
                  <i class="fa-solid fa-users-gear fs-4 text-primary" aria-hidden="true"></i>
                </div>
              </div>
              <router-link to="/admin/usuarios" class="btn btn-xs btn-label-primary py-1 px-2 small">
                Ver
              </router-link>
            </div>
            <div class="card-info mt-4">
              <div v-if="loadingMetrics">
                <TfSkeleton height="28px" width="50%" class="mb-1" />
                <TfSkeleton height="14px" width="80%" />
              </div>
              <div v-else>
                <h5 class="mb-1 fw-bold text-heading">{{ metrics.usuarios }}</h5>
                <p class="mb-1 text-muted small">Usuarios</p>
                <div class="badge bg-label-secondary rounded-pill">Cuentas</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI 3: Roles -->
      <div v-if="authStore.hasPermission('roles.ver')" class="col-xxl-2 col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-warning rounded-3">
                  <i class="fa-solid fa-user-shield fs-4 text-warning" aria-hidden="true"></i>
                </div>
              </div>
              <router-link to="/admin/roles" class="btn btn-xs btn-label-warning py-1 px-2 small">
                Ver
              </router-link>
            </div>
            <div class="card-info mt-4">
              <div v-if="loadingMetrics">
                <TfSkeleton height="28px" width="50%" class="mb-1" />
                <TfSkeleton height="14px" width="80%" />
              </div>
              <div v-else>
                <h5 class="mb-1 fw-bold text-heading">{{ metrics.roles }}</h5>
                <p class="mb-1 text-muted small">Roles</p>
                <div class="badge bg-label-secondary rounded-pill">Perfiles RBAC</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI 4: Privilegios Asignados -->
      <div class="col-xxl-2 col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-success rounded-3">
                  <i class="fa-solid fa-key fs-4 text-success" aria-hidden="true"></i>
                </div>
              </div>
              <span class="badge bg-label-success py-1 px-2 small">RBAC</span>
            </div>
            <div class="card-info mt-4">
              <h5 class="mb-1 fw-bold text-heading">{{ authStore.userPermissions.length }}</h5>
              <p class="mb-1 text-muted small">Privilegios</p>
              <div class="badge bg-label-secondary rounded-pill">Autorizados</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila 2: Operaciones y Cuenta -->
    <div class="row g-6">
      <!-- Columna Izquierda: Acciones Operativas del CMS -->
      <div class="col-12 col-lg-7">
        <div class="card h-100">
          <div class="card-header pb-2">
            <h5 class="card-title mb-1 text-heading">Acciones Operativas del CMS</h5>
            <small class="text-muted">Accesos rápidos autorizados para su perfil</small>
          </div>
          <div class="card-body pt-3">
            <div class="row g-3">
              <div v-if="authStore.hasPermission('personas.ver')" class="col-12 col-sm-6">
                <router-link
                  to="/admin/personas"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body"
                >
                  <div class="avatar">
                    <span class="avatar-initial rounded-3 bg-label-info p-3 d-flex align-items-center justify-content-center">
                      <i class="fa-solid fa-address-card fs-5" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading d-block">Gestionar Personas</span>
                    <small class="text-muted">Padrón de identidades y documentos</small>
                  </div>
                </router-link>
              </div>

              <div v-if="authStore.hasPermission('usuarios.ver')" class="col-12 col-sm-6">
                <router-link
                  to="/admin/usuarios"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body"
                >
                  <div class="avatar">
                    <span class="avatar-initial rounded-3 bg-label-primary p-3 d-flex align-items-center justify-content-center">
                      <i class="fa-solid fa-users-gear fs-5" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading d-block">Gestionar Usuarios</span>
                    <small class="text-muted">Cuentas, credenciales y estados</small>
                  </div>
                </router-link>
              </div>

              <div v-if="authStore.hasPermission('roles.ver')" class="col-12 col-sm-6">
                <router-link
                  to="/admin/roles"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body"
                >
                  <div class="avatar">
                    <span class="avatar-initial rounded-3 bg-label-warning p-3 d-flex align-items-center justify-content-center">
                      <i class="fa-solid fa-user-shield fs-5" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading d-block">Roles y Permisos</span>
                    <small class="text-muted">Perfiles y matriz de acceso</small>
                  </div>
                </router-link>
              </div>

              <div class="col-12 col-sm-6">
                <a
                  href="javascript:void(0);"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body cursor-pointer"
                  @click="preferencesStore.openCustomizer"
                >
                  <div class="avatar">
                    <span class="avatar-initial rounded-3 bg-label-secondary p-3 d-flex align-items-center justify-content-center">
                      <i class="fa-solid fa-sliders fs-5" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading d-block">Preferencias de Interfaz</span>
                    <small class="text-muted">Tema visual y diseño de layout</small>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Detalle de Sesión del Operador -->
      <div class="col-12 col-lg-5">
        <div class="card h-100">
          <div class="card-header pb-2">
            <h5 class="card-title mb-1 text-heading">Información de Cuenta</h5>
            <small class="text-muted">Detalles del operador autenticado</small>
          </div>
          <div class="card-body pt-3">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2 border-0">
                <span class="text-muted small">Nombre:</span>
                <span class="fw-semibold text-heading small">{{ authStore.displayName }}</span>
              </li>
              <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2 border-0">
                <span class="text-muted small">Correo Electrónico:</span>
                <code class="text-primary font-monospace small">{{ authStore.user?.email }}</code>
              </li>
              <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2 border-0">
                <span class="text-muted small">Rol Principal:</span>
                <span class="badge bg-label-primary font-monospace">{{ primaryRole }}</span>
              </li>
              <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2 border-0">
                <span class="text-muted small">Estado:</span>
                <span class="badge bg-label-success">Activo</span>
              </li>
              <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2 border-0">
                <span class="text-muted small">Permisos:</span>
                <span class="badge bg-label-info font-monospace">{{ authStore.userPermissions.length }} privilegios</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { usePreferencesStore } from '@/stores/preferences';
import { useApi } from '@/composables/useApi';
import { TfSkeleton } from '@/design-system';

const authStore = useAuthStore();
const preferencesStore = usePreferencesStore();
const api = useApi();

const loadingMetrics = ref<boolean>(true);

const metrics = reactive({
  personas: 0,
  usuarios: 0,
  roles: 0,
});

const primaryRole = computed(() => {
  if (authStore.userRoles.length > 0) {
    return authStore.userRoles[0].toUpperCase();
  }
  return 'ADMINISTRADOR';
});

async function loadRealMetrics() {
  loadingMetrics.value = true;
  try {
    const promises: Promise<any>[] = [];

    if (authStore.hasPermission('personas.ver')) {
      promises.push(
        api.get<{ meta?: { total: number } }>('/api/admin/personas?per_page=1').then(res => {
          if (res?.meta?.total !== undefined) metrics.personas = res.meta.total;
        }).catch(() => {})
      );
    }

    if (authStore.hasPermission('usuarios.ver')) {
      promises.push(
        api.get<{ meta?: { total: number } }>('/api/admin/usuarios?per_page=1').then(res => {
          if (res?.meta?.total !== undefined) metrics.usuarios = res.meta.total;
        }).catch(() => {})
      );
    }

    if (authStore.hasPermission('roles.ver')) {
      promises.push(
        api.get<{ meta?: { total: number } }>('/api/admin/roles?per_page=1').then(res => {
          if (res?.meta?.total !== undefined) metrics.roles = res.meta.total;
        }).catch(() => {})
      );
    }

    await Promise.all(promises);
  } finally {
    loadingMetrics.value = false;
  }
}

onMounted(() => {
  loadRealMetrics();
});
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
