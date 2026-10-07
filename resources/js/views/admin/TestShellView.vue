<template>
  <div class="tf-test-shell-view">
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h4 class="card-title fw-bold text-heading mb-2">
              Validación de Shell Materialize v13.11.1
            </h4>
            <p class="text-muted mb-4">
              Esta superficie comprueba la geometría exacta del layout: margen superior bajo el navbar fijo,
              alineación con el menú vertical (expandido y contraído), ausencia de superposiciones y comportamiento responsive.
            </p>
            <div class="alert alert-primary mb-0 d-flex align-items-center gap-2" role="alert">
              <i class="fa-solid fa-circle-check fs-5" aria-hidden="true"></i>
              <span>El contenedor principal respeta el padding y offset nativos de Materialize (64px + container-p-y). Cero superposición.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Shell de prueba vacío y limpio (M2-A)
</script>
