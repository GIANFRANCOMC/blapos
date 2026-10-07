<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Filters -->
    <FiltersSection
        :filter-by-value="filterByValue"
        @update:filterByValue="filterByValue = $event"
        :filter-word-value="filterWordValue"
        @update:filterWordValue="filterWordValue = $event"
        :filter-by-options="filterByOptions"
        :search-placeholder="searchPlaceholder"
        :loading="entityList.extras.loading"
        :filter-by-title="MODULE.texts.filters.filterBy"
        :search-title="MODULE.texts.filters.search"
        :search-button-text="MODULE.texts.actions.search"
        :add-button-text="MODULE.texts.actions.add"
        :show-add-button="true"
        :title-class="[config.forms.classes.title]"
        :select-class="config.forms.classes.select2"
        @search="handleSearch"
        @add="openModal()"/>

    <!-- Records -->
    <div class="list-section mb-1 mb-md-1 table-responsive">
        <table class="table table-hover">
            <thead class="align-middle bg-secondary text-center">
                <tr>
                    <th class="text-white" style="width: 18%;">PERFIL DE ACCESO</th>
                    <th class="text-white" style="width: 35%;" v-text="MODULE.config.pageTitleSingular"></th>
                    <th class="text-white" style="width: 30%;">CONTACTO</th>
                    <th class="text-white" style="width: 10%;">ESTADO</th>
                    <th class="text-white" style="width: 10%;">ACCIONES</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0 bg-white">
                <tr v-if="entityList.extras.loading">
                    <td colspan="99">
                        <Loader/>
                    </td>
                </tr>
                <template v-else-if="entityList.records.total > 0">
                    <tr v-for="record in entityList.records.data" :key="record.id">
                        <td class="text-center">
                            <span class="br-users-profile-label" v-text="record?.role?.name || 'Sin perfil'"></span>
                            <small class="br-users-branches-label" v-text="branchAccessLabel(record)"></small>
                        </td>
                        <td>
                            <div class="mb-1">
                                <span v-text="record.identity_document_type?.name" class="text-muted"></span>
                                <span v-text="record.document_number" class="fw-semibold ms-1"></span>
                            </div>
                            <span v-text="record.name" class="fw-semibold d-block"></span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <a :href="'mailto:'+record.email" class="text-decoration-none" v-if="isDefined(record.email)">
                                    <span v-text="record.email"></span>
                                </a>
                                <a :href="'tel:'+record.phone_number" class="text-decoration-none" v-if="isDefined(record.phone_number)">
                                    <span v-text="record.phone_number"></span>
                                </a>
                            </div>
                        </td>
                        <td class="text-center">
                            <StatusBadge class="flex-shrink-none" :status="record.status" :formatted-status="record.formatted_status"/>
                        </td>
                        <td class="text-center">
                            <div class="br-table-actions">
                                <button
                                    type="button"
                                    class="br-icon-action br-icon-action-info"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Historial de acceso"
                                    :aria-label="`Ver historial de acceso de ${record.name}`"
                                    @click="openAuthenticationEvents(record)">
                                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="br-icon-action br-icon-action-primary"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Cambiar contraseña"
                                    :aria-label="`Cambiar contraseña de ${record.name}`"
                                    @click="openPasswordModal(record)">
                                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="br-icon-action br-icon-action-edit"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    :title="MODULE.texts.actions.edit"
                                    :aria-label="`${MODULE.texts.actions.edit} ${record.name}`"
                                    @click="openModal(record)">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td colspan="99">
                        <WithoutData type="image"/>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav v-if="!entityList.extras.loading && entityList.records.total > 0" class="d-flex justify-content-center">
        <Paginator :links="entityList.records.links" @clickPage="listEntity"/>
    </nav>

    <!-- Modal: Authentication events -->
    <div class="modal fade" :id="authenticationEvents.modalId" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content br-entity-modal">
                <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Seguridad</p>
                        <h5 class="modal-title">Historial de acceso</h5>
                        <small v-if="authenticationEvents.user" class="br-entity-table__meta">
                            {{ authenticationEvents.user.name }} · {{ authenticationEvents.user.email }}
                        </small>
                    </div>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <section class="br-filter-bar br-auth-events__filters">
                        <div class="row align-items-end g-2">
                            <InputSlot hasDiv title="Evento" :titleClass="[config.forms.classes.title]" xl="3" lg="3">
                                <template #input>
                                    <v-select
                                        v-model="authenticationEvents.filters.eventType"
                                        :options="authenticationEventTypes"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"
                                        append-to-body/>
                                </template>
                            </InputSlot>
                            <InputSlot hasDiv title="Resultado" :titleClass="[config.forms.classes.title]" xl="3" lg="3">
                                <template #input>
                                    <v-select
                                        v-model="authenticationEvents.filters.result"
                                        :options="authenticationResults"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"
                                        append-to-body/>
                                </template>
                            </InputSlot>
                            <InputDate
                                v-model="authenticationEvents.filters.dateFrom"
                                hasDiv
                                title="Desde"
                                :titleClass="[config.forms.classes.title]"
                                xl="2"
                                lg="2"/>
                            <InputDate
                                v-model="authenticationEvents.filters.dateTo"
                                hasDiv
                                title="Hasta"
                                :titleClass="[config.forms.classes.title]"
                                xl="2"
                                lg="2"/>
                            <InputSlot
                                hasDiv
                                :isInputGroup="false"
                                :divInputClass="['br-filter-bar__actions']"
                                xl="2"
                                lg="2">
                                <template #input>
                                    <button
                                        type="button"
                                        class="br-btn br-btn-sm br-btn-search"
                                        :disabled="authenticationEvents.loading"
                                        @click="listAuthenticationEvents({})">
                                        <i class="fa-solid fa-filter" aria-hidden="true"></i>
                                        <span>Filtrar</span>
                                    </button>
                                </template>
                            </InputSlot>
                        </div>
                    </section>

                    <div class="table-responsive br-entity-table-wrap">
                        <table class="table br-entity-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">Fecha</th>
                                    <th style="width: 18%;">Evento</th>
                                    <th style="width: 14%;">Resultado</th>
                                    <th style="width: 18%;">Origen</th>
                                    <th>Detalle técnico</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="authenticationEvents.loading">
                                    <td colspan="5" class="text-center py-4"><Loader/></td>
                                </tr>
                                <template v-else-if="authenticationEvents.records.total > 0">
                                    <tr v-for="event in authenticationEvents.records.data" :key="event.id">
                                        <td>{{ legibleFormatDate({dateString: event.occurred_at, type: 'datetime'}) }}</td>
                                        <td>
                                            <strong class="br-entity-primary">{{ authEventLabel(event.event_type) }}</strong>
                                        </td>
                                        <td>
                                            <span :class="['br-status-label', event.result === 'success' ? 'br-status-active' : 'br-status-inactive']">
                                                {{ authResultLabel(event.result) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="br-entity-table__meta">IP {{ event.ip_address || "No registrada" }}</span>
                                            <span class="br-entity-table__meta">Tenant {{ event.tenant_key || "Actual" }}</span>
                                        </td>
                                        <td>
                                            <span class="br-entity-table__meta">{{ event.reason || "Sin observación" }}</span>
                                            <span class="br-auth-events__agent" :title="event.user_agent">{{ event.user_agent || "Agente no registrado" }}</span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="5"><WithoutData type="image"/></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <nav v-if="!authenticationEvents.loading && authenticationEvents.records.total > 0" class="d-flex justify-content-center mt-3">
                        <Paginator :links="authenticationEvents.records.links" @clickPage="listAuthenticationEvents"/>
                    </nav>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Create/Update -->
    <div class="modal fade" :id="forms[entity].createUpdate.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="modalTitles.createUpdate[isUpdate ? 'update' : 'store']"></h5>
                    <button type="button" class="btn-header-modal" data-bs-dismiss="modal">
                        <i class="fa fa-times icon-close-modal"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveEntity">
                        <div class="row g-3">
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.role"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.role_id"
                                xl="12"
                                lg="12">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.role"
                                        :options="roles"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="roles.length > 6"
                                        append-to-body
                                        @close="tooltips({show: true, time: 500})">
                                        <template #option="{label, data}">
                                            <span class="br-select-option-text" :title="label">
                                                {{ label }}
                                                <small v-if="data?.is_full_access" class="br-users-profile-option">Acceso total</small>
                                            </span>
                                        </template>
                                        <template #selected-option="{label}">
                                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                                        </template>
                                    </v-select>
                                </template>
                            </InputSlot>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.branches"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.branch_ids || MODULE.texts.form.branchesHint"
                                xl="12"
                                lg="12">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.branches"
                                        :options="branches"
                                        :class="config.forms.classes.select2"
                                        :clearable="true"
                                        :searchable="branches.length > 6"
                                        multiple
                                        append-to-body
                                        placeholder="Heredar sucursales del perfil"
                                        @close="tooltips({show: true, time: 500})">
                                        <template #option="{label}">
                                            <span class="br-select-option-text" :title="label">{{ label }}</span>
                                        </template>
                                        <template #selected-option="{label}">
                                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                                        </template>
                                    </v-select>
                                </template>
                            </InputSlot>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.cashRegisters"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.cash_register_ids || MODULE.texts.form.cashRegistersHint"
                                xl="6"
                                lg="6">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.cashRegisters"
                                        :options="cashRegisters"
                                        :class="config.forms.classes.select2"
                                        :clearable="true"
                                        :searchable="cashRegisters.length > 6"
                                        multiple
                                        append-to-body
                                        placeholder="Heredar cajas del perfil"
                                        @close="tooltips({show: true, time: 500})">
                                        <template #selected-option="{label}">
                                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                                        </template>
                                    </v-select>
                                </template>
                            </InputSlot>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.warehouses"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.warehouse_ids || MODULE.texts.form.warehousesHint"
                                xl="6"
                                lg="6">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.warehouses"
                                        :options="warehouses"
                                        :class="config.forms.classes.select2"
                                        :clearable="true"
                                        :searchable="warehouses.length > 6"
                                        multiple
                                        append-to-body
                                        placeholder="Heredar almacenes del perfil"
                                        @close="tooltips({show: true, time: 500})">
                                        <template #selected-option="{label}">
                                            <span class="br-select-selected-text" :title="label">{{ label }}</span>
                                        </template>
                                    </v-select>
                                </template>
                            </InputSlot>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.identityDocumentType"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.identity_document_type_id"
                                xl="6"
                                lg="6">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.identity_document_type"
                                        :options="identityDocumentTypes"
                                        :class="config.forms.classes.select2"
                                        @close="tooltips({show: true, time: 500})"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputText
                                v-model="forms[entity].createUpdate.data.document_number"
                                hasDiv
                                :title="MODULE.texts.form.documentNumber"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                :maxlength="documentNumberMaxLength"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.document_number"
                                xl="6"
                                lg="6">
                                <template v-slot:inputGroupPrepend v-if="!isUpdate">
                                    <template v-if="isDocumentTypeSearchable">
                                        <button :class="['btn waves-effect', isUpdate ? 'btn-warning' : 'btn-primary']" type="button" @click="searchDocumentNumber" data-bs-toggle="tooltip" data-bs-placement="top" :title="MODULE.texts.form.searchDocumentTooltip">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </template>
                                </template>
                            </InputText>
                            <InputText
                                v-model="forms[entity].createUpdate.data.name"
                                hasDiv
                                :title="MODULE.texts.form.name"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.name"
                                xl="6"
                                lg="6"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.email"
                                @input="onEmailInput"
                                hasDiv
                                :title="MODULE.texts.form.email"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                maxlength="100"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.email"
                                xl="6"
                                lg="6"/>
                            <InputText
                                v-model="forms[entity].createUpdate.data.phone_number"
                                hasDiv
                                :title="MODULE.texts.form.phoneNumber"
                                :titleClass="[config.forms.classes.title]"
                                maxlength="15"
                                showCharCounter
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.phone_number"
                                xl="4"
                                lg="4"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.gender"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.gender"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.gender"
                                        :options="genders"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <InputDate
                                v-model="forms[entity].createUpdate.data.birthdate"
                                hasDiv
                                :title="MODULE.texts.form.birthdate"
                                :titleClass="[config.forms.classes.title]"
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.birthdate"
                                xl="4"
                                lg="4"/>
                            <InputSlot
                                hasDiv
                                :title="MODULE.texts.form.status"
                                :titleClass="[config.forms.classes.title]"
                                isRequired
                                hasTextBottom
                                :textBottomInfo="forms[entity].createUpdate.errors?.status"
                                xl="4"
                                lg="4">
                                <template v-slot:input>
                                    <v-select
                                        v-model="forms[entity].createUpdate.data.status"
                                        :options="statuses"
                                        :class="config.forms.classes.select2"
                                        :clearable="false"
                                        :searchable="false"/>
                                </template>
                            </InputSlot>
                            <div v-if="!isUpdate" class="form-group col-xl-4 col-lg-4 col-md-12 col-sm-12">
                                <label :class="['form-label', config.forms.classes.title]">{{ MODULE.texts.form.password }}</label>
                                <label class="text-danger ms-1 fw-bold">*</label>
                                <div class="input-group">
                                    <input
                                        v-model.trim="forms[entity].createUpdate.data.password"
                                        type="password"
                                        class="form-control"
                                        maxlength="20"
                                        autocomplete="new-password">
                                </div>
                                <small class="text-danger">{{ errorMessage(forms[entity].createUpdate.errors?.password) }}</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary waves-effect"
                        data-bs-dismiss="modal"
                        v-text="MODULE.texts.modal.close">
                    </button>
                    <button
                        type="button"
                        :class="['btn waves-effect', isUpdate ? 'btn-warning' : 'btn-primary']"
                        @click="saveEntity"
                        :disabled="isSaving">
                        <i class="fa fa-save"></i>
                        <span class="ms-2" v-text="MODULE.texts.modal.save"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade br-entity-modal br-modal-standard" :id="passwordModal.id" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Seguridad</p>
                        <h2 class="modal-title br-entity-modal__title">Cambiar contraseña</h2>
                        <p v-if="passwordModal.user" class="br-entity-table__meta mb-0">{{ passwordModal.user.name }} - {{ passwordModal.user.email }}</p>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body br-modal-standard__body">
                    <div class="row g-3">
                        <div class="form-group col-12">
                            <label class="form-label fw-semibold">Nueva contraseña</label>
                            <label class="text-danger ms-1 fw-bold">*</label>
                            <div class="input-group">
                                <input
                                    v-model.trim="passwordModal.form.password"
                                    type="password"
                                    class="form-control"
                                    maxlength="20"
                                    autocomplete="new-password">
                            </div>
                            <small class="text-danger">{{ errorMessage(passwordModal.errors?.password) }}</small>
                        </div>
                        <div class="form-group col-12">
                            <label class="form-label fw-semibold">Confirmar contraseña</label>
                            <label class="text-danger ms-1 fw-bold">*</label>
                            <div class="input-group">
                                <input
                                    v-model.trim="passwordModal.form.password_confirmation"
                                    type="password"
                                    class="form-control"
                                    maxlength="20"
                                    autocomplete="new-password">
                            </div>
                            <small class="text-danger">{{ errorMessage(passwordModal.errors?.password_confirmation) }}</small>
                        </div>
                    </div>
                    <p class="br-field-help mt-2 mb-0">
                        El cambio revoca sesiones activas y queda auditado sin guardar la contraseña en claro.
                    </p>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-update" :disabled="passwordModal.saving" @click="savePassword">
                        Actualizar contraseña
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import { initCrudModule } from "@System/Helpers/ModuleFactory.js";
import * as Forms from "@System/Helpers/Forms.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import { validateOnlyDigits } from "@System/Helpers/ValidationHelpers.js";

const MODULE_CONFIG = {
    entity: "users",
    menuId: "menu-configuration-users",
    pageTitle: "Colaboradores",
    pageTitleSingular: "Colaborador",
    breadcrumbParent: "Configuración",
    perPage: 15
};

const FORM_FIELDS = {
    role: null,
    branches: [],
    cashRegisters: [],
    warehouses: [],
    identity_document_type: null,
    document_number: "",
    name: "",
    email: "",
    phone_number: "",
    gender: null,
    birthdate: "",
    status: null,
    password: ""
};

const FORM_FIELD_CONFIG = {
    role: {mapToField: "role_id"},
    branches: {removeIfEmpty: false},
    cashRegisters: {removeIfEmpty: false},
    warehouses: {removeIfEmpty: false},
    identity_document_type: {mapToField: "identity_document_type_id"},
    document_number: {trim: true},
    name: {trim: true},
    email: {trim: true},
    phone_number: {trim: true, normalize: true},
    gender: {getCode: true, removeIfEmpty: true},
    birthdate: {normalize: true},
    status: {getCode: true},
    password: {trim: true, removeIfEmpty: true}
};

const VALIDATION_RULES = {
    role: {required: true},
    branches: {required: false},
    cashRegisters: {required: false},
    warehouses: {required: false},
    identity_document_type: {required: true},
    document_number: {required: true},
    name: {required: true},
    email: {required: true, email: true},
    phone_number: {required: false},
    gender: {required: false},
    birthdate: {required: false},
    status: {required: true},
    password: {required: false}
};

const ERROR_LABELS = {
    role: "Perfil de acceso",
    role_id: "Perfil de acceso",
    branches: "Sucursales permitidas",
    branch_ids: "Sucursales permitidas",
    cashRegisters: "Cajas permitidas",
    cash_register_ids: "Cajas permitidas",
    warehouses: "Almacenes permitidos",
    warehouse_ids: "Almacenes permitidos",
    identity_document_type: "Tipo de documento",
    identity_document_type_id: "Tipo de documento",
    document_number: "Número de documento",
    name: "Nombre",
    email: "Correo electrónico",
    phone_number: "Celular",
    gender: "Género",
    birthdate: "Fecha de nacimiento",
    status: "Estado",
    password: "Contraseña"
};

const FILTER_OPTIONS = [
    {code: "all", label: "Todos los filtros"},
    {code: "document_number", label: "Número de documento"},
    {code: "name", label: "Nombre"},
    {code: "email", label: "Correo electrónico"},
    {code: "phone_number", label: "Celular"}
];

const AUTHENTICATION_EVENT_TYPES = [
    {code: "", label: "Todos los eventos"},
    {code: "login", label: "Inicio de sesión"},
    {code: "logout", label: "Cierre de sesión"},
    {code: "session_revoked", label: "Sesión revocada"},
    {code: "password_changed", label: "Contraseña modificada"}
];

const AUTHENTICATION_RESULTS = [
    {code: "", label: "Todos los resultados"},
    {code: "success", label: "Correcto"},
    {code: "failure", label: "Fallido"},
    {code: "blocked", label: "Bloqueado"}
];

const TEXTS = {
    filters: {
        filterBy: "Filtrar por",
        search: "Búsqueda"
    },
    actions: {
        search: "Buscar",
        add: "Agregar",
        edit: "Editar"
    },
    form: {
        role: "Perfil de acceso",
        branches: "Sucursales permitidas",
        branchesHint: "Deja vacío para heredar las sucursales permitidas por el perfil.",
        cashRegisters: "Cajas permitidas",
        cashRegistersHint: "Deja vacío para heredar las cajas permitidas por el perfil.",
        warehouses: "Almacenes permitidos",
        warehousesHint: "Deja vacío para heredar los almacenes permitidos por el perfil.",
        identityDocumentType: "Tipo de documento",
        documentNumber: "Número de documento",
        name: "Nombre",
        email: "Correo electrónico",
        phoneNumber: "Celular",
        gender: "Género",
        birthdate: "Fecha de nacimiento",
        status: "Estado",
        password: "Contraseña",
        changePassword: "Cambiar contraseña",
        searchDocumentTooltip: "Buscar N° documento"
    },
    modal: {
        close: "Cerrar",
        save: "Guardar"
    }
};

const MODULE = {
    config: MODULE_CONFIG,
    formFields: FORM_FIELDS,
    formFieldConfig: FORM_FIELD_CONFIG,
    validationRules: VALIDATION_RULES,
    errorLabels: ERROR_LABELS,
    texts: TEXTS,
    filterOptions: FILTER_OPTIONS
};

export default {
    name: "UsersMain",
    data() {

        const crudModule = initCrudModule({
            entity: MODULE.config.entity,
            menuId: MODULE.config.menuId,
            pageTitle: MODULE.config.pageTitle,
            pageTitleSingular: MODULE.config.pageTitleSingular
        });

        crudModule.lists[MODULE.config.entity].filters.filter_by = MODULE.filterOptions[0];
        crudModule.forms[MODULE.config.entity].createUpdate.data = Forms.initFormData(MODULE.formFields);

        return {
            ...crudModule,
            MODULE: MODULE,
            isInitialized: false,
            isSaving: false,
            authenticationEvents: {
                modalId: Utils.uuid(),
                loading: false,
                user: null,
                records: {total: 0, data: [], links: []},
                filters: {
                    eventType: AUTHENTICATION_EVENT_TYPES[0],
                    result: AUTHENTICATION_RESULTS[0],
                    dateFrom: "",
                    dateTo: ""
                }
            },
            passwordModal: {
                id: Utils.uuid(),
                saving: false,
                user: null,
                form: {
                    password: "",
                    password_confirmation: ""
                },
                errors: {}
            }
        };

    },
    mounted: async function() {

        Utils.navbarItem("menu-parent-infrastructure", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});

        Alerts.swals({type: "initParams"});

        try {

            const initParams = await this.initParams();

            if(initParams) {

                this.listEntity({});

            }

        }finally {

            this.isInitialized = true;
            Alerts.swals({show: false});

        }

    },
    methods: {
        async initParams() {

            const response = await Requests.get({
                route: this.routeActions.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(response?.data?.config) {

                this.options.genders               = response.data.config.genders;
                this.options.identityDocumentTypes = response.data.config.identityDocumentTypes;
                this.options.roles                 = response.data.config.roles;
                this.options.branches              = response.data.config.branches;
                this.options.cashRegisters         = response.data.config.cashRegisters;
                this.options.warehouses             = response.data.config.warehouses;
                this.options.statuses              = response.data.config.statuses;

            }

            return Requests.valid({result: response});

        },
        // List
        async listEntity(params = null) {

            const entityList   = this.lists[this.entity];
            const emptyRecords = {total: 0, data: [], links: []};
            const filters      = Utils.cloneJson(entityList.filters);
            const filterData   = {per_page: this.MODULE.config.perPage, filter_by: filters.filter_by?.code, word: filters.word};

            entityList.extras.loading = true;

            try {

                const url = this.isDefined(params) && typeof params === "object" ? params.url : params;

                let requestUrl  = url || entityList.extras.route;
                let requestData = {};

                if(this.isDefined(url)) {

                    const urlObj = new URL(url, window.location.origin);

                    Object.entries(filterData).forEach(([key, value]) => {

                        if(this.isDefined(value) && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);

                    });

                    requestUrl = `${urlObj.pathname}${urlObj.search}`;

                }else {

                    requestData = filterData;

                }

                const response = await Requests.get({route: requestUrl, data: requestData, showAlert: true});

                entityList.records = response?.data ?? emptyRecords;

            }catch(error) {

                entityList.records = emptyRecords;

            }finally {

                entityList.extras.loading = false;

            }

        },
        handleSearch() {

            this.listEntity({});

        },
        async openAuthenticationEvents(record) {

            this.authenticationEvents.user = record;
            this.authenticationEvents.records = {total: 0, data: [], links: []};
            this.authenticationEvents.filters = {
                eventType: AUTHENTICATION_EVENT_TYPES[0],
                result: AUTHENTICATION_RESULTS[0],
                dateFrom: "",
                dateTo: ""
            };

            Alerts.modals({type: "show", id: this.authenticationEvents.modalId});
            await this.listAuthenticationEvents({});

        },
        async listAuthenticationEvents(params = null) {

            const userId = this.authenticationEvents.user?.id;
            if(!userId) return;

            const url = this.isDefined(params) && typeof params === "object" ? params.url : params;
            const filters = {
                event_type: this.authenticationEvents.filters.eventType?.code || "",
                result: this.authenticationEvents.filters.result?.code || "",
                date_from: this.authenticationEvents.filters.dateFrom || "",
                date_to: this.authenticationEvents.filters.dateTo || ""
            };
            let route = url || `${this.routeActions.consult}/${userId}/authentication-events`;
            let data = filters;

            if(url) {
                const urlObj = new URL(url, window.location.origin);
                Object.entries(filters).forEach(([key, value]) => {
                    if(value && !urlObj.searchParams.has(key)) urlObj.searchParams.set(key, value);
                });
                route = `${urlObj.pathname}${urlObj.search}`;
                data = {};
            }

            this.authenticationEvents.loading = true;

            try {

                const response = await Requests.get({
                    route,
                    data,
                    showAlert: true
                });

                this.authenticationEvents.records = response?.data || {total: 0, data: [], links: []};

            }catch(error) {

                this.authenticationEvents.records = {total: 0, data: [], links: []};

            }finally {

                this.authenticationEvents.loading = false;

            }

        },
        onEmailInput(value) {

            this.forms[this.entity].createUpdate.data.email = (value ?? "").toString().toLowerCase();

        },
        // Forms
        openModal(record = null) {

            const entityForms = this.forms[this.entity].createUpdate;

            entityForms.errors = {};
            Forms.clearFormData(entityForms.data, this.MODULE.formFields);

            if(this.isDefined(record)) {

                // Map record data to form
                const genderOption               = this.genders.find(g => g.code === record?.gender),
                      identityDocumentTypeOption = this.identityDocumentTypes.find(e => e.code === record?.identity_document_type_id),
                      roleOption                 = this.roles.find(e => e.code === record?.role_id),
                      branchOptions              = this.branches.filter(branch => (record?.branches || []).some(recordBranch => Number(recordBranch.id) === Number(branch.code))),
                      cashRegisterOptions        = this.cashRegisters.filter(option => (record?.cash_registers || []).some(item => Number(item.id) === Number(option.code))),
                      warehouseOptions           = this.warehouses.filter(option => (record?.warehouses || []).some(item => Number(item.id) === Number(option.code))),
                      statusOption               = this.statuses.find(s => s.code === record?.status);

                entityForms.data.id                     = record.id;
                entityForms.data.role                   = roleOption;
                entityForms.data.branches               = branchOptions;
                entityForms.data.cashRegisters          = cashRegisterOptions;
                entityForms.data.warehouses             = warehouseOptions;
                entityForms.data.identity_document_type = identityDocumentTypeOption;
                entityForms.data.document_number        = record.document_number;
                entityForms.data.name                   = record.name;
                entityForms.data.email                  = record.email;
                entityForms.data.phone_number           = record.phone_number;
                entityForms.data.gender                 = genderOption;
                entityForms.data.birthdate              = record.birthdate;
                entityForms.data.status                 = statusOption;
                entityForms.data.password               = "";

            }else {

                // Set defaults for new record
                entityForms.data.role                   = this.roles.length > 0 ? this.roles[0] : null;
                entityForms.data.branches               = [];
                entityForms.data.cashRegisters          = [];
                entityForms.data.warehouses             = [];
                entityForms.data.identity_document_type = this.identityDocumentTypes.length > 1 ? this.identityDocumentTypes[1] : null;
                entityForms.data.gender                 = this.genders.length > 0 ? this.genders[0] : null;
                entityForms.data.status                 = this.statuses.length > 0 ? this.statuses[0] : null;
                entityForms.data.password               = this.generatePassword({length: 10});

            }

            Alerts.modals({type: "show", id: entityForms.extras.modals.default.id});
            Alerts.tooltips({show: true, time: 500});

        },
        openPasswordModal(record) {

            Alerts.tooltips({show: false});
            this.passwordModal.user = record;
            this.passwordModal.errors = {};
            this.passwordModal.form = {
                password: "",
                password_confirmation: ""
            };
            Alerts.modals({type: "show", id: this.passwordModal.id});

        },
        async savePassword() {

            if(this.passwordModal.saving || !this.passwordModal.user?.id) return;

            this.passwordModal.errors = {};

            if(!this.passwordModal.form.password) {
                this.passwordModal.errors.password = "Campo obligatorio.";
                return;
            }

            if(this.passwordModal.form.password !== this.passwordModal.form.password_confirmation) {
                this.passwordModal.errors.password_confirmation = "Debe coincidir con la nueva contraseña.";
                return;
            }

            this.passwordModal.saving = true;
            Alerts.swals({type: "loading", message: "Actualizando contraseña"});

            const result = await Requests.patch({
                route: `${this.routeActions.store}/${this.passwordModal.user.id}/password`,
                data: this.passwordModal.form
            });

            this.passwordModal.saving = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                Alerts.modals({type: "hide", id: this.passwordModal.id});
                Alerts.generateAlert({type: "success", msgContent: "Contraseña actualizada. Las sesiones activas fueron revocadas."});
                return;
            }

            this.passwordModal.errors = result?.errors || result?.data?.errors || {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.passwordModal.errors).flat().length
                    ? Object.values(this.passwordModal.errors).flat()
                    : [result?.data?.msg || "No se pudo cambiar la contraseña."]
            });

        },
        errorMessage(error) {

            return Array.isArray(error) ? (error[0] || "") : (error || "");

        },
        async saveEntity() {

            if(this.isSaving) return;

            const entityForms = this.forms[this.entity].createUpdate;

            Alerts.swals({});

            entityForms.errors = {};
            this.isSaving = true;

            try {

                const formData   = Utils.cloneJson(entityForms.data);
                const validation = this.validateFormData(formData);

                if(!validation.bool) {

                    Alerts.generateAlert({messages: Utils.getErrors({errors: validation.errors}), msgContent: this.config.messages.errorValidate});
                    this.isSaving = false;
                    return;

                }

                const preparedData  = Forms.prepareFormData(formData, this.MODULE.formFieldConfig);
                preparedData.branch_ids = (formData.branches || []).map(branch => branch?.code ?? branch).filter(Boolean);
                preparedData.cash_register_ids = (formData.cashRegisters || []).map(register => register?.code ?? register).filter(Boolean);
                preparedData.warehouse_ids = (formData.warehouses || []).map(warehouse => warehouse?.code ?? warehouse).filter(Boolean);
                delete preparedData.branches;
                delete preparedData.cashRegisters;
                delete preparedData.warehouses;
                const id            = preparedData.id;
                const isUpdate      = this.isDefined(id);
                const requestMethod = isUpdate ? "patch" : "post";
                const route         = this.routeActions[isUpdate ? "update" : "store"];
                const result        = await Requests[requestMethod]({route, data: preparedData, id});

                if(Requests.valid({result})) {

                    Alerts.modals({type: "hide", id: entityForms.extras.modals.default.id});
                    Alerts.generateAlert({type: "success", msgContent: result.data.msg});

                    Forms.clearFormData(entityForms.data, this.MODULE.formFields);

                    const entityList  = this.entityList;
                    const currentPage = entityList?.records?.current_page ?? 1;

                    this.listEntity({url: `${entityList?.extras?.route || ""}?page=${currentPage}`});

                }else {

                    Forms.handleFormResponseErrors({result, formErrorsObject: entityForms.errors, config: this.config});

                }

            }catch(error) {

                Alerts.generateAlert({type: "error", messages: [error], msgContent: this.config.messages.catchError});

            }finally {

                this.isSaving = false;

            }

        },
        validateFormData(formData) {

            const result = Forms.validateFormData(formData, this.validationRules, {isDescriptive: true, errorLabels: this.MODULE.errorLabels});

            // Custom validation for password (only for new users)
            if(!this.isDefined(formData.id) && !this.isDefined(formData.password)) {

                if(!result.errors.password) result.errors.password = [];

                result.errors.password.push(`${this.MODULE.errorLabels.password}: ${this.config.forms.errors.labels.required}`);
                result.bool = false;

            }

            return result;

        },
        // Others
        async searchDocumentNumber() {

            const entityForms          = this.forms[this.entity].createUpdate;
            const documentNumber       = entityForms.data.document_number;
            const identityDocumentType = entityForms.data.identity_document_type;

            if(!this.isDefined(documentNumber)) {

                Alerts.generateAlert({msgContent: "Debe ingresar el número de documento para realizar la búsqueda."});
                return;

            }

            if(!this.isDefined(identityDocumentType)) {

                Alerts.generateAlert({msgContent: "Debe seleccionar el tipo de documento."});
                return;

            }

            Alerts.swals({});

            const route    = Requests.config({entity: "helpers", type: "searchDocumentNumber"});
            const formJson = {document_number: documentNumber, type: identityDocumentType.data?.code};
            const response = await Requests.get({route, data: formJson});

            if(Requests.valid({result: response})) {

                const data = response.data.data;

                if(identityDocumentType.data?.code === "dni") {

                    entityForms.data.name = `${data?.first_name || ""} ${data?.last_name || ""} ${data?.second_last_name || ""}`.trim();

                }else if(identityDocumentType.data?.code === "ruc") {

                    entityForms.data.name = data?.legal_name || "";

                }

                Alerts.toastrs({type: "success", subtitle: response?.data?.msg});
                Alerts.swals({show: false});

            }else {

                Alerts.toastrs({type: "error", subtitle: response?.data?.msg});
                Alerts.swals({show: false});

            }

            Alerts.tooltips({show: false});

        },
        isDefined(value) {

            return Utils.isDefined({value});

        },
        generatePassword({length}) {

            return Utils.generatePassword({length});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        },
        tooltips({show = true, time = 10}) {

            Alerts.tooltips({show, time});

        },
        authEventLabel(value) {

            return AUTHENTICATION_EVENT_TYPES.find(option => option.code === value)?.label || value || "Sin evento";

        },
        authResultLabel(value) {

            return AUTHENTICATION_RESULTS.find(option => option.code === value)?.label || value || "Sin resultado";

        },
        branchAccessLabel(record) {

            const branches = record?.branches || [];
            const cashRegisters = record?.cash_registers || [];
            const warehouses = record?.warehouses || [];
            const scopes = [];

            if(branches.length) scopes.push(`${branches.length} suc.`);
            if(cashRegisters.length) scopes.push(`${cashRegisters.length} caja${cashRegisters.length === 1 ? "" : "s"}`);
            if(warehouses.length) scopes.push(`${warehouses.length} almacén${warehouses.length === 1 ? "" : "es"}`);

            if(!scopes.length) return "Hereda alcance del perfil";

            return scopes.join(" · ");

        }
    },
    computed: {
        entity() {

            return this.MODULE.config.entity;

        },
        routeActions() {

            return this.config.entity.routes;

        },
        entityList() {

            return this.lists[this.entity];

        },
        breadcrumbTitles() {

            return [
                {title: this.MODULE.config.breadcrumbParent},
                this.config.entity.page
            ];

        },
        roles() {

            return (this.options?.roles?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        branches() {

            return (this.options?.branches?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        cashRegisters() {

            const selectedBranchIds = (this.forms[this.entity].createUpdate.data.branches || [])
                .map(branch => Number(branch?.code ?? branch));

            return (this.options?.cashRegisters?.records ?? [])
                .filter(record => !selectedBranchIds.length || selectedBranchIds.includes(Number(record.branch_id)))
                .map(record => ({code: record.id, label: record.name, data: record}));

        },
        warehouses() {

            const selectedBranchIds = (this.forms[this.entity].createUpdate.data.branches || [])
                .map(branch => Number(branch?.code ?? branch));

            return (this.options?.warehouses?.records ?? [])
                .filter(record => !selectedBranchIds.length || selectedBranchIds.includes(Number(record.branch_id)))
                .map(record => ({code: record.id, label: record.name, data: record}));

        },
        identityDocumentTypes() {

            return (this.options?.identityDocumentTypes?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        genders() {

            return (this.options?.genders ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        statuses() {

            return (this.options?.statuses ?? []).map(e => ({code: e.code, label: e.label, data: e}));

        },
        isUpdate() {

            return this.isDefined(this.forms[this.entity].createUpdate.data.id);

        },
        modalTitles() {

            return {
                createUpdate: this.forms[this.entity].createUpdate.extras.modals.default.titles
            };

        },
        filterByOptions() {

            return this.MODULE.filterOptions;

        },
        authenticationEventTypes() {

            return AUTHENTICATION_EVENT_TYPES;

        },
        authenticationResults() {

            return AUTHENTICATION_RESULTS;

        },
        filterByValue: {
            get() {

                return this.entityList.filters?.filter_by || this.MODULE.filterOptions[0];

            },
            set(value) {

                this.entityList.filters.filter_by = value;

            }
        },
        filterWordValue: {
            get() {

                return this.entityList.filters.word || "";

            },
            set(value) {

                this.entityList.filters.word = value;

            }
        },
        searchPlaceholder() {

            const filterBy = this.entityList.filters.filter_by;

            if(!filterBy) return "Buscar...";

            return `Buscar por ${(filterBy.label || "...").toLowerCase()}`;

        },
        // Identity document type
        isDocumentTypeSearchable() {

            const documentType = this.forms[this.entity].createUpdate.data.identity_document_type?.data;

            return documentType?.is_searchable === true || documentType?.is_searchable === 1;

        },
        documentNumberMinLength() {

            const documentType = this.forms[this.entity].createUpdate.data.identity_document_type?.data;

            if(documentType?.min_length) {

                return parseInt(documentType.min_length);

            }

            return 1;

        },
        documentNumberMaxLength() {

            const documentType = this.forms[this.entity].createUpdate.data.identity_document_type?.data;

            if(documentType?.max_length) {

                return parseInt(documentType.max_length);

            }

            return 1;

        },
        validationRules() {

            const rules = Utils.cloneJson(this.MODULE.validationRules);

            rules.document_number = {
                ...rules.document_number,
                minLength: this.documentNumberMinLength,
                maxLength: this.documentNumberMaxLength,
                custom: (value) => validateOnlyDigits(value, this.MODULE.errorLabels.document_number)
            };

            return rules;

        }
    },
    watch: {
        "forms.users.createUpdate.data.branches": {
            handler() {

                const formData = this.forms[this.entity].createUpdate.data;
                const cashRegisterIds = this.cashRegisters.map(option => Number(option.code));
                const warehouseIds = this.warehouses.map(option => Number(option.code));

                formData.cashRegisters = (formData.cashRegisters || [])
                    .filter(option => cashRegisterIds.includes(Number(option?.code ?? option)));
                formData.warehouses = (formData.warehouses || [])
                    .filter(option => warehouseIds.includes(Number(option?.code ?? option)));

            },
            deep: true
        },
        "forms.users.createUpdate.data.identity_document_type": {
            handler(newValue) {

                const maxLength    = this.documentNumberMaxLength;
                const currentValue = this.forms[this.entity].createUpdate.data.document_number?.toString() || "";

                if(currentValue.length > maxLength) {

                    this.forms[this.entity].createUpdate.data.document_number = currentValue.substring(0, maxLength);

                }

            },
            immediate: false
        }
    }
};
</script>

<style scoped>
</style>
