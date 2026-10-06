<template>
  <div class="tf-dashboard">
    <!-- Encabezado de Bienvenida Neutral y Elegante -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body p-4 p-md-5">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary mb-3 small fw-semibold">
              <i class="fa-solid fa-compass" aria-hidden="true"></i>
              <span>Panel de Control &bull; TravelFlow CMS</span>
            </div>
            <h1 class="h2 fw-bold text-body mb-2">
              Bienvenido, {{ authStore.displayName }}
            </h1>
            <p class="lead text-muted mb-4 fs-6">
              Tu sistema de gestión de contenidos turísticos está listo para operar.
            </p>
            <div class="d-flex flex-wrap gap-2 text-muted small">
              <span class="d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-user-shield text-primary" aria-hidden="true"></i>
                <span>Rol: <strong>{{ primaryRole }}</strong></span>
              </span>
              <span class="mx-2 d-none d-sm-inline">&bull;</span>
              <span class="d-inline-flex align-items-center gap-1">
                <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i>
                <span>Estado: <strong>Activo</strong></span>
              </span>
            </div>
          </div>
          <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
            <div class="d-inline-flex align-items-center justify-content-center p-4 rounded-4 bg-primary bg-opacity-10 text-primary">
              <i class="fa-solid fa-globe fa-3x" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tarjeta Introductoria de Estado Operativo -->
    <div class="card shadow-sm border-0">
      <div class="card-body p-4">
        <h2 class="h5 fw-bold text-body mb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-layer-group text-primary" aria-hidden="true"></i>
          <span>Infraestructura Base Homologada</span>
        </h2>
        <p class="text-muted mb-3">
          El núcleo de autenticación, control de acceso basado en roles (RBAC) y la infraestructura visual oficial con
          <strong>AdminLTE 4 Vue</strong> sobre <strong>Bootstrap 5.3.8</strong> se encuentran integrados y verificados.
        </p>
        <p class="text-muted small mb-0">
          A medida que se desarrollen los módulos de gestión (Tours, Contenidos, Medios, Destinos), sus opciones se habilitarán
          progresivamente en el menú lateral conforme a las autorizaciones soberanas de tu perfil.
        </p>
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
