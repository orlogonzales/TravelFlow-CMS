<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary text-white py-3">
            <h4 class="mb-0 fw-semibold">
              <i class="fa-solid fa-layer-group me-2"></i>
              TravelFlow CMS — Infraestructura Técnica Base
            </h4>
            <small class="text-white-50">Fase 0D: Verificación de Frontend y Testing MySQL</small>
          </div>
          <div class="card-body p-4">
            <div class="alert alert-info border-0 d-flex align-items-center mb-4" role="alert">
              <i class="fa-solid fa-circle-info fs-4 me-3"></i>
              <div>
                <strong>Aviso de Gobernanza:</strong> Esta vista es un componente técnico de verificación.
                No constituye el dashboard definitivo ni implementa vistas de negocio o autenticación.
              </div>
            </div>

            <h5 class="fw-bold mb-3 text-secondary">
              <i class="fa-solid fa-microchip me-2"></i> Estado de la Infraestructura
            </h5>

            <ul class="list-group list-group-flush mb-4">
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-brands fa-laravel text-danger me-2"></i> Backend Framework</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Laravel 13.x</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-brands fa-vuejs text-success me-2"></i> Frontend Reactivo</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Vue 3.5+</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-code text-info me-2"></i> Tipado Estricto</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">TypeScript 5.x</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-brands fa-bootstrap text-primary me-2"></i> Framework CSS Admin</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Bootstrap 5.3.8</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-icons text-warning me-2"></i> Iconografía</span>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Font Awesome Free 6.7</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-database text-primary me-2"></i> Motor de Base de Datos</span>
                <span class="badge bg-info-subtle text-info border border-info-subtle">MySQL 8.4 (tf_cms / tf_cms_test)</span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-network-wired text-secondary me-2"></i> Estado Global / Rutas</span>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Pinia + Vue Router</span>
              </li>
            </ul>

            <div class="card bg-light border-0 p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="fw-semibold">Prueba de Reactividad Vue 3:</span>
                  <span class="badge bg-dark ms-2">Contador: {{ counter }}</span>
                </div>
                <div class="btn-group">
                  <button class="btn btn-outline-primary btn-sm" @click="increment">
                    <i class="fa-solid fa-plus me-1"></i> Incrementar
                  </button>
                  <button class="btn btn-outline-secondary btn-sm" @click="testSweetAlert">
                    <i class="fa-solid fa-bell me-1"></i> Probar Feedback
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white border-top-0 py-3 text-muted text-center small">
            TravelFlow CMS &copy; 2026 &mdash; Infraestructura de Frontend y MySQL Testing (Fase 0D)
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Swal from 'sweetalert2';

const counter = ref<number>(0);

function increment(): void {
  counter.value++;
}

function testSweetAlert(): void {
  Swal.fire({
    title: 'Infraestructura Frontend Lista',
    text: 'Bootstrap 5.3.8, Vue 3, TypeScript y SweetAlert2 integrados correctamente.',
    icon: 'success',
    confirmButtonText: 'Aceptar',
    customClass: {
      confirmButton: 'btn btn-primary',
    },
    buttonsStyling: false,
  });
}
</script>
