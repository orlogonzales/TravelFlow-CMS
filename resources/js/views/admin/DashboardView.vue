<template>
  <div class="tf-dashboard">
    <!-- Fila 1: Tarjeta de Bienvenida Principal Materialize -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-label-primary mb-3 small fw-semibold">
                  <i class="fa-solid fa-compass" aria-hidden="true"></i>
                  <span>Panel de Control &bull; TravelFlow CMS</span>
                </div>
                <h1 class="h2 fw-bold text-heading mb-2">
                  Bienvenido, {{ authStore.displayName }}
                </h1>
                <p class="lead text-muted mb-4 fs-6">
                  El sistema de gestión de contenidos turísticos se encuentra operativo y seguro.
                </p>
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                  <span class="d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-user-shield text-primary" aria-hidden="true"></i>
                    <span>Rol: <strong>{{ primaryRole }}</strong></span>
                  </span>
                  <span class="text-muted d-none d-sm-inline">&bull;</span>
                  <span class="d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i>
                    <span>Estado: <strong>Activo</strong></span>
                  </span>
                  <span class="text-muted d-none d-sm-inline">&bull;</span>
                  <span class="d-inline-flex align-items-center gap-1">
                    <i class="fa-solid fa-palette text-info" aria-hidden="true"></i>
                    <span>Sistema Visual: <strong>Materialize v13.11.1</strong></span>
                  </span>
                </div>
              </div>
              <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                <div class="avatar avatar-xl d-inline-flex align-items-center justify-content-center p-4 rounded-4 bg-label-primary shadow-sm" style="width: 88px; height: 88px;">
                  <i class="fa-solid fa-globe fa-3x" aria-hidden="true"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila 2: Tarjetas Métricas / Accesos Rápidos Materialize -->
    <div class="row g-4 mb-4">
      <!-- Módulo Personas -->
      <div v-if="authStore.hasPermission('personas.ver')" class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="avatar">
                <span class="avatar-initial rounded-3 bg-label-info p-3 d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-address-card fs-4" aria-hidden="true"></i>
                </span>
              </div>
              <router-link to="/admin/personas" class="btn btn-sm btn-label-info waves-effect">
                Gestionar
              </router-link>
            </div>
            <h5 class="card-title fw-bold mb-1">Módulo Personas</h5>
            <small class="text-muted d-block">Identidad soberana, documentos y datos personales</small>
          </div>
        </div>
      </div>

      <!-- Módulo Usuarios -->
      <div v-if="authStore.hasPermission('usuarios.ver')" class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="avatar">
                <span class="avatar-initial rounded-3 bg-label-primary p-3 d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-users-gear fs-4" aria-hidden="true"></i>
                </span>
              </div>
              <router-link to="/admin/usuarios" class="btn btn-sm btn-label-primary waves-effect">
                Gestionar
              </router-link>
            </div>
            <h5 class="card-title fw-bold mb-1">Módulo Usuarios</h5>
            <small class="text-muted d-block">Cuentas, credenciales seguras y vinculación</small>
          </div>
        </div>
      </div>

      <!-- Módulo Roles y Permisos -->
      <div v-if="authStore.hasPermission('roles.ver')" class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="avatar">
                <span class="avatar-initial rounded-3 bg-label-warning p-3 d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-user-shield fs-4 text-warning" aria-hidden="true"></i>
                </span>
              </div>
              <router-link to="/admin/roles" class="btn btn-sm btn-label-warning waves-effect">
                Gestionar
              </router-link>
            </div>
            <h5 class="card-title fw-bold mb-1">Roles y Permisos</h5>
            <small class="text-muted d-block">Matriz de privilegios y control de acceso RBAC</small>
          </div>
        </div>
      </div>

      <!-- Estado Técnico -->
      <div class="col-sm-6 col-xl-3">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="avatar">
                <span class="avatar-initial rounded-3 bg-label-secondary p-3 d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-server fs-4" aria-hidden="true"></i>
                </span>
              </div>
              <router-link to="/admin/status" class="btn btn-sm btn-label-secondary waves-effect">
                Ver estado
              </router-link>
            </div>
            <h5 class="card-title fw-bold mb-1">Estado Técnico</h5>
            <small class="text-muted d-block">Salud del sistema, base de datos y micro-baseline</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Fila 3: Tarjeta de Arquitectura y Homologación -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold text-heading mb-3 d-flex align-items-center gap-2">
              <i class="fa-solid fa-layer-group text-primary" aria-hidden="true"></i>
              <span>Arquitectura Visual Homologada — Materialize v13.11.1</span>
            </h2>
            <p class="text-muted mb-3">
              El entorno visual se encuentra migrado sobre la base oficial de <strong>Materialize Admin v13.11.1</strong>,
              compilado de forma nativa sobre <strong>Bootstrap 5.3.8</strong> y <strong>Font Awesome 6 Free</strong>.
              Todas las reglas de negocio, endpoints RESTful y pipeline de autorización permanecen soberanos e inmutables.
            </p>
            <p class="text-muted small mb-0">
              A medida que se desarrollen los módulos de contenido turístico (Tours, Salidas, Destinos, Medios),
              sus interfaces adoptarán los estándares y componentes oficiales de esta biblioteca.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

const primaryRole = computed(() => {
  if (authStore.userRoles.length > 0) {
    return authStore.userRoles[0].toUpperCase();
  }
  return 'ADMINISTRADOR';
});
</script>
