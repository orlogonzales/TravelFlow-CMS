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
      <!-- Barra de Filtros y Búsqueda -->
      <div class="card-header bg-transparent border-bottom p-3">
        <form class="row g-2 align-items-center" @submit.prevent="handleSearch">
          <div class="col-12 col-md-6 col-lg-5">
            <div class="input-group">
              <span class="input-group-text bg-body text-muted border-end-0">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              </span>
              <input
                v-model="searchQuery"
                type="search"
                class="form-control border-start-0"
                placeholder="Buscar por nombre, documento, correo o teléfono..."
                aria-label="Buscar personas"
              />
              <button
                type="submit"
                class="btn btn-outline-primary"
                :disabled="loading"
              >
                Buscar
              </button>
              <button
                v-if="activeSearch"
                type="button"
                class="btn btn-outline-secondary"
                title="Limpiar búsqueda"
                @click="clearSearch"
              >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              </button>
            </div>
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
                class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
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
              <span class="badge bg-body-secondary text-body border me-1">
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

          <!-- Celda: Cuenta User vinculada -->
          <template #cell-has_user="{ item }">
            <span
              v-if="item.has_user"
              class="badge bg-info-subtle text-info-emphasis border border-info-subtle"
              title="Esta persona posee una cuenta de acceso User asociada"
            >
              <i class="fa-solid fa-user-check me-1" aria-hidden="true"></i>Cuenta activa
            </span>
            <span
              v-else
              class="text-muted small"
              title="Sin cuenta de usuario asociada"
            >
              Sin usuario
            </span>
          </template>

          <!-- Acciones por Fila -->
          <template #actions="{ item }">
            <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
              <!-- Ver Detalle -->
              <button
                type="button"
                class="btn btn-outline-secondary"
                title="Ver ficha detallada"
                aria-label="Ver ficha detallada"
                @click="openDetailModal(item.id)"
              >
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
              </button>

              <!-- Editar Persona -->
              <button
                v-if="authStore.hasPermission('personas.editar')"
                type="button"
                class="btn btn-outline-primary"
                title="Editar persona"
                aria-label="Editar persona"
                @click="openEditModal(item.id)"
              >
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
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

    <!-- MODAL 1: Crear Nueva Persona -->
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

          <div class="col-12 col-md-4">
            <TfSelect
              id="create-tipo-doc"
              v-model="createForm.tipo_documento"
              label="Tipo Documento"
              placeholder="Seleccionar..."
              :options="documentTypeOptions"
              :error-message="createErrors.tipo_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-8">
            <TfInput
              id="create-num-doc"
              v-model="createForm.numero_documento"
              label="Número de Documento"
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
              label="Correo Electrónico (Opcional)"
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
              label="Teléfono / WhatsApp (Opcional)"
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

    <!-- MODAL 2: Editar Persona -->
    <TfModal
      v-model="showEditModal"
      title="Editar Persona"
      size="lg"
      :loading="saving"
    >
      <!-- Skeleton mientras recupera datos remotos -->
      <div v-if="loadingRecord" class="py-3">
        <div class="row g-3">
          <div class="col-6"><TfSkeleton height="40px" /></div>
          <div class="col-6"><TfSkeleton height="40px" /></div>
          <div class="col-4"><TfSkeleton height="40px" /></div>
          <div class="col-8"><TfSkeleton height="40px" /></div>
          <div class="col-6"><TfSkeleton height="40px" /></div>
          <div class="col-6"><TfSkeleton height="40px" /></div>
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

          <div class="col-12 col-md-4">
            <TfSelect
              id="edit-tipo-doc"
              v-model="editForm.tipo_documento"
              label="Tipo Documento"
              placeholder="Seleccionar..."
              :options="documentTypeOptions"
              :error-message="editErrors.tipo_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-8">
            <TfInput
              id="edit-num-doc"
              v-model="editForm.numero_documento"
              label="Número de Documento"
              :error-message="editErrors.numero_documento?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-email"
              v-model="editForm.email"
              type="email"
              label="Correo Electrónico (Opcional)"
              prefix-icon="fa-regular fa-envelope"
              :error-message="editErrors.email?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-telefono"
              v-model="editForm.telefono"
              label="Teléfono / WhatsApp (Opcional)"
              prefix-icon="fa-solid fa-phone"
              :error-message="editErrors.telefono?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfSelect
              id="edit-estado"
              v-model="editForm.estado"
              label="Estado de la Identidad"
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

    <!-- MODAL 3: Ficha de Detalle de Persona -->
    <TfModal
      v-model="showDetailModal"
      title="Ficha de Identidad de Persona"
    >
      <div v-if="loadingRecord" class="py-3">
        <TfSkeleton height="24px" width="col-6" class="mb-3" />
        <TfSkeleton :lines="5" class="mb-3" />
      </div>

      <div v-else-if="detailRecord" class="py-2">
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-secondary rounded">
          <div
            class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center"
            style="width: 48px; height: 48px; font-size: 1.2rem;"
          >
            {{ getInitials(detailRecord.nombres, detailRecord.apellidos) }}
          </div>
          <div>
            <h5 class="fw-bold mb-0 text-body">{{ detailRecord.nombre_completo }}</h5>
            <small class="text-muted">Registro #{{ detailRecord.id }}</small>
          </div>
          <div class="ms-auto">
            <TfBadge :variant="getStatusVariant(detailRecord.estado)" :dot="true">
              {{ formatStatusLabel(detailRecord.estado) }}
            </TfBadge>
          </div>
        </div>

        <dl class="row mb-0 small">
          <dt class="col-sm-4 text-muted">Tipo Documento:</dt>
          <dd class="col-sm-8 fw-semibold">{{ detailRecord.tipo_documento || 'No especificado' }}</dd>

          <dt class="col-sm-4 text-muted">N° Documento:</dt>
          <dd class="col-sm-8 font-monospace">{{ detailRecord.numero_documento || 'Sin número' }}</dd>

          <dt class="col-sm-4 text-muted">Correo Electrónico:</dt>
          <dd class="col-sm-8">
            <a v-if="detailRecord.email" :href="`mailto:${detailRecord.email}`" class="text-decoration-none">
              {{ detailRecord.email }}
            </a>
            <span v-else class="text-muted">Sin correo</span>
          </dd>

          <dt class="col-sm-4 text-muted">Teléfono / Celular:</dt>
          <dd class="col-sm-8">{{ detailRecord.telefono || 'Sin teléfono' }}</dd>

          <dt class="col-sm-4 text-muted">Cuenta de Acceso:</dt>
          <dd class="col-sm-8">
            <span v-if="detailRecord.has_user" class="text-success fw-semibold">
              <i class="fa-solid fa-check-circle me-1" aria-hidden="true"></i>Cuenta User vinculada
            </span>
            <span v-else class="text-muted">
              <i class="fa-solid fa-circle-xmark me-1" aria-hidden="true"></i>Sin cuenta de usuario asociada
            </span>
          </dd>

          <dt class="col-sm-4 text-muted">Fecha de Registro:</dt>
          <dd class="col-sm-8 text-muted">{{ formatDate(detailRecord.created_at) }}</dd>

          <dt class="col-sm-4 text-muted">Última Modificación:</dt>
          <dd class="col-sm-8 text-muted">{{ formatDate(detailRecord.updated_at) }}</dd>
        </dl>
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

// Columnas de la tabla
const tableHeaders: TableHeader[] = [
  { key: 'persona', label: 'Persona', sortable: true },
  { key: 'documento', label: 'Documento' },
  { key: 'contacto', label: 'Contacto' },
  { key: 'has_user', label: 'Cuenta User' },
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
 * Apertura de Modal Detalle
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
