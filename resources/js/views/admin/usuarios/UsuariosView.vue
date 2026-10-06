<template>
  <div class="tf-usuarios-view">
    <!-- Encabezado de la Vista -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1 text-body">Usuarios</h2>
        <p class="text-muted small mb-0">
          Administración de cuentas de acceso, roles asignados y seguridad de autenticación de TravelFlow CMS.
        </p>
      </div>
      <div>
        <TfButton
          v-if="authStore.hasPermission('usuarios.crear')"
          variant="primary"
          @click="openCreateModal"
        >
          <template #icon>
            <i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i>
          </template>
          Nuevo usuario
        </TfButton>
      </div>
    </div>

    <!-- Alerta de Error de Carga Principal -->
    <TfAlert
      v-if="fetchError"
      variant="danger"
      class="mb-4"
    >
      <div class="d-flex justify-content-between align-items-center">
        <span>{{ fetchError }}</span>
        <button
          type="button"
          class="btn btn-sm btn-outline-danger ms-3"
          @click="fetchUsuarios(pagination.currentPage)"
        >
          <i class="fa-solid fa-rotate-right me-1" aria-hidden="true"></i> Reintentar
        </button>
      </div>
    </TfAlert>

    <!-- Tarjeta Principal con Búsqueda y Tabla -->
    <div class="card shadow-sm border-0 mb-4">
      <!-- Toolbar de Búsqueda de Ancho Completo -->
      <div class="card-header bg-transparent border-bottom p-3">
        <form class="w-100" @submit.prevent="handleSearch">
          <div class="input-group">
            <span class="input-group-text bg-body text-muted border-end-0">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </span>
            <input
              v-model="searchQuery"
              type="search"
              class="form-control border-start-0"
              placeholder="Buscar por usuario, correo electrónico, documento o nombre de persona..."
              aria-label="Buscar usuarios"
            />
            <button
              type="submit"
              class="btn btn-primary px-3 d-inline-flex align-items-center gap-1"
              :disabled="loading"
            >
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <span class="d-none d-sm-inline">Buscar</span>
            </button>
            <button
              v-if="activeSearch"
              type="button"
              class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1"
              title="Limpiar búsqueda"
              aria-label="Limpiar búsqueda"
              @click="clearSearch"
            >
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              <span class="d-none d-sm-inline">Limpiar</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Tabla Asíncrona -->
      <div class="card-body p-0">
        <TfTable
          :headers="tableHeaders"
          :items="usuarios"
          :loading="loading"
          :skeleton-rows="5"
          :empty-title="activeSearch ? 'No se encontraron resultados' : 'No hay usuarios registrados'"
          :empty-description="activeSearch ? `No se encontraron coincidencias para &quot;${activeSearch}&quot;.` : 'Comience creando una nueva cuenta de acceso en el sistema.'"
          :current-sort="sortColumn"
          :current-direction="sortDirection"
          @sort="handleSort"
        >
          <!-- Celda: Usuario / Persona Vinculada -->
          <template #cell-usuario="{ item }">
            <div class="d-flex align-items-center gap-2">
              <div
                class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                style="width: 38px; height: 38px; font-size: 0.85rem;"
                aria-hidden="true"
              >
                {{ getUserInitials(item.name) }}
              </div>
              <div class="min-w-0">
                <span class="fw-semibold text-body d-block text-truncate">
                  {{ item.persona ? item.persona.nombre_completo : item.name }}
                </span>
                <small v-if="item.persona" class="text-muted d-block font-monospace">
                  <i class="fa-solid fa-id-card me-1 small"></i>{{ item.persona.tipo_documento || 'DOC' }} {{ item.persona.numero_documento }}
                </small>
                <small v-else class="badge bg-secondary-subtle text-secondary border">Sin persona vinculada</small>
              </div>
            </div>
          </template>

          <!-- Celda: Email de Acceso -->
          <template #cell-email="{ item }">
            <div>
              <span class="font-monospace small text-body d-inline-flex align-items-center gap-1">
                <i class="fa-regular fa-envelope text-muted" aria-hidden="true"></i>
                {{ item.email }}
              </span>
              <span v-if="item.id === authStore.user?.id" class="badge bg-primary-subtle text-primary ms-1">
                Mi cuenta
              </span>
            </div>
          </template>

          <!-- Celda: Roles Asignados -->
          <template #cell-roles="{ item }">
            <div v-if="item.roles && item.roles.length > 0" class="d-flex flex-wrap gap-1">
              <span
                v-for="r in item.roles"
                :key="r.id"
                class="badge bg-primary text-white border"
                :title="r.slug"
              >
                {{ r.name }}
              </span>
            </div>
            <span v-else class="text-muted small">— Sin roles —</span>
          </template>

          <!-- Celda: Estado -->
          <template #cell-status="{ item }">
            <TfBadge
              :variant="getStatusVariant(item.status)"
              :dot="true"
            >
              {{ formatStatusLabel(item.status) }}
            </TfBadge>
          </template>

          <!-- Celda: Último Acceso -->
          <template #cell-last_login_at="{ item }">
            <span class="small text-muted">
              {{ formatDate(item.last_login_at) }}
            </span>
          </template>

          <!-- Celda: Acciones por Fila -->
          <template #actions="{ item }">
            <div class="d-flex justify-content-end gap-1" role="group" aria-label="Acciones de usuario">
              <!-- Ver Detalle -->
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Ver ficha de usuario"
                aria-label="Ver ficha de usuario"
                @click="openDetailModal(item.id)"
              >
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Ver</span>
              </button>

              <!-- Editar Datos Base -->
              <button
                v-if="authStore.hasPermission('usuarios.editar')"
                type="button"
                class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Editar información básica"
                aria-label="Editar información básica"
                @click="openEditModal(item.id)"
              >
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Editar</span>
              </button>

              <!-- Administrar Roles -->
              <button
                v-if="authStore.hasPermission('usuarios.roles')"
                type="button"
                class="btn btn-sm btn-outline-info px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Administrar roles"
                aria-label="Administrar roles"
                @click="openRolesModal(item)"
              >
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Roles</span>
              </button>

              <!-- Cambiar Estado -->
              <button
                v-if="authStore.hasPermission('usuarios.estado')"
                type="button"
                class="btn btn-sm btn-outline-warning px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Modificar estado de cuenta"
                aria-label="Modificar estado de cuenta"
                @click="openEstadoModal(item)"
              >
                <i class="fa-solid fa-toggle-on" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Estado</span>
              </button>

              <!-- Restablecer Contraseña -->
              <button
                v-if="authStore.hasPermission('usuarios.password')"
                type="button"
                class="btn btn-sm btn-outline-danger px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Restablecer contraseña"
                aria-label="Restablecer contraseña"
                @click="openPasswordModal(item)"
              >
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Clave</span>
              </button>
            </div>
          </template>
        </TfTable>
      </div>

      <!-- Paginación Backend -->
      <div v-if="pagination.total > 0" class="card-footer bg-transparent border-top p-3">
        <TfPagination
          :current-page="pagination.currentPage"
          :last-page="pagination.lastPage"
          :total="pagination.total"
          :per-page="pagination.perPage"
          :disabled="loading"
          @page-change="handlePageChange"
        />
      </div>
    </div>

    <!-- MODAL 1: Registrar Nuevo Usuario (size="lg") -->
    <TfModal
      v-model="showCreateModal"
      title="Registrar Nueva Cuenta de Usuario"
      size="lg"
      :loading="saving"
    >
      <form id="create-user-form" @submit.prevent="submitCreateUser">
        <div class="row g-3">
          <!-- Persona Elegible Obligatoria -->
          <div class="col-12">
            <label for="create-persona-id" class="form-label fw-semibold">
              Persona vinculada <span class="text-danger">*</span>
            </label>
            <div v-if="loadingPersonasElegibles" class="py-2">
              <TfSkeleton height="38px" />
            </div>
            <select
              v-else
              id="create-persona-id"
              v-model="createForm.persona_id"
              class="form-select"
              :class="{ 'is-invalid': createErrors.persona_id }"
              :disabled="saving"
              required
            >
              <option value="" disabled>Seleccione una persona física activa sin cuenta...</option>
              <option
                v-for="p in personasElegibles"
                :key="p.id"
                :value="p.id"
              >
                {{ p.nombre_completo }} ({{ p.tipo_documento || 'DOC' }}: {{ p.numero_documento || 'Sin número' }})
              </option>
            </select>
            <div v-if="createErrors.persona_id" class="invalid-feedback d-block">
              {{ createErrors.persona_id[0] }}
            </div>
            <div v-if="personasElegibles.length === 0 && !loadingPersonasElegibles" class="form-text text-warning mt-1">
              <i class="fa-solid fa-triangle-exclamation me-1"></i>
              No hay personas activas sin cuenta disponible. Debe registrar primero una nueva persona en el módulo Personas.
            </div>
          </div>

          <!-- Email de Acceso -->
          <div class="col-12 col-md-6">
            <TfInput
              id="create-email"
              v-model="createForm.email"
              type="email"
              label="Correo electrónico de acceso"
              placeholder="usuario@dominio.com"
              prefix-icon="fa-regular fa-envelope"
              :required="true"
              :error-message="createErrors.email?.[0]"
              :disabled="saving"
            />
          </div>

          <!-- Estado Inicial -->
          <div class="col-12 col-md-6">
            <TfSelect
              id="create-status"
              v-model="createForm.status"
              label="Estado inicial"
              :required="true"
              :options="statusOptions"
              :error-message="createErrors.status?.[0]"
              :disabled="saving"
            />
          </div>

          <!-- Contraseña Temporal -->
          <div class="col-12 col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label for="create-password" class="form-label fw-semibold mb-0">
                Contraseña temporal <span class="text-danger">*</span>
              </label>
              <button
                type="button"
                class="btn btn-link btn-sm p-0 text-decoration-none"
                @click="generateAndFillPassword('create')"
              >
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generar segura
              </button>
            </div>
            <input
              id="create-password"
              v-model="createForm.password"
              type="text"
              class="form-control font-monospace"
              :class="{ 'is-invalid': createErrors.password }"
              placeholder="Mínimo 12 caracteres..."
              required
              :disabled="saving"
            />
            <div v-if="createErrors.password" class="invalid-feedback d-block">
              {{ createErrors.password[0] }}
            </div>
            <div class="form-text small">Mínimo 12 caracteres criptográficamente seguros.</div>
          </div>

          <!-- Confirmación de Contraseña -->
          <div class="col-12 col-md-6">
            <TfInput
              id="create-password-confirm"
              v-model="createForm.password_confirmation"
              type="text"
              label="Confirmar contraseña"
              placeholder="Repetir contraseña..."
              :required="true"
              :disabled="saving"
            />
          </div>

          <!-- Asignación de Roles (Solo si posee usuarios.roles) -->
          <div v-if="authStore.hasPermission('usuarios.roles')" class="col-12 border-top pt-3 mt-3">
            <label class="form-label fw-semibold d-block">
              Roles asignados (Regla Anti-Escalada activa)
            </label>
            <div v-if="loadingRolesDisponibles" class="py-2">
              <TfSkeleton height="36px" />
            </div>
            <div v-else-if="rolesDisponibles.length === 0" class="text-muted small">
              No posee permisos suficientes para asignar los roles del catálogo.
            </div>
            <div v-else class="row g-2">
              <div
                v-for="r in rolesDisponibles"
                :key="r.id"
                class="col-12 col-md-6"
              >
                <div class="form-check p-2 border rounded bg-body-tertiary h-100">
                  <input
                    :id="`create-role-${r.id}`"
                    v-model="createForm.roles"
                    class="form-check-input ms-0 me-2"
                    type="checkbox"
                    :value="r.id"
                    :disabled="saving"
                  />
                  <label :for="`create-role-${r.id}`" class="form-check-label fw-semibold text-body">
                    {{ r.name }}
                  </label>
                  <small class="d-block text-muted ps-4">{{ r.description || r.slug }}</small>
                </div>
              </div>
            </div>
            <div v-if="createErrors.roles" class="text-danger small mt-2">
              {{ createErrors.roles[0] }}
            </div>
          </div>
        </div>
      </form>

      <template #footer>
        <TfButton variant="secondary" :disabled="saving" @click="showCreateModal = false">
          Cancelar
        </TfButton>
        <TfButton variant="primary" :loading="saving" form="create-user-form" type="submit">
          <template #icon>
            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>
          </template>
          Guardar usuario
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 2: Editar Información Base (size="lg") -->
    <TfModal
      v-model="showEditModal"
      title="Editar Información de Usuario"
      size="lg"
      :loading="saving"
    >
      <div v-if="loadingRecord" class="py-3">
        <div class="row g-3">
          <div class="col-12"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
        </div>
      </div>

      <form v-else id="edit-user-form" @submit.prevent="submitEditUser">
        <div class="row g-3">
          <div class="col-12">
            <label for="edit-persona-id" class="form-label fw-semibold">
              Persona vinculada <span class="text-danger">*</span>
            </label>
            <select
              id="edit-persona-id"
              v-model="editForm.persona_id"
              class="form-select"
              :class="{ 'is-invalid': editErrors.persona_id }"
              :disabled="saving"
              required
            >
              <option
                v-for="p in editPersonaOptions"
                :key="p.id"
                :value="p.id"
              >
                {{ p.nombre_completo }} ({{ p.tipo_documento || 'DOC' }}: {{ p.numero_documento || 'Sin número' }})
              </option>
            </select>
            <div v-if="editErrors.persona_id" class="invalid-feedback d-block">
              {{ editErrors.persona_id[0] }}
            </div>
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-name"
              v-model="editForm.name"
              label="Nombre descriptivo de la cuenta"
              placeholder="Opcional (deriva de persona si queda vacío)"
              :error-message="editErrors.name?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-email"
              v-model="editForm.email"
              type="email"
              label="Correo electrónico de acceso"
              prefix-icon="fa-regular fa-envelope"
              :required="true"
              :error-message="editErrors.email?.[0]"
              :disabled="saving"
            />
          </div>
        </div>
      </form>

      <template #footer>
        <TfButton variant="secondary" :disabled="saving" @click="showEditModal = false">
          Cancelar
        </TfButton>
        <TfButton variant="primary" :loading="saving" form="edit-user-form" type="submit">
          <template #icon>
            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>
          </template>
          Actualizar usuario
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 3: Ficha de Identidad de Usuario (size="lg") -->
    <TfModal
      v-model="showDetailModal"
      title="Ficha de Cuenta de Usuario"
      size="lg"
    >
      <div v-if="loadingRecord" class="py-3">
        <div class="d-flex align-items-center gap-3 mb-4">
          <TfSkeleton height="52px" width="52px" :circle="true" />
          <div class="flex-grow-1">
            <TfSkeleton height="24px" width="col-5" class="mb-2" />
            <TfSkeleton height="14px" width="col-3" />
          </div>
        </div>
        <div class="row g-3">
          <div class="col-12 col-md-6"><TfSkeleton height="110px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="110px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="110px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="110px" /></div>
        </div>
      </div>

      <div v-else-if="detailRecord" class="py-2">
        <!-- Encabezado de la Ficha -->
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-secondary rounded">
          <div
            class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-sm flex-shrink-0"
            style="width: 52px; height: 52px; font-size: 1.25rem;"
          >
            {{ getUserInitials(detailRecord.name) }}
          </div>
          <div class="flex-grow-1 min-w-0">
            <h5 class="fw-bold mb-0 text-body text-truncate">
              {{ detailRecord.persona ? detailRecord.persona.nombre_completo : detailRecord.name }}
            </h5>
            <span class="badge bg-body text-muted border fw-normal font-monospace mt-1">
              Usuario ID #{{ detailRecord.id }}
            </span>
          </div>
          <div class="ms-auto flex-shrink-0">
            <TfBadge :variant="getStatusVariant(detailRecord.status)" :dot="true">
              {{ formatStatusLabel(detailRecord.status) }}
            </TfBadge>
          </div>
        </div>

        <!-- 4 Tarjetas de Información Desahogadas -->
        <div class="row g-3">
          <!-- Tarjeta 1: Identidad Humana (Persona) -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-address-card text-primary" aria-hidden="true"></i>
                Identidad Humana (Persona)
              </h6>
              <div v-if="detailRecord.persona">
                <div class="mb-2">
                  <span class="text-muted small d-block">Nombre completo:</span>
                  <span class="fw-semibold text-body">{{ detailRecord.persona.nombre_completo }}</span>
                </div>
                <div class="mb-2">
                  <span class="text-muted small d-block">Documento:</span>
                  <span class="font-monospace text-body">{{ detailRecord.persona.tipo_documento || 'DOC' }}: {{ detailRecord.persona.numero_documento || '—' }}</span>
                </div>
                <div>
                  <span class="text-muted small d-block">Correo de contacto biográfico:</span>
                  <span class="text-body small">{{ detailRecord.persona.email || '—' }}</span>
                </div>
              </div>
              <div v-else class="text-muted small">
                Sin identidad de persona vinculada.
              </div>
            </div>
          </div>

          <!-- Tarjeta 2: Cuenta de Acceso Digital -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-shield-halved text-primary" aria-hidden="true"></i>
                Cuenta de Acceso Digital
              </h6>
              <div class="mb-2">
                <span class="text-muted small d-block">Email de acceso:</span>
                <span class="font-monospace fw-bold text-body">{{ detailRecord.email }}</span>
              </div>
              <div class="mb-2">
                <span class="text-muted small d-block">Estado operativo:</span>
                <TfBadge :variant="getStatusVariant(detailRecord.status)" :dot="true">
                  {{ formatStatusLabel(detailRecord.status) }}
                </TfBadge>
              </div>
              <div>
                <span class="text-muted small d-block">Seguridad de sesión:</span>
                <span class="badge bg-success-subtle text-success-emphasis border">Sesión segura HttpOnly</span>
              </div>
            </div>
          </div>

          <!-- Tarjeta 3: Roles y Permisos Asignados -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-lock text-primary" aria-hidden="true"></i>
                Roles y Permisos Efectivos
              </h6>
              <div class="mb-2">
                <span class="text-muted small d-block mb-1">Roles:</span>
                <div v-if="detailRecord.roles && detailRecord.roles.length > 0" class="d-flex flex-wrap gap-1">
                  <span
                    v-for="r in detailRecord.roles"
                    :key="r.id"
                    class="badge bg-primary text-white border"
                  >
                    {{ r.name }}
                  </span>
                </div>
                <span v-else class="text-muted small">— Sin roles asignados —</span>
              </div>
              <div v-if="detailRecord.permissions && detailRecord.permissions.length > 0">
                <span class="text-muted small d-block mb-1">Permisos efectivos ({{ detailRecord.permissions.length }}):</span>
                <div class="d-flex flex-wrap gap-1" style="max-height: 80px; overflow-y: auto;">
                  <span
                    v-for="p in detailRecord.permissions"
                    :key="p"
                    class="badge bg-body-secondary text-body font-monospace border small"
                  >
                    {{ p }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tarjeta 4: Trazabilidad y Acceso -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-primary" aria-hidden="true"></i>
                Trazabilidad y Acceso
              </h6>
              <div class="mb-2">
                <span class="text-muted small d-block">Último inicio de sesión:</span>
                <span class="small text-body fw-semibold">{{ formatDate(detailRecord.last_login_at) }}</span>
              </div>
              <div class="mb-2">
                <span class="text-muted small d-block">Fecha de creación:</span>
                <span class="small text-body">{{ formatDate(detailRecord.created_at) }}</span>
              </div>
              <div>
                <span class="text-muted small d-block">Última modificación:</span>
                <span class="small text-body">{{ formatDate(detailRecord.updated_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <TfButton variant="secondary" @click="showDetailModal = false">
          Cerrar
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 4: Administrar Roles (size="md") -->
    <TfModal
      v-model="showRolesModal"
      title="Administrar Roles de Usuario"
      size="md"
      :loading="saving"
    >
      <div v-if="loadingRecord" class="py-3">
        <TfSkeleton height="40px" class="mb-2" />
        <TfSkeleton height="40px" />
      </div>
      <form v-else id="roles-user-form" @submit.prevent="submitRolesUser">
        <p class="small text-muted mb-3">
          Asigne o retire roles a la cuenta de <strong>{{ activeUser?.email }}</strong>. Solo se muestran los roles autorizados por la regla anti-escalada.
        </p>

        <div v-if="rolesDisponibles.length === 0" class="alert alert-warning small">
          No posee privilegios suficientes para asignar roles a este usuario.
        </div>
        <div v-else class="d-flex flex-column gap-2">
          <div
            v-for="r in rolesDisponibles"
            :key="r.id"
            class="form-check p-2 border rounded bg-body-tertiary"
          >
            <input
              :id="`manage-role-${r.id}`"
              v-model="rolesForm.roles"
              class="form-check-input ms-0 me-2"
              type="checkbox"
              :value="r.id"
              :disabled="saving"
            />
            <label :for="`manage-role-${r.id}`" class="form-check-label fw-semibold text-body">
              {{ r.name }}
            </label>
            <small class="d-block text-muted ps-4">{{ r.description || r.slug }}</small>
          </div>
        </div>

        <div v-if="rolesErrors.roles" class="text-danger small mt-2">
          {{ rolesErrors.roles[0] }}
        </div>
      </form>

      <template #footer>
        <TfButton variant="secondary" :disabled="saving" @click="showRolesModal = false">
          Cancelar
        </TfButton>
        <TfButton variant="primary" :loading="saving" form="roles-user-form" type="submit">
          <template #icon>
            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>
          </template>
          Actualizar roles
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 5: Modificar Estado (size="md") -->
    <TfModal
      v-model="showEstadoModal"
      title="Modificar Estado de Cuenta"
      size="md"
      :loading="saving"
    >
      <form id="estado-user-form" @submit.prevent="submitEstadoUser">
        <p class="small text-muted mb-3">
          Actualice el estado de acceso de <strong>{{ activeUser?.email }}</strong>.
        </p>

        <div class="mb-3">
          <TfSelect
            id="modal-estado"
            v-model="estadoForm.status"
            label="Estado de la cuenta"
            :options="statusOptions"
            :error-message="estadoErrors.status?.[0]"
            :disabled="saving"
          />
        </div>

        <div v-if="estadoForm.status !== 'active'" class="alert alert-warning small mb-0">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          <strong>Atención:</strong> Al inactivar o bloquear esta cuenta se revocarán de inmediato todas sus sesiones activas en el sistema.
        </div>
      </form>

      <template #footer>
        <TfButton variant="secondary" :disabled="saving" @click="showEstadoModal = false">
          Cancelar
        </TfButton>
        <TfButton variant="warning" :loading="saving" form="estado-user-form" type="submit">
          <template #icon>
            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>
          </template>
          Guardar estado
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 6: Restablecer Contraseña (size="md") -->
    <TfModal
      v-model="showPasswordModal"
      title="Restablecer Contraseña de Usuario"
      size="md"
      :loading="saving"
    >
      <form id="password-user-form" @submit.prevent="submitPasswordUser">
        <p class="small text-muted mb-3">
          Defina una nueva contraseña segura para <strong>{{ activeUser?.email }}</strong>.
        </p>

        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="modal-new-password" class="form-label fw-semibold mb-0">
              Nueva contraseña <span class="text-danger">*</span>
            </label>
            <button
              type="button"
              class="btn btn-link btn-sm p-0 text-decoration-none"
              @click="generateAndFillPassword('reset')"
            >
              <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generar segura
            </button>
          </div>
          <input
            id="modal-new-password"
            v-model="passwordForm.password"
            type="text"
            class="form-control font-monospace"
            :class="{ 'is-invalid': passwordErrors.password }"
            placeholder="Mínimo 12 caracteres..."
            required
            :disabled="saving"
          />
          <div v-if="passwordErrors.password" class="invalid-feedback d-block">
            {{ passwordErrors.password[0] }}
          </div>
        </div>

        <div class="mb-3">
          <TfInput
            id="modal-confirm-password"
            v-model="passwordForm.password_confirmation"
            type="text"
            label="Confirmar nueva contraseña"
            placeholder="Repetir nueva contraseña..."
            :required="true"
            :disabled="saving"
          />
        </div>

        <div class="alert alert-info small mb-0">
          <i class="fa-solid fa-shield-halved me-1"></i>
          Al cambiar la contraseña se cerrarán automáticamente todas las sesiones abiertas de este usuario.
        </div>
      </form>

      <template #footer>
        <TfButton variant="secondary" :disabled="saving" @click="showPasswordModal = false">
          Cancelar
        </TfButton>
        <TfButton variant="danger" :loading="saving" form="password-user-form" type="submit">
          <template #icon>
            <i class="fa-solid fa-key me-1" aria-hidden="true"></i>
          </template>
          Restablecer clave
        </TfButton>
      </template>
    </TfModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';
import { useApi, ApiError } from '@/composables/useApi';
import {
  TfButton,
  TfInput,
  TfSelect,
  TfAlert,
  TfSkeleton,
  TfBadge,
  TfTable,
  TfPagination,
  TfModal,
  type TableHeader,
  type SelectOption,
} from '@/design-system';

interface PersonaSummary {
  id: number;
  nombre_completo: string;
  tipo_documento: string | null;
  numero_documento: string | null;
  email: string | null;
  telefono?: string | null;
  estado?: string;
}

interface RoleItem {
  id: number;
  name: string;
  slug: string;
  description?: string;
  is_system?: boolean;
}

interface UsuarioItem {
  id: number;
  name: string;
  email: string;
  status: 'active' | 'inactive' | 'blocked';
  persona_id: number | null;
  persona: PersonaSummary | null;
  roles: RoleItem[];
  permissions?: string[];
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
}

const authStore = useAuthStore();
const api = useApi();

// Estado reactivo de la colección
const usuarios = ref<UsuarioItem[]>([]);
const loading = ref<boolean>(false);
const fetchError = ref<string | null>(null);

// Búsqueda y Ordenamiento
const searchQuery = ref<string>('');
const activeSearch = ref<string>('');
const sortColumn = ref<string>('id');
const sortDirection = ref<'asc' | 'desc'>('desc');

// Paginación
const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 15,
});

// Columnas de la tabla
const tableHeaders: TableHeader[] = [
  { key: 'usuario', label: 'Usuario / Persona', sortable: true },
  { key: 'email', label: 'Email de acceso', sortable: true },
  { key: 'roles', label: 'Roles' },
  { key: 'status', label: 'Estado', sortable: true },
  { key: 'last_login_at', label: 'Último acceso', sortable: true },
];

const statusOptions: SelectOption[] = [
  { value: 'active', label: 'Activo' },
  { value: 'inactive', label: 'Inactivo' },
  { value: 'blocked', label: 'Bloqueado' },
];

// Modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailModal = ref(false);
const showRolesModal = ref(false);
const showEstadoModal = ref(false);
const showPasswordModal = ref(false);

const saving = ref(false);
const loadingRecord = ref(false);
const activeUser = ref<UsuarioItem | null>(null);
const detailRecord = ref<UsuarioItem | null>(null);

// Datos remotos de soporte
const personasElegibles = ref<PersonaSummary[]>([]);
const loadingPersonasElegibles = ref(false);
const rolesDisponibles = ref<RoleItem[]>([]);
const loadingRolesDisponibles = ref(false);

// Opciones de personas para el modal de edición
const editPersonaOptions = ref<PersonaSummary[]>([]);

// Formularios
const createForm = reactive({
  persona_id: '' as number | '',
  email: '',
  password: '',
  password_confirmation: '',
  status: 'active' as 'active' | 'inactive' | 'blocked',
  roles: [] as number[],
});
const createErrors = ref<Record<string, string[]>>({});

const editForm = reactive({
  persona_id: '' as number | '',
  name: '',
  email: '',
});
const editErrors = ref<Record<string, string[]>>({});

const rolesForm = reactive({
  roles: [] as number[],
});
const rolesErrors = ref<Record<string, string[]>>({});

const estadoForm = reactive({
  status: 'active' as 'active' | 'inactive' | 'blocked',
});
const estadoErrors = ref<Record<string, string[]>>({});

const passwordForm = reactive({
  password: '',
  password_confirmation: '',
});
const passwordErrors = ref<Record<string, string[]>>({});

/**
 * Consulta listado paginado de usuarios
 */
async function fetchUsuarios(page = 1) {
  loading.value = true;
  fetchError.value = null;

  try {
    const params: Record<string, string | number> = {
      page,
      per_page: pagination.perPage,
      sort: sortColumn.value,
      direction: sortDirection.value,
    };

    if (activeSearch.value) {
      params.search = activeSearch.value;
    }

    const response = await api.get<{
      data: UsuarioItem[];
      meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
      };
    }>('/api/admin/usuarios', { params });

    usuarios.value = response.data || [];
    if (response.meta) {
      pagination.currentPage = response.meta.current_page;
      pagination.lastPage = response.meta.last_page;
      pagination.total = response.meta.total;
      pagination.perPage = response.meta.per_page;
    }
  } catch (err: any) {
    if (err instanceof ApiError) {
      fetchError.value = err.data.message || `Error HTTP ${err.status} al consultar usuarios.`;
    } else {
      fetchError.value = 'No se pudo conectar con el servidor para obtener el listado de usuarios.';
    }
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  activeSearch.value = searchQuery.value.trim();
  pagination.currentPage = 1;
  fetchUsuarios(1);
}

function clearSearch() {
  searchQuery.value = '';
  activeSearch.value = '';
  pagination.currentPage = 1;
  fetchUsuarios(1);
}

function handleSort(key: string) {
  if (sortColumn.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = key;
    sortDirection.value = 'desc';
  }
  fetchUsuarios(pagination.currentPage);
}

function handlePageChange(page: number) {
  pagination.currentPage = page;
  fetchUsuarios(page);
}

/**
 * Generador Criptográfico Seguro de Contraseñas (Sin Math.random)
 */
function generateSecurePassword(length = 16): string {
  const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
  const array = new Uint8Array(length);
  window.crypto.getRandomValues(array);
  let password = '';
  for (let i = 0; i < length; i++) {
    password += charset[array[i] % charset.length];
  }
  return password;
}

function generateAndFillPassword(target: 'create' | 'reset') {
  const generated = generateSecurePassword(16);
  if (target === 'create') {
    createForm.password = generated;
    createForm.password_confirmation = generated;
  } else {
    passwordForm.password = generated;
    passwordForm.password_confirmation = generated;
  }

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'info',
    title: 'Contraseña generada en el formulario',
    showConfirmButton: false,
    timer: 2500,
  });
}

/**
 * Carga remota de soporte para modales
 */
async function loadPersonasElegibles() {
  loadingPersonasElegibles.value = true;
  try {
    const res = await api.get<{ data: PersonaSummary[] }>('/api/admin/usuarios/personas-elegibles');
    personasElegibles.value = res.data || [];
  } catch {
    personasElegibles.value = [];
  } finally {
    loadingPersonasElegibles.value = false;
  }
}

async function loadRolesDisponibles() {
  if (!authStore.hasPermission('usuarios.roles')) return;
  loadingRolesDisponibles.value = true;
  try {
    const res = await api.get<{ data: RoleItem[] }>('/api/admin/usuarios/roles-disponibles');
    rolesDisponibles.value = res.data || [];
  } catch {
    rolesDisponibles.value = [];
  } finally {
    loadingRolesDisponibles.value = false;
  }
}

/**
 * MODAL 1: Crear Usuario
 */
async function openCreateModal() {
  createForm.persona_id = '';
  createForm.email = '';
  createForm.password = '';
  createForm.password_confirmation = '';
  createForm.status = 'active';
  createForm.roles = [];
  createErrors.value = {};

  showCreateModal.value = true;
  await loadPersonasElegibles();
  await loadRolesDisponibles();
}

async function submitCreateUser() {
  saving.value = true;
  createErrors.value = {};

  try {
    await api.post('/api/admin/usuarios', createForm);
    showCreateModal.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Usuario registrado correctamente',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });

    fetchUsuarios(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      createErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error de registro',
        text: err?.message || 'No fue posible registrar la cuenta de usuario.',
      });
    }
  } finally {
    saving.value = false;
  }
}

/**
 * MODAL 2: Editar Información Base
 */
async function openEditModal(id: number) {
  editErrors.value = {};
  showEditModal.value = true;
  loadingRecord.value = true;

  try {
    const [userRes, personasRes] = await Promise.all([
      api.get<{ data: UsuarioItem }>(`/api/admin/usuarios/${id}`),
      api.get<{ data: PersonaSummary[] }>('/api/admin/usuarios/personas-elegibles'),
    ]);

    const user = userRes.data;
    activeUser.value = user;
    editForm.persona_id = user.persona_id || '';
    editForm.name = user.name;
    editForm.email = user.email;

    // Incluir la persona actualmente vinculada en las opciones de selección
    const elegibles = personasRes.data || [];
    if (user.persona && !elegibles.some(p => p.id === user.persona?.id)) {
      editPersonaOptions.value = [user.persona, ...elegibles];
    } else {
      editPersonaOptions.value = elegibles;
    }
  } catch (err: any) {
    showEditModal.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Error al recuperar usuario',
      text: err?.message || 'No se pudieron cargar los datos de la cuenta.',
    });
  } finally {
    loadingRecord.value = false;
  }
}

async function submitEditUser() {
  if (!activeUser.value) return;
  saving.value = true;
  editErrors.value = {};

  try {
    await api.put(`/api/admin/usuarios/${activeUser.value.id}`, editForm);
    showEditModal.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Usuario actualizado correctamente',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });

    fetchUsuarios(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      editErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error de actualización',
        text: err?.message || 'No fue posible actualizar los datos de la cuenta.',
      });
    }
  } finally {
    saving.value = false;
  }
}

/**
 * MODAL 3: Ficha de Detalle
 */
async function openDetailModal(id: number) {
  detailRecord.value = null;
  showDetailModal.value = true;
  loadingRecord.value = true;

  try {
    const res = await api.get<{ data: UsuarioItem }>(`/api/admin/usuarios/${id}`);
    detailRecord.value = res.data;
  } catch (err: any) {
    showDetailModal.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Error al consultar',
      text: err?.message || 'No se pudo obtener la información del usuario.',
    });
  } finally {
    loadingRecord.value = false;
  }
}

/**
 * MODAL 4: Administrar Roles
 */
async function openRolesModal(user: UsuarioItem) {
  activeUser.value = user;
  rolesForm.roles = user.roles ? user.roles.map(r => r.id) : [];
  rolesErrors.value = {};
  showRolesModal.value = true;
  loadingRecord.value = true;

  try {
    await loadRolesDisponibles();
  } finally {
    loadingRecord.value = false;
  }
}

async function submitRolesUser() {
  if (!activeUser.value) return;
  saving.value = true;
  rolesErrors.value = {};

  try {
    await api.put(`/api/admin/usuarios/${activeUser.value.id}/roles`, rolesForm);
    showRolesModal.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Roles actualizados correctamente',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });

    fetchUsuarios(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      rolesErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al asignar roles',
        text: err?.message || 'No fue posible actualizar los roles.',
      });
    }
  } finally {
    saving.value = false;
  }
}

/**
 * MODAL 5: Cambiar Estado
 */
function openEstadoModal(user: UsuarioItem) {
  activeUser.value = user;
  estadoForm.status = user.status;
  estadoErrors.value = {};
  showEstadoModal.value = true;
}

async function submitEstadoUser() {
  if (!activeUser.value) return;
  saving.value = true;
  estadoErrors.value = {};

  try {
    await api.patch(`/api/admin/usuarios/${activeUser.value.id}/estado`, estadoForm);
    showEstadoModal.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Estado de cuenta actualizado',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });

    fetchUsuarios(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      estadoErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al cambiar estado',
        text: err?.message || 'No fue posible actualizar el estado.',
      });
    }
  } finally {
    saving.value = false;
  }
}

/**
 * MODAL 6: Restablecer Contraseña
 */
function openPasswordModal(user: UsuarioItem) {
  activeUser.value = user;
  passwordForm.password = '';
  passwordForm.password_confirmation = '';
  passwordErrors.value = {};
  showPasswordModal.value = true;
}

async function submitPasswordUser() {
  if (!activeUser.value) return;
  saving.value = true;
  passwordErrors.value = {};

  try {
    await api.put(`/api/admin/usuarios/${activeUser.value.id}/password`, passwordForm);
    showPasswordModal.value = false;

    Swal.fire({
      icon: 'success',
      title: 'Contraseña restablecida',
      text: 'La nueva contraseña fue guardada y todas las sesiones previas del usuario fueron revocadas.',
    });

    fetchUsuarios(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      passwordErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al restablecer contraseña',
        text: err?.message || 'No fue posible cambiar la contraseña.',
      });
    }
  } finally {
    saving.value = false;
  }
}

// Helpers de presentación
function getUserInitials(name?: string): string {
  if (!name) return 'U';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

function getStatusVariant(status: string): 'success' | 'warning' | 'danger' {
  switch (status) {
    case 'active':
      return 'success';
    case 'inactive':
      return 'warning';
    case 'blocked':
      return 'danger';
    default:
      return 'warning';
  }
}

function formatStatusLabel(status: string): string {
  switch (status) {
    case 'active':
      return 'Activo';
    case 'inactive':
      return 'Inactivo';
    case 'blocked':
      return 'Bloqueado';
    default:
      return status;
  }
}

function formatDate(isoDateString?: string | null): string {
  if (!isoDateString) return '—';
  try {
    const d = new Date(isoDateString);
    return d.toLocaleString('es-PE', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return isoDateString;
  }
}

onMounted(() => {
  fetchUsuarios(1);
});
</script>
