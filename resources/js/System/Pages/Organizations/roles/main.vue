<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <main class="br-entity br-roles">
        <section class="br-filter-bar">
            <div class="row align-items-end g-2">
                <InputText
                    v-model="filters.word"
                    hasDiv
                    title="Búsqueda"
                    :titleClass="[config.forms.classes.title]"
                    placeholder="Buscar perfil de acceso"
                    xl="8"
                    lg="8"
                    @enterKeyPressed="listRoles({})"/>
                <div class="form-group col-xl-4 col-lg-4">
                    <div class="br-filter-bar__actions">
                        <button type="button" class="br-btn br-btn-sm br-btn-search" @click="listRoles({})">
                            <span>Buscar</span>
                        </button>
                        <button
                            type="button"
                            class="br-btn br-btn-sm br-btn-action-open-create"
                            data-bs-toggle="modal"
                            data-bs-target="#roleModal"
                            @click="openModal()">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Agregar perfil</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="br-entity-list">
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Perfil de acceso</th>
                            <th>Tipo</th>
                            <th>Módulos permitidos</th>
                            <th>Colaboradores</th>
                            <th>Estado</th>
                            <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="py-4"><Loader/></td>
                        </tr>
                        <template v-else-if="records.total > 0">
                            <tr v-for="role in records.data" :key="role.id">
                                <td>
                                    <strong>{{ role.name }}</strong>
                                    <span class="br-roles__meta">
                                        Se asigna desde Colaboradores
                                    </span>
                                </td>
                                <td>
                                    <span :class="['br-roles__access-type', {'is-full': role.is_full_access}]">
                                        {{ roleAccessType(role) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="br-roles__metric" :title="roleModuleTooltip(role)">
                                        {{ roleModuleSummary(role) }}
                                    </span>
                                </td>
                                <td>{{ role.users_count || 0 }}</td>
                                <td>
                                    <span :class="['br-status-label', `br-status-${role.status}`]">
                                        {{ role.formatted_status || statusLabel(role.status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="br-table-actions">
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#roleModal"
                                        data-bs-placement="top"
                                        title="Editar perfil"
                                        :aria-label="`Editar perfil ${role.name}`"
                                        @click="openModal(role)">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-info"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="Duplicar perfil"
                                        :aria-label="`Duplicar perfil ${role.name}`"
                                        @click="duplicateRole(role)">
                                        <i class="fa-solid fa-copy" aria-hidden="true"></i>
                                    </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="6"><WithoutData type="image"/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="records.links" class="d-flex justify-content-center mt-3">
            <Paginator :links="records.links" @clickPage="listRoles"/>
        </div>
    </main>

    <div id="roleModal" class="modal fade br-entity-modal br-modal-standard" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Configuración</p>
                        <h2 class="modal-title br-entity-modal__title">
                            {{ editingId ? "Editar perfil de acceso" : "Agregar perfil de acceso" }}
                        </h2>
                        <p class="br-roles__modal-help">
                            El sistema lo guarda como rol técnico, pero para el usuario representa el perfil de acceso que se asigna a cada colaborador.
                        </p>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="modal-body br-entity-modal__body br-modal-standard__body">
                    <div class="row g-3">
                        <InputText
                            v-model="form.name"
                            hasDiv
                            title="Nombre del perfil"
                            :titleClass="[config.forms.classes.title]"
                            isRequired
                            maxlength="80"
                            showCharCounter
                            hasTextBottom
                            :textBottomInfo="firstError(errors.name)"
                            xl="5"
                            lg="5"/>
                        <div class="form-group col-xl-3 col-lg-3">
                            <label class="form-label">Estado</label>
                            <v-select
                                v-model="form.status"
                                :options="statusOptions"
                                :clearable="false"
                                :searchable="false"/>
                            <small v-if="errors.status" class="text-danger">{{ firstError(errors.status) }}</small>
                        </div>
                        <div class="form-group col-xl-4 col-lg-4">
                            <label class="br-entity-switch br-roles__full-access" for="roleFullAccess">
                                <input
                                    id="roleFullAccess"
                                    v-model="form.isFullAccess"
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch">
                                <span>
                                    <strong>Acceso total</strong>
                                    <small>El colaborador podrá entrar a todos los módulos habilitados para la empresa.</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="br-roles__permissions">
                        <div class="br-roles__permissions-head">
                            <div>
                                <strong>Módulos disponibles</strong>
                                <small>Selecciona lo que verá y podrá abrir el colaborador con este perfil.</small>
                            </div>
                            <button
                                type="button"
                                class="br-btn br-btn-sm br-btn-outline-secondary"
                                :disabled="form.isFullAccess"
                                @click="toggleAllModules">
                                {{ allModulesSelected ? "Quitar selección" : "Seleccionar todo" }}
                            </button>
                        </div>

                        <div class="br-roles__permission-tools">
                            <div class="br-roles__permission-search">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input
                                    v-model.trim="moduleSearch"
                                    type="search"
                                    class="form-control"
                                    placeholder="Buscar módulo o sección"
                                    aria-label="Buscar módulo o sección">
                            </div>
                            <label class="br-roles__selected-filter">
                                <input v-model="showSelectedOnly" type="checkbox" :disabled="form.isFullAccess">
                                <span>Ver solo seleccionados</span>
                            </label>
                        </div>

                        <div class="br-roles__sections">
                            <article v-for="section in sections" :key="section.id" class="br-roles__section">
                                <header>
                                    <div>
                                        <strong>{{ section.dom_label }}</strong>
                                        <small>{{ selectedInSection(section) }} de {{ section.totalModules }} módulos</small>
                                    </div>
                                    <button
                                        type="button"
                                        class="br-roles__section-action"
                                        :disabled="form.isFullAccess"
                                        @click="toggleSection(section)">
                                        {{ isSectionSelected(section) ? "Quitar" : "Seleccionar" }}
                                    </button>
                                </header>
                                <div class="br-roles__modules">
                                    <div
                                        v-for="module in section.subSections"
                                        :key="module.id"
                                        :class="['br-roles__module', {'is-selected': isSelected(module.id), 'is-disabled': form.isFullAccess}]">
                                        <label class="br-roles__module-main">
                                            <input
                                                type="checkbox"
                                                :checked="isSelected(module.id)"
                                                :disabled="form.isFullAccess"
                                                @change="toggleModule(module.id)">
                                            <span>
                                                <strong>{{ module.dom_label }}</strong>
                                                <small>{{ module.description }}</small>
                                            </span>
                                        </label>
                                        <div v-if="isSelected(module.id)" class="br-roles__actions" aria-label="Acciones permitidas">
                                            <label
                                                v-for="action in actionsForModule(module)"
                                                :key="`${module.id}-${action.code}`"
                                                :title="action.description">
                                                <input
                                                    type="checkbox"
                                                    :checked="hasAction(module.id, action.code)"
                                                    :disabled="form.isFullAccess"
                                                    @change="toggleAction(module.id, action.code)">
                                                <span>{{ action.label }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <small v-if="errors.permissions || errors.sub_section_ids" class="text-danger">
                            {{ firstError(errors.permissions || errors.sub_section_ids) }}
                        </small>
                    </div>

                    <section class="br-roles__scopes">
                        <header class="br-roles__scopes-head">
                            <strong>Alcance operativo</strong>
                            <small>Limita dónde puede trabajar el perfil. Estas restricciones también se validan en el servidor.</small>
                        </header>
                        <div class="br-roles__scope-grid">
                            <article v-for="scope in scopeDefinitions" :key="scope.type" class="br-roles__scope">
                                <label class="br-entity-switch" :for="`role-scope-${scope.type}`">
                                    <input
                                        :id="`role-scope-${scope.type}`"
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        :checked="form[scope.modeField] === 'restricted'"
                                        :disabled="form.isFullAccess"
                                        @change="setScopeMode(scope, $event.target.checked)">
                                    <span>
                                        <strong>{{ scope.title }}</strong>
                                        <small>{{ scope.help }}</small>
                                    </span>
                                </label>
                                <v-select
                                    v-if="form[scope.modeField] === 'restricted' && !form.isFullAccess"
                                    v-model="form[scope.selectionField]"
                                    :options="scope.options"
                                    :clearable="true"
                                    :searchable="scope.options.length > 6"
                                    multiple
                                    append-to-body
                                    :placeholder="scope.placeholder">
                                    <template #selected-option="{label}">
                                        <span class="br-select-selected-text" :title="label">{{ label }}</span>
                                    </template>
                                </v-select>
                                <small v-if="errors[scope.errorField]" class="text-danger">
                                    {{ firstError(errors[scope.errorField]) }}
                                </small>
                            </article>
                        </div>
                    </section>
                </div>

                <div class="modal-footer br-entity-modal__footer">
                    <button ref="closeModal" type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button
                        type="button"
                        :class="['br-btn', editingId ? 'br-btn-action-update' : 'br-btn-action-create']"
                        :disabled="saving"
                        @click="saveRole">
                        {{ editingId ? "Actualizar perfil" : "Agregar perfil" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

export default {
    mounted() {
        Utils.navbarItem("menu-parent-configuration", {addClass: "open"});
        Utils.navbarItem("menu-configuration-roles", {});
        this.initParams();
        this.listRoles({});
    },
    data() {
        return {
            loading: false,
            saving: false,
            editingId: null,
            records: {total: 0, data: []},
            filters: {word: ""},
            options: {
                sections: [],
                statuses: [],
                permissionActions: [],
                branches: [],
                cashRegisters: [],
                warehouses: []
            },
            moduleSearch: "",
            showSelectedOnly: false,
            form: this.defaultForm(),
            errors: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "roles"}),
                    page: {
                        title: "Perfiles de acceso",
                        active: true,
                        menu: {id: "menu-configuration-roles"}
                    }
                }
            }
        };
    },
    methods: {
        defaultForm() {
            return {
                name: "",
                isFullAccess: false,
                selectedIds: [],
                selectedActions: {},
                branchScopeMode: "all",
                cashRegisterScopeMode: "all",
                warehouseScopeMode: "all",
                branches: [],
                cashRegisters: [],
                warehouses: [],
                status: {code: "active", label: "Activo"}
            };
        },
        async initParams() {
            Alerts.swals({type: "initParams"});
            const result = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });
            this.options.sections = result.data?.config?.sections?.records || [];
            this.options.statuses = result.data?.config?.statuses || [];
            this.options.permissionActions = result.data?.config?.permissionActions || [];
            this.options.branches = result.data?.config?.branches || [];
            this.options.cashRegisters = result.data?.config?.cashRegisters || [];
            this.options.warehouses = result.data?.config?.warehouses || [];
            Alerts.swals({show: false});
        },
        async listRoles({url = null} = {}) {
            this.loading = true;
            const result = await Requests.get({
                route: url || this.config.entity.routes.list,
                data: {word: this.filters.word}
            });
            this.records = result.data || {total: 0, data: []};
            this.loading = false;
        },
        openModal(role = null) {
            this.errors = {};
            this.editingId = role?.id || null;
            this.form = this.defaultForm();
            this.moduleSearch = "";
            this.showSelectedOnly = false;

            if(role) {
                const selectedActions = {};
                (role.role_sub_sections || []).forEach(permission => {
                    selectedActions[Number(permission.sub_section_id)] = permission.actions?.length
                        ? permission.actions
                        : this.actionCodes;
                });

                this.form = {
                    name: role.name || "",
                    isFullAccess: Boolean(role.is_full_access),
                    selectedIds: (role.role_sub_sections || []).map(permission => Number(permission.sub_section_id)),
                    selectedActions,
                    branchScopeMode: role.branch_scope_mode || "all",
                    cashRegisterScopeMode: role.cash_register_scope_mode || "all",
                    warehouseScopeMode: role.warehouse_scope_mode || "all",
                    branches: this.matchOptions(this.branchOptions, role.branches),
                    cashRegisters: this.matchOptions(this.cashRegisterOptions, role.cash_registers),
                    warehouses: this.matchOptions(this.warehouseOptions, role.warehouses),
                    status: this.statusOptions.find(option => option.code === role.status) || this.statusOptions[0]
                };
            }
        },
        isSelected(id) {
            return this.form.selectedIds.includes(Number(id));
        },
        toggleModule(id) {
            const moduleId = Number(id);

            if(this.isSelected(moduleId)) {
                this.form.selectedIds = this.form.selectedIds.filter(selectedId => selectedId !== moduleId);
                const selectedActions = {...this.form.selectedActions};
                delete selectedActions[moduleId];
                this.form.selectedActions = selectedActions;
                return;
            }

            this.form.selectedIds = [...this.form.selectedIds, moduleId];
            this.form.selectedActions = {
                ...this.form.selectedActions,
                [moduleId]: this.actionCodesForModule(moduleId)
            };
        },
        hasAction(moduleId, actionCode) {
            return (this.form.selectedActions[Number(moduleId)] || []).includes(actionCode);
        },
        actionsForModule(module) {
            const allowed = module?.delegable_actions;

            return Array.isArray(allowed)
                ? this.permissionActions.filter(action => allowed.includes(action.code))
                : this.permissionActions;
        },
        actionCodesForModule(moduleId) {
            const module = this.availableSections
                .flatMap(section => section.subSections)
                .find(item => Number(item.id) === Number(moduleId));

            return this.actionsForModule(module).map(action => action.code);
        },
        toggleAction(moduleId, actionCode) {
            const id = Number(moduleId);
            const current = [...(this.form.selectedActions[id] || [])];

            if(current.includes(actionCode)) {
                if(actionCode === "view" && current.length > 1) return;
                if(current.length === 1) return;
                this.form.selectedActions = {
                    ...this.form.selectedActions,
                    [id]: current.filter(code => code !== actionCode)
                };
                return;
            }

            this.form.selectedActions = {
                ...this.form.selectedActions,
                [id]: [...current, actionCode]
            };
        },
        toggleSection(section) {
            const ids = this.sectionModules(section).map(module => Number(module.id));
            const selected = this.isSectionSelected(section);

            ids.forEach(id => {
                if(selected === this.isSelected(id)) this.toggleModule(id);
            });
        },
        toggleAllModules() {
            const ids = this.availableSections.flatMap(section => section.subSections.map(module => Number(module.id)));
            const selected = this.allModulesSelected;
            ids.forEach(id => {
                if(selected === this.isSelected(id)) this.toggleModule(id);
            });
        },
        isSectionSelected(section) {
            return this.sectionModules(section).every(module => this.isSelected(module.id));
        },
        selectedInSection(section) {
            return this.sectionModules(section).filter(module => this.isSelected(module.id)).length;
        },
        sectionModules(section) {
            return this.availableSections.find(availableSection => availableSection.id === section.id)?.subSections || section.subSections;
        },
        matchOptions(options, records = []) {
            const ids = (records || []).map(record => Number(record.id));
            return options.filter(option => ids.includes(Number(option.code)));
        },
        setScopeMode(scope, restricted) {
            this.form[scope.modeField] = restricted ? "restricted" : "all";
            if(!restricted) this.form[scope.selectionField] = [];
        },
        async saveRole() {
            if(this.saving) return;

            const canContinue = await this.confirmRoleImpact();
            if(!canContinue) return;

            this.saving = true;
            this.errors = {};
            Alerts.swals({
                type: "loading",
                message: this.editingId ? "Actualizando perfil" : "Agregando perfil"
            });

            const data = {
                name: this.form.name,
                is_full_access: this.form.isFullAccess,
                permissions: this.form.isFullAccess ? [] : this.form.selectedIds.map(id => ({
                    sub_section_id: id,
                    actions: this.form.selectedActions[id] || this.actionCodesForModule(id)
                })),
                branch_scope_mode: this.form.isFullAccess ? "all" : this.form.branchScopeMode,
                cash_register_scope_mode: this.form.isFullAccess ? "all" : this.form.cashRegisterScopeMode,
                warehouse_scope_mode: this.form.isFullAccess ? "all" : this.form.warehouseScopeMode,
                branch_ids: this.optionIds(this.form.branches),
                cash_register_ids: this.optionIds(this.form.cashRegisters),
                warehouse_ids: this.optionIds(this.form.warehouses),
                status: this.form.status?.code
            };
            const result = this.editingId
                ? await Requests.patch({route: this.config.entity.routes.store, id: this.editingId, data})
                : await Requests.post({route: this.config.entity.routes.store, data});

            this.saving = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                this.$refs.closeModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listRoles({});
                return;
            }

            this.errors = result?.errors || result?.data?.errors || {};
            Alerts.generateAlert({
                type: "error",
                messages: Object.values(this.errors).flat().length
                    ? Object.values(this.errors).flat()
                    : [result?.data?.msg || "No se pudo guardar el perfil."]
            });
        },
        async confirmRoleImpact() {

            if(!this.editingId) return true;

            const currentRole = (this.records.data || []).find(role => Number(role.id) === Number(this.editingId));
            const affectedUsers = Number(currentRole?.users_count || 0);

            if(affectedUsers <= 0) return true;

            const willDisable = this.form.status?.code !== "active";
            const removesFullAccess = Boolean(currentRole?.is_full_access) && !this.form.isFullAccess;

            if(!willDisable && !removesFullAccess) return true;

            const confirmation = await Swal.fire({
                title: "Revisar perfil asignado",
                html: `
                    <p>Este perfil está asignado a <strong>${affectedUsers}</strong> colaborador${affectedUsers === 1 ? "" : "es"} activo${affectedUsers === 1 ? "" : "s"}.</p>
                    <p class="mb-0">Si continúas, sus accesos cambiarán de inmediato. El backend bloqueará la operación si este cambio deja a la empresa sin administrador activo.</p>
                `,
                icon: "warning",
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: "Continuar",
                cancelButtonText: "Cancelar",
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--warning",
                    confirmButton: "br-btn br-swal-alert__confirm br-swal-alert__confirm--warning",
                    cancelButton: "br-btn br-btn-cancel ms-2"
                }
            });

            return confirmation.isConfirmed;

        },
        async duplicateRole(role) {
            if(this.saving || !role?.id) return;

            Alerts.tooltips({show: false});

            const confirmation = await Swal.fire({
                title: "Duplicar perfil",
                text: "Asigna un nombre para el nuevo perfil. Se copiarán sus permisos y alcances operativos.",
                icon: "question",
                input: "text",
                inputValue: this.suggestDuplicateName(role),
                inputAttributes: {
                    maxlength: 80,
                    autocapitalize: "off",
                    autocomplete: "off"
                },
                allowOutsideClick: false,
                allowEscapeKey: false,
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: "Duplicar perfil",
                cancelButtonText: "Cancelar",
                customClass: {
                    container: "br-swal-backdrop",
                    popup: "br-swal-alert br-swal-alert--question",
                    confirmButton: "br-btn br-swal-alert__confirm br-swal-alert__confirm--question",
                    cancelButton: "br-btn br-btn-cancel ms-2",
                    input: "br-swal-alert__input"
                },
                inputValidator: value => {
                    const name = String(value || "").trim();

                    if(!name) return "Ingresa el nombre del nuevo perfil.";
                    if(name.length > 80) return "El nombre no debe superar 80 caracteres.";

                    return null;
                }
            });

            if(!confirmation.isConfirmed) {
                Alerts.tooltips({time: 250});
                return;
            }

            this.saving = true;
            Alerts.swals({type: "loading", message: "Duplicando perfil"});

            const result = await Requests.post({
                route: `${this.config.entity.routes.store}/${role.id}/duplicate`,
                data: {name: confirmation.value.trim()}
            });

            this.saving = false;
            Alerts.swals({show: false});

            if(Requests.valid({result})) {
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listRoles({});
                return;
            }

            const errors = result?.errors || result?.data?.errors || {};
            const messages = Object.values(errors).flat();

            Alerts.generateAlert({
                type: "error",
                messages: messages.length ? messages : [result?.data?.msg || "No se pudo duplicar el perfil."]
            });
        },
        suggestDuplicateName(role) {
            const baseName = `Copia de ${role.name || "perfil"}`.slice(0, 80);
            const existingNames = (this.records.data || []).map(record => String(record.name || "").toLowerCase());

            if(!existingNames.includes(baseName.toLowerCase())) return baseName;

            for(let index = 2; index <= 20; index++) {
                const suffix = ` ${index}`;
                const candidate = `${baseName.slice(0, 80 - suffix.length)}${suffix}`;

                if(!existingNames.includes(candidate.toLowerCase())) return candidate;
            }

            return baseName;
        },
        firstError(error) {
            return Array.isArray(error) ? error[0] : error;
        },
        optionIds(options = []) {
            return options.map(option => Number(option?.code ?? option)).filter(Boolean);
        },
        statusLabel(status) {
            return this.statusOptions.find(option => option.code === status)?.label || status;
        },
        roleAccessType(role) {
            return role.is_full_access ? "Administrador" : "Limitado";
        },
        roleModuleSummary(role) {
            return role.is_full_access
                ? "Todos los módulos"
                : `${role.modules_count || 0} módulos`;
        },
        roleModuleTooltip(role) {
            return role.is_full_access
                ? "Acceso a todos los módulos habilitados para la empresa"
                : "Acceso solo a los módulos seleccionados";
        }
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Configuración"}, this.config.entity.page];
        },
        availableSections() {
            return (this.options.sections || [])
                .map(section => ({
                    ...section,
                    subSections: section.sub_sections || section.subSections || []
                }))
                .filter(section => section.subSections.length);
        },
        sections() {
            const search = this.moduleSearch.toLowerCase();

            return this.availableSections
                .map(section => {
                    const sectionText = `${section.dom_label || ""} ${section.name || ""}`.toLowerCase();
                    const sectionMatches = search && sectionText.includes(search);
                    const subSections = section.subSections.filter(module => {
                        const moduleText = `${module.dom_label || ""} ${module.description || ""} ${module.name || ""}`.toLowerCase();
                        const matchesSearch = !search || sectionMatches || moduleText.includes(search);
                        const matchesSelection = !this.showSelectedOnly || this.isSelected(module.id);

                        return matchesSearch && matchesSelection;
                    });

                    return {
                        ...section,
                        totalModules: section.subSections.length,
                        subSections
                    };
                })
                .filter(section => section.subSections.length);
        },
        statusOptions() {
            return this.options.statuses.length
                ? this.options.statuses
                : [{code: "active", label: "Activo"}, {code: "inactive", label: "Inactivo"}];
        },
        allModulesSelected() {
            const ids = this.availableSections.flatMap(section => section.subSections.map(module => Number(module.id)));
            return ids.length > 0 && ids.every(id => this.isSelected(id));
        },
        permissionActions() {
            return this.options.permissionActions.length
                ? this.options.permissionActions
                : [
                    {code: "view", label: "Ver"},
                    {code: "create", label: "Crear"},
                    {code: "update", label: "Editar"},
                    {code: "delete", label: "Eliminar"},
                    {code: "export", label: "Exportar"},
                    {code: "import", label: "Importar"},
                    {code: "operate", label: "Operar"}
                ];
        },
        actionCodes() {
            return this.permissionActions.map(action => action.code);
        },
        branchOptions() {
            return this.options.branches.map(record => ({code: record.id, label: record.name, data: record}));
        },
        cashRegisterOptions() {
            const branchIds = this.optionIds(this.form.branches);
            return this.options.cashRegisters
                .filter(record => !branchIds.length || branchIds.includes(Number(record.branch_id)))
                .map(record => ({code: record.id, label: record.name, data: record}));
        },
        warehouseOptions() {
            const branchIds = this.optionIds(this.form.branches);
            return this.options.warehouses
                .filter(record => !branchIds.length || branchIds.includes(Number(record.branch_id)))
                .map(record => ({code: record.id, label: record.name, data: record}));
        },
        scopeDefinitions() {
            return [
                {
                    type: "branch",
                    title: "Restringir sucursales",
                    help: "El perfil solo podrá operar en las sucursales seleccionadas.",
                    modeField: "branchScopeMode",
                    selectionField: "branches",
                    errorField: "branch_ids",
                    options: this.branchOptions,
                    placeholder: "Seleccionar sucursales"
                },
                {
                    type: "cash-register",
                    title: "Restringir cajas",
                    help: "Aplica a apertura, cierre, movimientos y ventas POS.",
                    modeField: "cashRegisterScopeMode",
                    selectionField: "cashRegisters",
                    errorField: "cash_register_ids",
                    options: this.cashRegisterOptions,
                    placeholder: "Seleccionar cajas"
                },
                {
                    type: "warehouse",
                    title: "Restringir almacenes",
                    help: "Aplica a existencias, compras, ventas y traslados.",
                    modeField: "warehouseScopeMode",
                    selectionField: "warehouses",
                    errorField: "warehouse_ids",
                    options: this.warehouseOptions,
                    placeholder: "Seleccionar almacenes"
                }
            ];
        }
    },
    watch: {
        "form.branches": {
            handler() {
                const cashRegisterIds = this.cashRegisterOptions.map(option => Number(option.code));
                const warehouseIds = this.warehouseOptions.map(option => Number(option.code));

                this.form.cashRegisters = this.form.cashRegisters
                    .filter(option => cashRegisterIds.includes(Number(option?.code ?? option)));
                this.form.warehouses = this.form.warehouses
                    .filter(option => warehouseIds.includes(Number(option?.code ?? option)));
            },
            deep: true
        }
    }
};
</script>
