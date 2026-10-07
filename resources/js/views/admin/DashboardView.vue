<template>
  <div class="tf-dashboard">
    <!-- Fila 1: Bienvenida Ejecutiva y Tarjetas Métricas KPI Materialize -->
    <div class="row g-6 mb-6">
      <!-- Tarjeta de Bienvenida Oficial Materialize CRM -->
      <div class="col-12 col-xxl-4 col-lg-5">
        <div class="card h-100 border-0 shadow-sm position-relative overflow-hidden">
          <div class="card-body d-flex flex-column justify-content-between p-4">
            <div>
              <div class="badge bg-label-primary rounded-pill mb-2 small fw-semibold">
                <i class="fa-solid fa-compass me-1" aria-hidden="true"></i> TravelFlow CMS
              </div>
              <h4 class="card-title fw-bold mb-1 text-heading">
                ¡Bienvenido, <span class="text-primary">{{ authStore.displayName }}</span>!
              </h4>
              <p class="text-muted small mb-3">
                Panel central para la gestión integral de contenidos turísticos y seguridad RBAC.
              </p>
              <div class="d-flex align-items-center gap-2 mb-4">
                <span class="badge bg-label-info fw-semibold font-monospace">
                  <i class="fa-solid fa-user-shield me-1" aria-hidden="true"></i>
                  {{ primaryRole }}
                </span>
                <span class="badge bg-label-success fw-semibold">
                  <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>
                  Sesión Activa
                </span>
              </div>
            </div>

            <div>
              <router-link
                v-if="authStore.hasPermission('personas.ver')"
                to="/admin/personas"
                class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2"
              >
                <span>Explorar registros</span>
                <i class="fa-solid fa-arrow-right small" aria-hidden="true"></i>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Tarjeta Métrica 1: Módulo Personas -->
      <div v-if="authStore.hasPermission('personas.ver')" class="col-sm-6 col-xxl-2 col-lg-3 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column justify-content-between p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
              <div class="avatar">
                <div class="avatar-initial bg-label-info rounded-3 d-flex align-items-center justify-content-center p-3">
                  <i class="fa-solid fa-address-card fs-4 text-info" aria-hidden="true"></i>
                </div>
              </div>
              <router-link to="/admin/personas" class="btn btn-xs btn-label-info py-1 px-2 small" title="Gestionar personas">
                Ver
              </router-link>
            </div>
            <div class="card-info">
              <div v-if="loadingMetrics">
                <TfSkeleton height="28px" width="50%" class="mb-1" />
                <TfSkeleton height="14px" width="80%" />
              </div>
              <div v-else>
                <h4 class="mb-1 fw-bold text-heading">{{ metrics.personas }}</h4>
                <p class="mb-1 text-muted small">Personas</p>
                <div class="badge bg-label-secondary rounded-pill small">Identidad Soberana</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tarjeta Métrica 2: Módulo Usuarios -->
      <div v-if="authStore.hasPermission('usuarios.ver')" class="col-sm-6 col-xxl-2 col-lg-3 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column justify-content-between p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
              <div class="avatar">
                <div class="avatar-initial bg-label-primary rounded-3 d-flex align-items-center justify-content-center p-3">
                  <i class="fa-solid fa-users-gear fs-4 text-primary" aria-hidden="true"></i>
                </div>
              </div>
              <router-link to="/admin/usuarios" class="btn btn-xs btn-label-primary py-1 px-2 small" title="Gestionar usuarios">
                Ver
              </router-link>
            </div>
            <div class="card-info">
              <div v-if="loadingMetrics">
                <TfSkeleton height="28px" width="50%" class="mb-1" />
                <TfSkeleton height="14px" width="80%" />
              </div>
              <div v-else>
                <h4 class="mb-1 fw-bold text-heading">{{ metrics.usuarios }}</h4>
                <p class="mb-1 text-muted small">Usuarios</p>
                <div class="badge bg-label-secondary rounded-pill small">Cuentas Seguras</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tarjeta Métrica 3: Roles y Permisos -->
      <div v-if="authStore.hasPermission('roles.ver')" class="col-sm-6 col-xxl-2 col-lg-3 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column justify-content-between p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
              <div class="avatar">
                <div class="avatar-initial bg-label-warning rounded-3 d-flex align-items-center justify-content-center p-3">
                  <i class="fa-solid fa-user-shield fs-4 text-warning" aria-hidden="true"></i>
                </div>
              </div>
              <router-link to="/admin/roles" class="btn btn-xs btn-label-warning py-1 px-2 small" title="Gestionar roles">
                Ver
              </router-link>
            </div>
            <div class="card-info">
              <div v-if="loadingMetrics">
                <TfSkeleton height="28px" width="50%" class="mb-1" />
                <TfSkeleton height="14px" width="80%" />
              </div>
              <div v-else>
                <h4 class="mb-1 fw-bold text-heading">{{ metrics.roles }}</h4>
                <p class="mb-1 text-muted small">Roles RBAC</p>
                <div class="badge bg-label-secondary rounded-pill small">Matriz de Acceso</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tarjeta Métrica 4: Estado de Seguridad -->
      <div class="col-sm-6 col-xxl-2 col-lg-3 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex flex-column justify-content-between p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
              <div class="avatar">
                <div class="avatar-initial bg-label-success rounded-3 d-flex align-items-center justify-content-center p-3">
                  <i class="fa-solid fa-shield-halved fs-4 text-success" aria-hidden="true"></i>
                </div>
              </div>
              <span class="badge bg-label-success py-1 px-2 small">Activo</span>
            </div>
            <div class="card-info">
              <h4 class="mb-1 fw-bold text-heading">100%</h4>
              <p class="mb-1 text-muted small">Seguridad</p>
              <div class="badge bg-label-secondary rounded-pill small">Sanctum Stateful</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila 2: Accesos Directos Operativos del CMS -->
    <div class="row g-6 mb-6">
      <!-- Columna Izquierda: Acciones Operativas -->
      <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-bottom p-4">
            <h5 class="card-title mb-1 fw-bold text-heading">Acciones Operativas del CMS</h5>
            <small class="text-muted">Tareas administrativas frecuentes autorizadas para su perfil</small>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <!-- Botón Personas -->
              <div v-if="authStore.hasPermission('personas.ver')" class="col-12 col-sm-6">
                <router-link
                  to="/admin/personas"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body hover-shadow transition"
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

              <!-- Botón Usuarios -->
              <div v-if="authStore.hasPermission('usuarios.ver')" class="col-12 col-sm-6">
                <router-link
                  to="/admin/usuarios"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body hover-shadow transition"
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

              <!-- Botón Roles -->
              <div v-if="authStore.hasPermission('roles.ver')" class="col-12 col-sm-6">
                <router-link
                  to="/admin/roles"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body hover-shadow transition"
                >
                  <div class="avatar">
                    <span class="avatar-initial rounded-3 bg-label-warning p-3 d-flex align-items-center justify-content-center">
                      <i class="fa-solid fa-user-shield fs-5" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading d-block">Roles y Permisos</span>
                    <small class="text-muted">Definición de perfiles y privilegios</small>
                  </div>
                </router-link>
              </div>

              <!-- Botón Preferencias de Interfaz -->
              <div class="col-12 col-sm-6">
                <a
                  href="javascript:void(0);"
                  class="d-flex align-items-center gap-3 p-3 rounded-3 border text-decoration-none bg-body hover-shadow transition cursor-pointer"
                  @click="preferencesStore.openCustomizer"
                >
                  <div class="avatar">
                    <span class="avatar-initial rounded-3 bg-label-secondary p-3 d-flex align-items-center justify-content-center">
                      <i class="fa-solid fa-sliders fs-5" aria-hidden="true"></i>
                    </span>
                  </div>
                  <div>
                    <span class="fw-semibold text-heading d-block">Preferencias de Interfaz</span>
                    <small class="text-muted">Personalizar tema y diseño visual</small>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna Derecha: Detalle de Sesión del Operador -->
      <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-bottom p-4">
            <h5 class="card-title mb-1 fw-bold text-heading">Información de Cuenta</h5>
            <small class="text-muted">Detalles del operador autenticado</small>
          </div>
          <div class="card-body p-4">
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
                <span class="text-muted small">Estado de Cuenta:</span>
                <span class="badge bg-label-success">Activo</span>
              </li>
              <li class="list-group-item d-flex align-items-center justify-content-between px-0 py-2 border-0">
                <span class="text-muted small">Permisos Asignados:</span>
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
.hover-shadow {
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.hover-shadow:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08);
}

.cursor-pointer {
  cursor: pointer;
}
</style>
