<template>
  <div class="tf-roles-view">
    <!-- Encabezado de la Vista -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1 text-heading">Roles y Permisos</h2>
        <p class="text-muted small mb-0">
          Control de acceso basado en roles (RBAC) y definición de la matriz de privilegios de TravelFlow CMS.
        </p>
      </div>
      <div>
        <TfButton
          v-if="authStore.hasPermission('roles.crear')"
          variant="primary"
          icon="fa-solid fa-plus"
          @click="openCreateModal"
        >
          Nuevo rol
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
          @click="fetchRoles(pagination.currentPage)"
        >
          <i class="fa-solid fa-rotate-right me-1" aria-hidden="true"></i> Reintentar
        </button>
      </div>
    </TfAlert>

    <!-- Tarjeta Principal con Búsqueda y Tabla -->
    <div class="card shadow-sm border-0 mb-4">
      <!-- Toolbar de Búsqueda -->
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
              placeholder="Buscar por nombre de rol, identificador técnico (slug) o descripción..."
              aria-label="Buscar roles"
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

      <!-- Tabla de Roles Materialize -->
      <div class="card-body p-0">
        <TfTable
          :headers="tableHeaders"
          :items="roles"
          :loading="loading"
          :skeleton-rows="5"
          :empty-title="activeSearch ? 'No se encontraron resultados' : 'No hay roles registrados'"
          :empty-description="activeSearch ? `No se encontraron coincidencias para &quot;${activeSearch}&quot;.` : 'Comience registrando un nuevo rol en el sistema.'"
          :current-sort="sortColumn"
          :current-direction="sortDirection"
          @sort="handleSort"
        >
          <!-- Celda: Rol (Nombre y Tipo) -->
          <template #cell-rol="{ item }">
            <div class="d-flex align-items-center gap-2">
              <div
                class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                :class="item.is_system ? 'bg-label-primary text-primary' : 'bg-label-secondary text-secondary'"
                style="width: 38px; height: 38px; font-size: 0.85rem;"
                aria-hidden="true"
              >
                <i :class="item.is_system ? 'fa-solid fa-shield-halved' : 'fa-solid fa-user-tag'"></i>
              </div>
              <div class="min-w-0">
                <span class="fw-semibold text-body d-block text-truncate">
                  {{ item.name }}
                </span>
                <span v-if="item.is_system" class="badge bg-label-info font-monospace" style="font-size: 0.7rem;">
                  Sistema
                </span>
                <span v-else class="badge bg-label-secondary" style="font-size: 0.7rem;">
                  Personalizado
                </span>
              </div>
            </div>
          </template>

          <!-- Celda: Slug Técnico -->
          <template #cell-slug="{ item }">
            <code class="font-monospace small text-primary">{{ item.slug }}</code>
          </template>

          <!-- Celda: Descripción -->
          <template #cell-description="{ item }">
            <span class="text-muted small text-truncate d-inline-block" style="max-width: 280px;" :title="item.description || ''">
              {{ item.description || '— Sin descripción —' }}
            </span>
          </template>

          <!-- Celda: Permisos Asignados -->
          <template #cell-permissions_count="{ item }">
            <span class="badge bg-label-primary fw-semibold">
              <i class="fa-solid fa-key me-1" aria-hidden="true"></i>
              {{ item.permissions_count }} permisos
            </span>
          </template>

          <!-- Celda: Usuarios con el Rol -->
          <template #cell-users_count="{ item }">
            <span class="badge bg-label-secondary fw-semibold">
              <i class="fa-solid fa-users me-1" aria-hidden="true"></i>
              {{ item.users_count }} usuarios
            </span>
          </template>

          <!-- Celda: Acciones por Fila -->
          <template #actions="{ item }">
            <div class="d-flex justify-content-end gap-1" role="group" aria-label="Acciones de rol">
              <!-- Ver Detalle -->
              <button
                type="button"
                class="btn btn-sm btn-outline-secondary px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Ver detalle del rol"
                aria-label="Ver detalle del rol"
                @click="openDetailModal(item.id)"
              >
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Ver</span>
              </button>

              <!-- Editar Rol -->
              <button
                v-if="authStore.hasPermission('roles.editar')"
                type="button"
                class="btn btn-sm btn-outline-primary px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Editar rol y permisos"
                aria-label="Editar rol y permisos"
                @click="openEditModal(item.id)"
              >
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Editar</span>
              </button>

              <!-- Eliminar Rol (Solo roles personalizados sin usuarios asignados) -->
              <button
                v-if="authStore.hasPermission('roles.eliminar') && !item.is_system && item.users_count === 0"
                type="button"
                class="btn btn-sm btn-outline-danger px-2 py-1 d-inline-flex align-items-center gap-1"
                title="Eliminar rol"
                aria-label="Eliminar rol"
                @click="handleDeleteRole(item)"
              >
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                <span class="d-none d-xl-inline small">Eliminar</span>
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

    <!-- ================= MODAL 1: REGISTRAR NUEVO ROL ================= -->
    <TfModal
      v-model="showCreateModal"
      title="Registrar Nuevo Rol"
      size="lg"
      :loading="saving"
    >
      <form id="create-role-form" @submit.prevent="submitCreateRole">
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <TfInput
              id="create-role-name"
              v-model="createForm.name"
              label="Nombre del rol"
              placeholder="Ej. Gestor de Destinos"
              :required="true"
              :error-message="createErrors.name?.[0]"
              :disabled="saving"
              @input="onRoleNameInput"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="create-role-slug"
              v-model="createForm.slug"
              label="Identificador técnico (slug)"
              placeholder="Ej. gestor-destinos"
              :required="true"
              :error-message="createErrors.slug?.[0]"
              :disabled="saving"
              help-text="Identificador único en minúsculas y guiones."
            />
          </div>

          <div class="col-12">
            <label for="create-role-desc" class="form-label fw-semibold">Descripción (opcional)</label>
            <textarea
              id="create-role-desc"
              v-model="createForm.description"
              class="form-control"
              rows="2"
              placeholder="Breve explicación de las responsabilidades asignadas a este rol..."
              :disabled="saving"
            ></textarea>
          </div>
        </div>

        <!-- Matriz de Selección de Permisos por Dominio -->
        <div class="border-top pt-3">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h6 class="fw-bold mb-0 text-heading">Matriz de Permisos</h6>
              <small class="text-muted">Seleccione los privilegios otorgados a este rol</small>
            </div>
            <span class="badge bg-label-primary">
              {{ createForm.permissions.length }} permisos seleccionados
            </span>
          </div>

          <div v-if="createErrors.permissions" class="alert alert-danger py-2 small mb-3">
            {{ createErrors.permissions[0] }}
          </div>

          <!-- Permisos agrupados en acordeón/tarjetas -->
          <div class="row g-3">
            <div
              v-for="(perms, domain) in groupedPermissions"
              :key="domain"
              class="col-12 col-md-6"
            >
              <div class="card border shadow-none h-100">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center justify-content-between">
                  <span class="fw-bold small text-uppercase text-heading">
                    <i class="fa-solid fa-folder me-1 text-primary"></i> {{ domain }}
                  </span>
                  <button
                    type="button"
                    class="btn btn-link btn-xs text-primary p-0 text-decoration-none"
                    @click="toggleDomainPermissions(createForm.permissions, perms)"
                  >
                    {{ areAllDomainSelected(createForm.permissions, perms) ? 'Desmarcar todos' : 'Marcar todos' }}
                  </button>
                </div>
                <div class="card-body p-3">
                  <div
                    v-for="perm in perms"
                    :key="perm.id"
                    class="form-check mb-2"
                  >
                    <input
                      :id="`create-perm-${perm.id}`"
                      v-model="createForm.permissions"
                      type="checkbox"
                      class="form-check-input"
                      :value="perm.id"
                      :disabled="saving"
                    />
                    <label :for="`create-perm-${perm.id}`" class="form-check-label small user-select-none">
                      <span class="fw-semibold d-block text-body">{{ perm.name }}</span>
                      <code class="text-muted font-monospace" style="font-size: 0.75rem;">{{ perm.slug }}</code>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>

      <template #footer>
        <div class="d-flex justify-content-end gap-2 w-100">
          <TfButton
            variant="secondary"
            :disabled="saving"
            @click="showCreateModal = false"
          >
            Cancelar
          </TfButton>
          <TfButton
            type="submit"
            form="create-role-form"
            variant="primary"
            :loading="saving"
            icon="fa-solid fa-check"
          >
            Guardar rol
          </TfButton>
        </div>
      </template>
    </TfModal>

    <!-- ================= MODAL 2: EDITAR ROL ================= -->
    <TfModal
      v-model="showEditModal"
      title="Editar Rol y Permisos"
      size="lg"
      :loading="saving"
    >
      <form id="edit-role-form" @submit.prevent="submitEditRole">
        <div class="row g-3 mb-4">
          <div class="col-12 col-md-6">
            <TfInput
              id="edit-role-name"
              v-model="editForm.name"
              label="Nombre del rol"
              :required="true"
              :error-message="editErrors.name?.[0]"
              :disabled="saving"
            />
          </div>

          <div class="col-12 col-md-6">
            <TfInput
              id="edit-role-slug"
              v-model="editForm.slug"
              label="Identificador técnico (slug)"
              :required="true"
              :error-message="editErrors.slug?.[0]"
              :disabled="saving || editForm.is_system"
              :help-text="editForm.is_system ? 'El identificador de los roles del sistema no puede ser modificado.' : 'Identificador único en minúsculas.'"
            />
          </div>

          <div class="col-12">
            <label for="edit-role-desc" class="form-label fw-semibold">Descripción (opcional)</label>
            <textarea
              id="edit-role-desc"
              v-model="editForm.description"
              class="form-control"
              rows="2"
              :disabled="saving"
            ></textarea>
          </div>
        </div>

        <!-- Matriz de Selección de Permisos por Dominio -->
        <div class="border-top pt-3">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h6 class="fw-bold mb-0 text-heading">Matriz de Permisos</h6>
              <small class="text-muted">Ajuste los privilegios asignados</small>
            </div>
            <span class="badge bg-label-primary">
              {{ editForm.permissions.length }} permisos seleccionados
            </span>
          </div>

          <div v-if="editErrors.permissions" class="alert alert-danger py-2 small mb-3">
            {{ editErrors.permissions[0] }}
          </div>

          <div class="row g-3">
            <div
              v-for="(perms, domain) in groupedPermissions"
              :key="`edit-${domain}`"
              class="col-12 col-md-6"
            >
              <div class="card border shadow-none h-100">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center justify-content-between">
                  <span class="fw-bold small text-uppercase text-heading">
                    <i class="fa-solid fa-folder me-1 text-primary"></i> {{ domain }}
                  </span>
                  <button
                    type="button"
                    class="btn btn-link btn-xs text-primary p-0 text-decoration-none"
                    @click="toggleDomainPermissions(editForm.permissions, perms)"
                  >
                    {{ areAllDomainSelected(editForm.permissions, perms) ? 'Desmarcar todos' : 'Marcar todos' }}
                  </button>
                </div>
                <div class="card-body p-3">
                  <div
                    v-for="perm in perms"
                    :key="`edit-perm-${perm.id}`"
                    class="form-check mb-2"
                  >
                    <input
                      :id="`edit-perm-${perm.id}`"
                      v-model="editForm.permissions"
                      type="checkbox"
                      class="form-check-input"
                      :value="perm.id"
                      :disabled="saving"
                    />
                    <label :for="`edit-perm-${perm.id}`" class="form-check-label small user-select-none">
                      <span class="fw-semibold d-block text-body">{{ perm.name }}</span>
                      <code class="text-muted font-monospace" style="font-size: 0.75rem;">{{ perm.slug }}</code>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>

      <template #footer>
        <div class="d-flex justify-content-end gap-2 w-100">
          <TfButton
            variant="secondary"
            :disabled="saving"
            @click="showEditModal = false"
          >
            Cancelar
          </TfButton>
          <TfButton
            type="submit"
            form="edit-role-form"
            variant="primary"
            :loading="saving"
            icon="fa-solid fa-check"
          >
            Actualizar cambios
          </TfButton>
        </div>
      </template>
    </TfModal>

    <!-- ================= MODAL 3: DETALLE / FICHA DE ROL ================= -->
    <TfModal
      v-model="showDetailModal"
      title="Ficha de Rol"
      size="lg"
    >
      <div v-if="detailLoading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Cargando detalles...</span>
        </div>
      </div>

      <div v-else-if="currentRoleDetail">
        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4">
          <div
            class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
            :class="currentRoleDetail.is_system ? 'bg-label-primary text-primary' : 'bg-label-secondary text-secondary'"
            style="width: 48px; height: 48px; font-size: 1.2rem;"
          >
            <i :class="currentRoleDetail.is_system ? 'fa-solid fa-shield-halved' : 'fa-solid fa-user-tag'"></i>
          </div>
          <div class="flex-grow-1">
            <h5 class="mb-1 fw-bold text-heading">{{ currentRoleDetail.name }}</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <code class="font-monospace small text-primary">{{ currentRoleDetail.slug }}</code>
              <span v-if="currentRoleDetail.is_system" class="badge bg-label-info">Rol del Sistema</span>
              <span v-else class="badge bg-label-secondary">Rol Personalizado</span>
            </div>
          </div>
        </div>

        <p v-if="currentRoleDetail.description" class="text-muted small mb-4">
          {{ currentRoleDetail.description }}
        </p>

        <!-- Pestañas o Bloques: Permisos y Usuarios -->
        <div class="mb-4">
          <h6 class="fw-bold mb-2 text-heading d-flex align-items-center justify-content-between">
            <span>Permisos Asignados ({{ currentRoleDetail.permissions?.length || 0 }})</span>
          </h6>
          <div v-if="currentRoleDetail.permissions && currentRoleDetail.permissions.length > 0" class="d-flex flex-wrap gap-1 p-3 border rounded-2 bg-body">
            <span
              v-for="p in currentRoleDetail.permissions"
              :key="p.id"
              class="badge bg-label-primary font-monospace"
              :title="p.description || p.name"
            >
              {{ p.slug }}
            </span>
          </div>
          <span v-else class="text-muted small">Sin permisos asignados</span>
        </div>

        <div>
          <h6 class="fw-bold mb-2 text-heading">
            Usuarios con este Rol ({{ currentRoleDetail.users?.length || 0 }})
          </h6>
          <div v-if="currentRoleDetail.users && currentRoleDetail.users.length > 0" class="list-group list-group-flush border rounded-2">
            <div
              v-for="u in currentRoleDetail.users"
              :key="u.id"
              class="list-group-item d-flex align-items-center justify-content-between py-2 px-3"
            >
              <div>
                <span class="fw-semibold text-body d-block small">
                  {{ u.persona ? u.persona.nombre_completo : u.name }}
                </span>
                <span class="text-muted font-monospace small">{{ u.email }}</span>
              </div>
              <span
                class="badge"
                :class="u.status === 'active' ? 'bg-label-success' : 'bg-label-secondary'"
              >
                {{ u.status === 'active' ? 'Activo' : u.status }}
              </span>
            </div>
          </div>
          <span v-else class="text-muted small">Ningún usuario tiene este rol asignado en este momento.</span>
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
import { TfButton, TfInput, TfTable, TfPagination, TfModal, TfAlert } from '@/design-system';
import type { TableHeader } from '@/design-system/components/TfTable.vue';

interface PermissionItem {
  id: number;
  name: string;
  slug: string;
  domain: string;
  description?: string;
}

interface RoleItem {
  id: number;
  name: string;
  slug: string;
  description?: string;
  is_system: boolean;
  permissions_count: number;
  users_count: number;
  created_at: string;
}

interface RoleDetail extends RoleItem {
  permissions: PermissionItem[];
  users: Array<{
    id: number;
    name: string;
    email: string;
    status: string;
    persona?: {
      id: number;
      nombre_completo: string;
    };
  }>;
}

const authStore = useAuthStore();
const api = useApi();

const roles = ref<RoleItem[]>([]);
const groupedPermissions = ref<Record<string, PermissionItem[]>>({});
const loading = ref<boolean>(false);
const saving = ref<boolean>(false);
const detailLoading = ref<boolean>(false);
const fetchError = ref<string>('');

const searchQuery = ref<string>('');
const activeSearch = ref<string>('');
const sortColumn = ref<string>('name');
const sortDirection = ref<'asc' | 'desc'>('asc');

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 0,
});

const tableHeaders: TableHeader[] = [
  { key: 'rol', label: 'Rol', sortable: true },
  { key: 'slug', label: 'Identificador', sortable: true },
  { key: 'description', label: 'Descripción' },
  { key: 'permissions_count', label: 'Permisos', align: 'center', sortable: true },
  { key: 'users_count', label: 'Usuarios', align: 'center', sortable: true },
];

// Modales
const showCreateModal = ref<boolean>(false);
const showEditModal = ref<boolean>(false);
const showDetailModal = ref<boolean>(false);
const currentRoleId = ref<number | null>(null);
const currentRoleDetail = ref<RoleDetail | null>(null);

const createForm = reactive({
  name: '',
  slug: '',
  description: '',
  permissions: [] as number[],
});

const createErrors = reactive<Record<string, string[]>>({});

const editForm = reactive({
  name: '',
  slug: '',
  description: '',
  is_system: false,
  permissions: [] as number[],
});

const editErrors = reactive<Record<string, string[]>>({});

async function fetchPermissionsCatalogue() {
  try {
    const res = await api.get<{ success: boolean; grouped: Record<string, PermissionItem[]> }>('/api/admin/permisos');
    if (res && res.grouped) {
      groupedPermissions.value = res.grouped;
    }
  } catch (error) {
    // Si no tiene permisos.ver, ignorar catálogo silenciosamente
  }
}

async function fetchRoles(page = 1) {
  loading.value = true;
  fetchError.value = '';

  try {
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(pagination.perPage),
      sort: sortColumn.value,
      direction: sortDirection.value,
    });

    if (activeSearch.value) {
      params.append('search', activeSearch.value);
    }

    const res = await api.get<{
      success: boolean;
      data: RoleItem[];
      meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
      };
    }>(`/api/admin/roles?${params.toString()}`);

    if (res && res.data) {
      roles.value = res.data;
      pagination.currentPage = res.meta.current_page;
      pagination.lastPage = res.meta.last_page;
      pagination.perPage = res.meta.per_page;
      pagination.total = res.meta.total;
    }
  } catch (error: any) {
    fetchError.value = error?.message || 'Error al cargar el listado de roles.';
  } finally {
    loading.value = false;
  }
}

function handleSearch() {
  activeSearch.value = searchQuery.value.trim();
  fetchRoles(1);
}

function clearSearch() {
  searchQuery.value = '';
  activeSearch.value = '';
  fetchRoles(1);
}

function handleSort(column: string) {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
  fetchRoles(pagination.currentPage);
}

function handlePageChange(page: number) {
  fetchRoles(page);
}

function onRoleNameInput() {
  if (!createForm.slug || createForm.slug.startsWith(createForm.name.substring(0, 3).toLowerCase())) {
    createForm.slug = createForm.name
      .toLowerCase()
      .trim()
      .replace(/[\s\W-]+/g, '-');
  }
}

function areAllDomainSelected(selected: number[], domainPerms: PermissionItem[]): boolean {
  return domainPerms.every(p => selected.includes(p.id));
}

function toggleDomainPermissions(selected: number[], domainPerms: PermissionItem[]) {
  const allSelected = areAllDomainSelected(selected, domainPerms);
  const domainIds = domainPerms.map(p => p.id);

  if (allSelected) {
    // Remover todos los del dominio
    const filtered = selected.filter(id => !domainIds.includes(id));
    selected.length = 0;
    selected.push(...filtered);
  } else {
    // Agregar los faltantes del dominio
    for (const id of domainIds) {
      if (!selected.includes(id)) {
        selected.push(id);
      }
    }
  }
}

function openCreateModal() {
  createForm.name = '';
  createForm.slug = '';
  createForm.description = '';
  createForm.permissions = [];
  Object.keys(createErrors).forEach(key => delete createErrors[key]);
  showCreateModal.value = true;
}

async function submitCreateRole() {
  saving.value = true;
  Object.keys(createErrors).forEach(key => delete createErrors[key]);

  try {
    await api.post('/api/admin/roles', {
      name: createForm.name,
      slug: createForm.slug,
      description: createForm.description || null,
      permissions: createForm.permissions,
    });

    showCreateModal.value = false;
    fetchRoles(pagination.currentPage);

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Rol registrado exitosamente',
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
    });
  } catch (error: any) {
    if (error instanceof ApiError && error.status === 422 && error.data?.errors) {
      Object.assign(createErrors, error.data.errors);
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al registrar rol',
        text: error?.message || 'Ocurrió un error inesperado al guardar el rol.',
      });
    }
  } finally {
    saving.value = false;
  }
}

async function openEditModal(roleId: number) {
  currentRoleId.value = roleId;
  saving.value = true;
  Object.keys(editErrors).forEach(key => delete editErrors[key]);

  try {
    const res = await api.get<{ success: boolean; data: RoleDetail }>(`/api/admin/roles/${roleId}`);
    if (res && res.data) {
      editForm.name = res.data.name;
      editForm.slug = res.data.slug;
      editForm.description = res.data.description || '';
      editForm.is_system = res.data.is_system;
      editForm.permissions = res.data.permissions?.map(p => p.id) || [];
      showEditModal.value = true;
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error?.message || 'No fue posible cargar los datos del rol.',
    });
  } finally {
    saving.value = false;
  }
}

async function submitEditRole() {
  if (!currentRoleId.value) return;

  saving.value = true;
  Object.keys(editErrors).forEach(key => delete editErrors[key]);

  try {
    await api.patch(`/api/admin/roles/${currentRoleId.value}`, {
      name: editForm.name,
      slug: editForm.slug,
      description: editForm.description || null,
      permissions: editForm.permissions,
    });

    showEditModal.value = false;
    fetchRoles(pagination.currentPage);

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Rol actualizado exitosamente',
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,
    });
  } catch (error: any) {
    if (error instanceof ApiError && error.status === 422 && error.data?.errors) {
      Object.assign(editErrors, error.data.errors);
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al actualizar',
        text: error?.message || 'No fue posible actualizar el rol.',
      });
    }
  } finally {
    saving.value = false;
  }
}

async function openDetailModal(roleId: number) {
  detailLoading.value = true;
  currentRoleDetail.value = null;
  showDetailModal.value = true;

  try {
    const res = await api.get<{ success: boolean; data: RoleDetail }>(`/api/admin/roles/${roleId}`);
    if (res && res.data) {
      currentRoleDetail.value = res.data;
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error?.message || 'No fue posible cargar el detalle del rol.',
    });
    showDetailModal.value = false;
  } finally {
    detailLoading.value = false;
  }
}

async function handleDeleteRole(role: RoleItem) {
  const result = await Swal.fire({
    title: `¿Eliminar rol "${role.name}"?`,
    text: 'Esta acción no se puede deshacer. Se removerán todas las asignaciones de este rol.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ea5455',
    cancelButtonColor: '#808390',
    confirmButtonText: 'Sí, eliminar rol',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  });

  if (result.isConfirmed) {
    try {
      await api.delete(`/api/admin/roles/${role.id}`);

      fetchRoles(pagination.currentPage);

      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Rol eliminado correctamente',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
      });
    } catch (error: any) {
      Swal.fire({
        icon: 'error',
        title: 'No se pudo eliminar',
        text: error?.message || 'Ocurrió un error al intentar eliminar el rol.',
      });
    }
  }
}

onMounted(() => {
  fetchPermissionsCatalogue();
  fetchRoles(1);
});
</script>

<style scoped>
.btn-xs {
  font-size: 0.75rem;
}
</style>
