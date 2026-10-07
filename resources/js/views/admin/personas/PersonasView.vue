<template>
  <div class="tf-personas-view">
    <!-- Encabezado de la Vista -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1 text-body">Personas</h2>
        <p class="text-muted small mb-0">
          Gestión del registro de identidades humanas y datos biográficos de TravelFlow CMS.
        </p>
      </div>
      <div>
        <TfButton
          v-if="authStore.hasPermission('personas.crear')"
          variant="primary"
          @click="openCreateModal"
        >
          <template #icon>
            <i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i>
          </template>
          Nueva persona
        </TfButton>
      </div>
    </div>

    <!-- Alerta de Error de Carga Principal (con botón reintentar) -->
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
          @click="fetchPersonas(pagination.currentPage)"
        >
          <i class="fa-solid fa-rotate-right me-1" aria-hidden="true"></i> Reintentar
        </button>
      </div>
    </TfAlert>

    <!-- Tarjeta Principal con Búsqueda y Tabla -->
    <div class="card shadow-sm border-0 mb-4">
      <!-- Barra de Filtros y Búsqueda (Toolbar de ancho completo optimizado) -->
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
              placeholder="Buscar por nombre, documento, correo electrónico o teléfono..."
              aria-label="Buscar personas"
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
          :items="personas"
          :loading="loading"
          :skeleton-rows="5"
          :empty-title="activeSearch ? 'No se encontraron resultados' : 'No hay personas registradas'"
          :empty-description="activeSearch ? `No se encontraron coincidencias para &quot;${activeSearch}&quot;.` : 'Comience registrando una nueva persona en el sistema.'"
          :current-sort="sortColumn"
          :current-direction="sortDirection"
          @sort="handleSort"
        >
          <!-- Celda: Persona (Nombre Completo) -->
          <template #cell-persona="{ item }">
            <div class="d-flex align-items-center gap-2">
              <div
                class="rounded-circle bg-label-primary text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                style="width: 38px; height: 38px; font-size: 0.85rem;"
                aria-hidden="true"
              >
                {{ getInitials(item.nombres, item.apellidos) }}
              </div>
              <div>
                <span class="fw-semibold text-body d-block">{{ item.nombre_completo }}</span>
                <small class="text-muted font-monospace">ID #{{ item.id }}</small>
              </div>
            </div>
          </template>

          <!-- Celda: Documento -->
          <template #cell-documento="{ item }">
            <div v-if="item.numero_documento">
              <span class="badge bg-label-secondary me-1">
                {{ item.tipo_documento || 'DOC' }}
              </span>
              <span class="font-monospace small">{{ item.numero_documento }}</span>
            </div>
            <span v-else class="text-muted small">—</span>
          </template>

          <!-- Celda: Contacto -->
          <template #cell-contacto="{ item }">
            <div>
              <div v-if="item.email" class="small text-truncate" style="max-width: 220px;">
                <i class="fa-regular fa-envelope text-muted me-1" aria-hidden="true"></i>
                <a :href="`mailto:${item.email}`" class="text-decoration-none text-body">{{ item.email }}</a>
              </div>
              <div v-if="item.telefono" class="small text-muted">
                <i class="fa-solid fa-phone text-muted me-1" aria-hidden="true"></i>
                {{ item.telefono }}
              </div>
              <span v-if="!item.email && !item.telefono" class="text-muted small">—</span>
            </div>
          </template>

          <!-- Celda: Estado -->
          <template #cell-estado="{ item }">
            <TfBadge
              :variant="getStatusVariant(item.estado)"
              :dot="true"
            >
              {{ formatStatusLabel(item.estado) }}
            </TfBadge>
          </template>

          <!-- Celda: Cuenta de acceso vinculada (Terminología Oficial) -->
          <template #cell-has_user="{ item }">
            <span
              v-if="item.has_user"
              class="badge bg-label-info"
              title="Esta persona posee una cuenta de acceso vinculada"
            >
              <i class="fa-solid fa-user-check me-1" aria-hidden="true"></i>Cuenta activa
            </span>
            <span
              v-else
              class="text-muted small"
              title="Sin cuenta de acceso asociada"
            >
              Sin cuenta
            </span>
          </template>

          <!-- Acciones por Fila con Área Clicable y Accesibilidad Optimizadas -->
          <template #actions="{ item }">
            <div class="d-flex justify-content-end gap-1" role="group" aria-label="Acciones de persona">
              <!-- Ver Detalle -->
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Ver ficha de persona"
                aria-label="Ver ficha de persona"
                @click="openDetailModal(item.id)"
              >
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Ver</span>
              </button>

              <!-- Editar Persona -->
              <button
                v-if="authStore.hasPermission('personas.editar')"
                type="button"
                class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Editar datos de persona"
                aria-label="Editar datos de persona"
                @click="openEditModal(item.id)"
              >
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Editar</span>
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

    <!-- MODAL 1: Crear Nueva Persona (Grid Balanceado 50/50) -->
    <TfModal
      v-model="showCreateModal"
      title="Registrar Nueva Persona"
      size="lg"
      :loading="saving"
    >
      <form id="create-persona-form" @submit.prevent="submitCreatePersona">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <TfInput
              id="create-nombres"
              v-model="createForm.nombres"
              label="Nombres"
              placeholder="Ej. Carlos Alberto"
              :required="true"
              :error-message="createErrors.nombres?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="create-apellidos"
              v-model="createForm.apellidos"
              label="Apellidos"
              placeholder="Ej. Quispe Morales"
              :required="true"
              :error-message="createErrors.apellidos?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfSelect
              id="create-tipo-doc"
              v-model="createForm.tipo_documento"
              label="Tipo de documento"
              placeholder="Seleccionar..."
              :options="documentTypeOptions"
              :error-message="createErrors.tipo_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="create-num-doc"
              v-model="createForm.numero_documento"
              label="Número de documento"
              placeholder="Ej. 10203040"
              :error-message="createErrors.numero_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="create-email"
              v-model="createForm.email"
              type="email"
              label="Correo electrónico (opcional)"
              placeholder="correo@ejemplo.com"
              prefix-icon="fa-regular fa-envelope"
              :error-message="createErrors.email?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="create-telefono"
              v-model="createForm.telefono"
              label="Teléfono / WhatsApp (opcional)"
              placeholder="+51 987 654 321"
              prefix-icon="fa-solid fa-phone"
              :error-message="createErrors.telefono?.[0]"
              :disabled="saving"
            />
          </div>
        </div>
      </form>

      <template #footer>
        <TfButton
          variant="secondary"
          :disabled="saving"
          @click="showCreateModal = false"
        >
          Cancelar
        </TfButton>
        <TfButton
          variant="primary"
          :loading="saving"
          form="create-persona-form"
          type="submit"
        >
          <template #icon>
            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>
          </template>
          Guardar persona
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 2: Editar Persona (Grid Balanceado 50/50 y Etiquetas Humanas) -->
    <TfModal
      v-model="showEditModal"
      title="Editar Persona"
      size="lg"
      :loading="saving"
    >
      <!-- Skeleton mientras recupera datos remotos -->
      <div v-if="loadingRecord" class="py-3">
        <div class="row g-3">
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
          <div class="col-12 col-md-6"><TfSkeleton height="40px" /></div>
        </div>
      </div>

      <!-- Formulario de Edición -->
      <form v-else id="edit-persona-form" @submit.prevent="submitEditPersona">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <TfInput
              id="edit-nombres"
              v-model="editForm.nombres"
              label="Nombres"
              :required="true"
              :error-message="editErrors.nombres?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-apellidos"
              v-model="editForm.apellidos"
              label="Apellidos"
              :required="true"
              :error-message="editErrors.apellidos?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfSelect
              id="edit-tipo-doc"
              v-model="editForm.tipo_documento"
              label="Tipo de documento"
              placeholder="Seleccionar..."
              :options="documentTypeOptions"
              :error-message="editErrors.tipo_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-num-doc"
              v-model="editForm.numero_documento"
              label="Número de documento"
              :error-message="editErrors.numero_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-email"
              v-model="editForm.email"
              type="email"
              label="Correo electrónico (opcional)"
              prefix-icon="fa-regular fa-envelope"
              :error-message="editErrors.email?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-telefono"
              v-model="editForm.telefono"
              label="Teléfono / WhatsApp (opcional)"
              prefix-icon="fa-solid fa-phone"
              :error-message="editErrors.telefono?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfSelect
              id="edit-estado"
              v-model="editForm.estado"
              label="Estado"
              :required="true"
              :options="statusOptions"
              :error-message="editErrors.estado?.[0]"
              :disabled="saving"
            />
          </div>
        </div>
      </form>

      <template #footer>
        <TfButton
          variant="secondary"
          :disabled="saving"
          @click="showEditModal = false"
        >
          Cancelar
        </TfButton>
        <TfButton
          variant="primary"
          :loading="saving"
          form="edit-persona-form"
          type="submit"
        >
          <template #icon>
            <i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>
          </template>
          Actualizar persona
        </TfButton>
      </template>
    </TfModal>

    <!-- MODAL 3: Ficha de Detalle de Persona (Modal Amplio size="lg" con Distribución en Tarjetas) -->
    <TfModal
      v-model="showDetailModal"
      title="Ficha de Identidad de Persona"
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
        <!-- Encabezado de la Ficha con Avatar e Identidad -->
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-secondary rounded">
          <div
            class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-sm flex-shrink-0"
            style="width: 52px; height: 52px; font-size: 1.25rem;"
          >
            {{ getInitials(detailRecord.nombres, detailRecord.apellidos) }}
          </div>
          <div class="flex-grow-1 min-w-0">
            <h5 class="fw-bold mb-0 text-body text-truncate">{{ detailRecord.nombre_completo }}</h5>
            <span class="badge bg-body text-muted border fw-normal font-monospace mt-1">
              ID #{{ detailRecord.id }}
            </span>
          </div>
          <div class="ms-auto flex-shrink-0">
            <TfBadge :variant="getStatusVariant(detailRecord.estado)" :dot="true">
              {{ formatStatusLabel(detailRecord.estado) }}
            </TfBadge>
          </div>
        </div>

        <!-- Grilla de Información en 2 Columnas Desahogadas -->
        <div class="row g-3">
          <!-- Tarjeta 1: Documento de Identidad -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-id-card text-primary" aria-hidden="true"></i>
                Documento de Identidad
              </h6>
              <div class="mb-2">
                <span class="text-muted small d-block">Tipo:</span>
                <span class="fw-semibold text-body">{{ detailRecord.tipo_documento || 'No especificado' }}</span>
              </div>
              <div>
                <span class="text-muted small d-block">Número:</span>
                <span class="font-monospace fw-bold text-body fs-6">{{ detailRecord.numero_documento || 'Sin número' }}</span>
              </div>
            </div>
          </div>

          <!-- Tarjeta 2: Contacto -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-address-book text-primary" aria-hidden="true"></i>
                Información de Contacto
              </h6>
              <div class="mb-2">
                <span class="text-muted small d-block">Correo electrónico:</span>
                <a v-if="detailRecord.email" :href="`mailto:${detailRecord.email}`" class="text-decoration-none fw-semibold">
                  {{ detailRecord.email }}
                </a>
                <span v-else class="text-muted small">Sin correo registrado</span>
              </div>
              <div>
                <span class="text-muted small d-block">Teléfono / WhatsApp:</span>
                <span v-if="detailRecord.telefono" class="fw-semibold text-body">{{ detailRecord.telefono }}</span>
                <span v-else class="text-muted small">Sin teléfono registrado</span>
              </div>
            </div>
          </div>

          <!-- Tarjeta 3: Cuenta de Acceso (Terminología Humana) -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-shield-halved text-primary" aria-hidden="true"></i>
                Cuenta de Acceso
              </h6>
              <div v-if="detailRecord.has_user">
                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1">
                  <i class="fa-solid fa-check-circle me-1" aria-hidden="true"></i>Cuenta de acceso vinculada
                </span>
                <small class="text-muted d-block mt-2">Esta persona posee credenciales activas de acceso al sistema.</small>
              </div>
              <div v-else>
                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2 py-1">
                  <i class="fa-solid fa-circle-xmark me-1" aria-hidden="true"></i>Sin cuenta de acceso
                </span>
                <small class="text-muted d-block mt-2">Identidad biográfica sin credenciales asignadas.</small>
              </div>
            </div>
          </div>

          <!-- Tarjeta 4: Trazabilidad de Registro -->
          <div class="col-12 col-md-6">
            <div class="p-3 rounded border bg-body-tertiary h-100">
              <h6 class="fw-bold text-muted small text-uppercase mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-primary" aria-hidden="true"></i>
                Trazabilidad de Registro
              </h6>
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

interface PersonaItem {
  id: number;
  nombres: string;
  apellidos: string;
  nombre_completo: string;
  tipo_documento: string | null;
  numero_documento: string | null;
  email: string | null;
  telefono: string | null;
  estado: 'activo' | 'inactivo' | 'archivado';
  has_user: boolean;
  created_at: string;
  updated_at: string;
}

const authStore = useAuthStore();
const api = useApi();

// Estado reactivo del listado
const personas = ref<PersonaItem[]>([]);
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

// Columnas de la tabla (Terminología Oficial Refinada)
const tableHeaders: TableHeader[] = [
  { key: 'persona', label: 'Persona', sortable: true },
  { key: 'documento', label: 'Documento' },
  { key: 'contacto', label: 'Contacto' },
  { key: 'has_user', label: 'Cuenta de acceso' },
  { key: 'estado', label: 'Estado', sortable: true },
];

// Opciones estándar para Tipo de Documento
const documentTypeOptions: SelectOption[] = [
  { value: 'DNI', label: 'DNI — Documento Nacional de Identidad' },
  { value: 'Pasaporte', label: 'Pasaporte' },
  { value: 'Carnet de Extranjería', label: 'Carnet de Extranjería (CE)' },
  { value: 'RUC', label: 'RUC' },
  { value: 'Otro', label: 'Otro Documento' },
];

// Opciones de Estados según PersonaStatus Enum
const statusOptions: SelectOption[] = [
  { value: 'activo', label: 'Activo' },
  { value: 'inactivo', label: 'Inactivo' },
  { value: 'archivado', label: 'Archivado' },
];

// Estado de Modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailModal = ref(false);

const saving = ref(false);
const loadingRecord = ref(false);
const editingId = ref<number | null>(null);
const detailRecord = ref<PersonaItem | null>(null);

// Formularios y Errores
const createForm = reactive({
  nombres: '',
  apellidos: '',
  tipo_documento: '',
  numero_documento: '',
  email: '',
  telefono: '',
});
const createErrors = ref<Record<string, string[]>>({});

const editForm = reactive({
  nombres: '',
  apellidos: '',
  tipo_documento: '',
  numero_documento: '',
  email: '',
  telefono: '',
  estado: 'activo' as 'activo' | 'inactivo' | 'archivado',
});
const editErrors = ref<Record<string, string[]>>({});

/**
 * Consulta listado paginado de personas al backend.
 */
async function fetchPersonas(page = 1) {
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
      data: PersonaItem[];
      meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
      };
    }>('/api/admin/personas', { params });

    personas.value = response.data || [];
    if (response.meta) {
      pagination.currentPage = response.meta.current_page;
      pagination.lastPage = response.meta.last_page;
      pagination.total = response.meta.total;
      pagination.perPage = response.meta.per_page;
    }
  } catch (err: any) {
    if (err instanceof ApiError) {
      fetchError.value = err.data.message || `Error HTTP ${err.status} al consultar personas.`;
    } else {
      fetchError.value = 'No se pudo conectar con el servidor para obtener el listado.';
    }
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  activeSearch.value = searchQuery.value.trim();
  pagination.currentPage = 1;
  fetchPersonas(1);
}

function clearSearch() {
  searchQuery.value = '';
  activeSearch.value = '';
  pagination.currentPage = 1;
  fetchPersonas(1);
}

function handleSort(key: string) {
  if (key === 'persona') {
    sortColumn.value = sortColumn.value === 'apellidos' ? 'nombres' : 'apellidos';
  } else {
    sortColumn.value = key;
  }
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  fetchPersonas(pagination.currentPage);
}

function handlePageChange(page: number) {
  pagination.currentPage = page;
  fetchPersonas(page);
}

/**
 * Apertura de Modal Crear
 */
function openCreateModal() {
  createForm.nombres = '';
  createForm.apellidos = '';
  createForm.tipo_documento = 'DNI';
  createForm.numero_documento = '';
  createForm.email = '';
  createForm.telefono = '';
  createErrors.value = {};
  showCreateModal.value = true;
}

/**
 * Enviar creación de Persona mediante POST asíncrono
 */
async function submitCreatePersona() {
  saving.value = true;
  createErrors.value = {};

  try {
    await api.post('/api/admin/personas', createForm);

    showCreateModal.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Persona registrada correctamente',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });

    fetchPersonas(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      createErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error de registro',
        text: err?.message || 'No fue posible registrar la persona.',
      });
    }
  } finally {
    saving.value = false;
  }
}

/**
 * Apertura de Modal Editar y precarga remota con TfSkeleton
 */
async function openEditModal(id: number) {
  editingId.value = id;
  editErrors.value = {};
  showEditModal.value = true;
  loadingRecord.value = true;

  try {
    const res = await api.get<{ data: PersonaItem }>(`/api/admin/personas/${id}`);
    const record = res.data;
    editForm.nombres = record.nombres;
    editForm.apellidos = record.apellidos;
    editForm.tipo_documento = record.tipo_documento || '';
    editForm.numero_documento = record.numero_documento || '';
    editForm.email = record.email || '';
    editForm.telefono = record.telefono || '';
    editForm.estado = record.estado;
  } catch (err: any) {
    showEditModal.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Error al recuperar registro',
      text: err?.message || 'No se pudieron cargar los datos de la persona.',
    });
  } finally {
    loadingRecord.value = false;
  }
}

/**
 * Enviar actualización de Persona mediante PUT asíncrono
 */
async function submitEditPersona() {
  if (!editingId.value) return;

  saving.value = true;
  editErrors.value = {};

  try {
    await api.put(`/api/admin/personas/${editingId.value}`, editForm);

    showEditModal.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Persona actualizada correctamente',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });

    fetchPersonas(pagination.currentPage);
  } catch (err: any) {
    if (err instanceof ApiError && err.data.errors) {
      editErrors.value = err.data.errors;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error de actualización',
        text: err?.message || 'No fue posible actualizar la persona.',
      });
    }
  } finally {
    saving.value = false;
  }
}

/**
 * Apertura de Modal Detalle (Ficha de Identidad)
 */
async function openDetailModal(id: number) {
  detailRecord.value = null;
  showDetailModal.value = true;
  loadingRecord.value = true;

  try {
    const res = await api.get<{ data: PersonaItem }>(`/api/admin/personas/${id}`);
    detailRecord.value = res.data;
  } catch (err: any) {
    showDetailModal.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Error al consultar',
      text: err?.message || 'No se pudo obtener la información de la persona.',
    });
  } finally {
    loadingRecord.value = false;
  }
}

// Helpers de Presentación
function getInitials(nombres: string, apellidos: string): string {
  const n = (nombres || '').trim();
  const a = (apellidos || '').trim();
  const part1 = n.length > 0 ? n[0] : '';
  const part2 = a.length > 0 ? a[0] : '';
  return (part1 + part2).toUpperCase() || 'P';
}

function getStatusVariant(estado: string): 'success' | 'warning' | 'secondary' {
  switch (estado) {
    case 'activo':
      return 'success';
    case 'inactivo':
      return 'warning';
    case 'archivado':
      return 'secondary';
    default:
      return 'secondary';
  }
}

function formatStatusLabel(estado: string): string {
  switch (estado) {
    case 'activo':
      return 'Activo';
    case 'inactivo':
      return 'Inactivo';
    case 'archivado':
      return 'Archivado';
    default:
      return estado;
  }
}

function formatDate(isoDateString?: string): string {
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
  fetchPersonas(1);
});
</script>
