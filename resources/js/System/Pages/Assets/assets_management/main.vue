<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <!-- Content -->
    <div class="row align-items-end g-3 mb-3 mb-md-4">
        <InputSlot
            hasDiv
            title="Sucursal"
            :titleClass="[config.forms.classes.title]"
            xl="6"
            lg="7">
            <template v-slot:input>
                <v-select
                    v-model="lists.entity.filters.branch"
                    :options="branches"
                    :class="config.forms.classes.select2"
                    :clearable="false"
                    :searchable="false"/>
            </template>
        </InputSlot>
        <InputSlot
            hasDiv
            :isInputGroup="false"
            :divInputClass="['d-flex flex-wrap justify-content-start gap-2 gap-md-3']"
            xl="6"
            lg="5">
            <template v-slot:input>
                <button type="button" class="br-btn br-btn-search waves-effect" @click="listEntity({})" :disabled="lists.entity.extras.loading">
                    <span>Buscar</span>
                </button>
            </template>
        </InputSlot>
    </div>
    <div class="row g-3" v-if="isDefined({value: lists.entity.filters.branch?.code})">
        <template v-if="!lists.entity.extras.loading">
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                <h5 class="fw-bold mb-2 text-uppercase ps-1">Activos disponibles</h5>
                <div class="list-group" v-if="availableAssets.length > 0">
                    <div v-for="record in availableAssets" :key="record.code" class="list-group-item bg-white">
                        <div class="d-flex justify-content-between align-items-center gap-2 gap-md-3">
                            <div>
                                <span v-text="record.internal_code" class="fw-bold d-block"></span>
                                <span v-text="record.name" class="d-block"></span>
                                <span v-text="record.formatted_management_type" class="d-block text-primary fw-semibold"></span>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" @click="assignAssetToBranch({record})" v-if="hasAssetToBranch(record)">Agregar</button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center">
                    <WithoutData type="image"/>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                <h5 class="fw-bold mb-2 text-uppercase ps-1">Activos asignados a la sucursal</h5>
                <div class="list-group" v-if="lists.entity.records.total > 0">
                    <div v-for="record in lists.entity.records.data" :key="record.id" class="list-group-item bg-white">
                        <div class="d-flex justify-content-between align-items-center gap-2 gap-md-3">
                            <div>
                                <span v-text="record.asset?.internal_code" class="fw-bold d-block"></span>
                                <span v-text="record.asset?.name" class="d-block mb-1"></span>
                                <span v-text="record.asset?.formatted_management_type" class="d-block text-primary fw-semibold mb-1"></span>
                                <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                            </div>
                            <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 gap-md-3">
                                <button class="btn btn-sm btn-warning" @click="modalAssetInBranch({record})" v-if="['active', 'maintenance'].includes(record.status)">Editar</button>
                                <button class="btn btn-sm btn-primary" @click="modalAssignToUser({record})" v-if="record.quantity > 0 && ['active', 'maintenance'].includes(record.status)">Gestionar asignación</button>
                                <button class="btn btn-sm btn-danger" @click="unassignAssetFromBranch({record})" v-if="['active', 'maintenance'].includes(record.status)">Quitar</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center">
                    <WithoutData type="image"/>
                </div>
            </div>
        </template>
    </div>

    <!-- Modals -->
    <div class="modal fade" :id="forms.entity.assetInBranch.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.assetInBranch.extras.modals.default.titles[isAssign ? 'update' : 'store']"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <label class="fw-bold colon-at-end">Activo</label>
                            <div>
                                <span v-text="forms.entity.assetInBranch.data?.asset?.name"></span>
                                (<span v-text="forms.entity.assetInBranch.data?.asset?.internal_code" class="fw-bold"></span>)
                            </div>
                        </div>
                        <InputNumber
                            v-model="forms.entity.assetInBranch.data.quantity"
                            hasDiv
                            title="Cantidad"
                            hasTextBottom
                            :textBottomInfo="forms.entity.assetInBranch.errors?.quantity"
                            xl="4"
                            lg="4"/>
                        <InputNumber
                            v-model="forms.entity.assetInBranch.data.acquisition_value"
                            hasDiv
                            title="Valor de adquisición"
                            hasTextBottom
                            :textBottomInfo="forms.entity.assetInBranch.errors?.acquisition_value"
                            xl="4"
                            lg="4"/>
                        <InputDate
                            v-model="forms.entity.assetInBranch.data.acquisition_date"
                            hasDiv
                            title="Fecha de adquisición"
                            hasTextBottom
                            :textBottomInfo="forms.entity.assetInBranch.errors?.acquisition_date"
                            xl="4"
                            lg="4"/>
                        <InputText
                            v-model="forms.entity.assetInBranch.data.note"
                            hasDiv
                            title="Nota"
                            maxlength="100"
                            hasTextBottom
                            :textBottomInfo="forms.entity.assetInBranch.errors?.note"
                            xl="8"
                            lg="8"/>
                        <InputSlot
                            hasDiv
                            title="Estado"
                            isRequired
                            hasTextBottom
                            :textBottomInfo="forms.entity.assetInBranch.errors?.status"
                            xl="4"
                            lg="4">
                            <template v-slot:input>
                                <v-select
                                    v-model="forms.entity.assetInBranch.data.status"
                                    :options="statuses"
                                    :clearable="false"
                                    :searchable="false"/>
                            </template>
                        </InputSlot>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" :class="['btn waves-effect', isAssign ? 'btn-warning' : 'btn-primary']" @click="assetInBranch()">
                        <i class="fa fa-save"></i>
                        <span class="ms-2">Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" :id="forms.entity.assignToUser.extras.modals.default.id" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase fw-bold" v-text="forms.entity.assignToUser.extras.modals.default.titles.default"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <label class="fw-bold colon-at-end">Activo</label>
                            <div>
                                <span v-text="forms.entity.assignToUser.data?.branchAsset?.asset?.name"></span>
                                (<span v-text="forms.entity.assignToUser.data?.branchAsset?.asset?.internal_code" class="fw-bold"></span>)
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <label class="fw-bold colon-at-end">Cantidad total</label>
                            <div>
                                <span v-text="forms.entity.assignToUser.data?.branchAsset?.quantity"></span>
                            </div>
                        </div>
                    </div>
                    <div v-if="false && ['stock'].includes(forms.entity.assignToUser.data?.branchAsset?.asset?.management_type)">
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <label class="fw-bold colon-at-end">Colaborador asignado</label>
                            <div>
                                <span v-text="forms.entity.assignToUser?.data?.assignments[0]?.user?.name"></span>
                            </div>
                        </div>
                        <InputSlot
                            hasDiv
                            title="Cambiar colaborador asignado"
                            isRequired
                            hasTextBottom
                            :textBottomInfo="forms.entity.assignToUser.errors?.user"
                            xl="12"
                            lg="12">
                            <template v-slot:input>
                                <v-select
                                    v-model="forms.entity.assignToUser.data.user"
                                    :options="users"
                                    :clearable="true"
                                    :searchable="true"/>
                            </template>
                        </InputSlot>
                    </div>
                    <div v-else-if="['unit'].includes(forms.entity.assignToUser.data?.branchAsset?.asset?.management_type)" class="mt-2">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr class="text-center align-middle">
                                        <th class="bg-secondary text-white fw-semibold" style="width: 10%;">#</th>
                                        <th class="bg-secondary text-white fw-semibold" style="width: 40%;">COLABORADOR<br/>ASIGNADO</th>
                                        <th class="bg-secondary text-white fw-semibold" style="width: 20%;">CANTIDAD</th>
                                        <th class="bg-secondary text-white fw-semibold" style="width: 15%;">ESTADO</th>
                                        <th class="bg-secondary text-white fw-semibold" style="width: 15%;"></th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0 bg-white">
                                    <template v-if="(forms.entity.assignToUser?.data?.assignments ?? []).length > 0">
                                        <template v-for="(record, keyRecord) in forms.entity.assignToUser?.data?.assignments" :key="record.id">
                                            <template v-if="isNumber({value: record.id})">
                                                <tr class="text-center">
                                                    <td v-text="keyRecord + 1"></td>
                                                    <td v-text="record?.user?.name" class="text-start fw-bold"></td>
                                                    <td class="text-start">
                                                        <span v-text="record?.quantity" class="ps-2"></span>
                                                    </td>
                                                    <td>
                                                        <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-danger btn-xs waves-effect" type="button" @click="unassignToUser({record, keyRecord})">
                                                            <i class="fa fa-times"></i>
                                                            <span class="ms-1">Retirar</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template v-else>
                                                <tr class="text-center">
                                                    <td v-text="keyRecord + 1"></td>
                                                    <td class="text-start">
                                                        <InputSlot
                                                            :hasDiv="false">
                                                            <template v-slot:input>
                                                                <v-select
                                                                    v-model="record.user"
                                                                    :options="users"
                                                                    :clearable="false"
                                                                    :searchable="true"/>
                                                            </template>
                                                        </InputSlot>
                                                    </td>
                                                    <td class="text-start">
                                                        <InputNumber
                                                            v-model="record.quantity"
                                                            :hasDiv="false"/>
                                                    </td>
                                                    <td>
                                                        <StatusBadge
                                                            :status="record.status"
                                                            :formatted-status="record.formatted_status"
                                                            :custom-variants="statusBadgeAssetMaintenancePrimary"/>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-danger btn-xs waves-effect" type="button" @click="unassignToUser({record, keyRecord})">
                                                            <i class="fa fa-times"></i>
                                                            <span class="ms-1">Retirar</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>
                                        <tr class="fs-5">
                                            <td colspan="2" class="fw-bold text-end">TOTAL :</td>
                                            <td colspan="3" class="fw-bold text-start">
                                                <span class="text-break">
                                                    <span v-text="separatorNumber(assignmentsSum)" class="ms-2"></span>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr class="fs-5">
                                            <td colspan="2" class="fw-bold text-end text-secondary">PENDIENTE :</td>
                                            <td colspan="3" class="fw-bold text-start text-secondary">
                                                <span class="text-break">
                                                    <span v-text="separatorNumber(assignmentsPending)" class="ms-2"></span>
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <tr>
                                            <td class="text-center" colspan="99">
                                                <WithoutData type="text"/>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-start align-items-center flex-grap mt-2">
                            <a href="javascript:void(0)" @click="addAssignment({})" class="fw-bold">
                                <i class="fa fa-plus-circle"></i>
                                <span class="ms-1">Agregar asignación</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" :class="['btn waves-effect', 'btn-primary']" @click="assignToUser()">
                        <i class="fa fa-save"></i>
                        <span class="ms-2">Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";
import {STATUS_BADGE_CUSTOM_ASSET_MAINTENANCE_PRIMARY} from "@System/Helpers/ModuleConstants.js";

export default {
    mounted: async function() {

        Utils.navbarItem("menu-parent-infrastructure", {addClass: "open"});
        Utils.navbarItem(this.config.entity.page.menu.id, {});
        Alerts.swals({type: "initParams"});

        let initParams = await this.initParams({}),
            initOthers = await this.initOthers({});

        if(initParams && initOthers) {

            Alerts.swals({show: false});
            // this.listEntity({});

        }

    },
    data() {
        return {
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        route: Requests.config({entity: "assets_management", type: "list"})
                    },
                    filters: {
                        branch: null
                    },
                    records: {
                        total: 0
                    }
                }
            },
            forms: {
                entity: {
                    assetInBranch: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    titles: {
                                        store: "Agregar",
                                        update: "Editar"
                                    }
                                }
                            }
                        },
                        data: {},
                        errors: {}
                    },
                    assignToUser: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    titles: {
                                        default: "Gestionar asignación"
                                    }
                                }
                            }
                        },
                        data: {
                            branchAsset: null,
                            assignments: []
                        },
                        errors: {}
                    }
                }
            },
            options: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "assets_management"}),
                    page: {
                        title: "Gestión de activos",
                        active: true,
                        menu: {
                            id: "menu-infrastructure-assets_management"
                        }
                    }
                }
            }
        };
    },
    methods: {
        // Init
        async initParams({}) {

            let initParams = await Requests.get({route: this.config.entity.routes.initParams, data: {page: "main"}, showAlert: true});

            this.options.assets   = initParams.data?.config?.assets;
            this.options.branches = initParams.data?.config?.branches;
            this.options.users    = initParams.data?.config?.users;
            this.options.assetAssignments = initParams.data?.config?.assetAssignments;
            this.options.branchAssets     = initParams.data?.config?.branchAssets;

            return Requests.valid({result: initParams});

        },
        async initOthers({}) {

            return new Promise(resolve => {

                this.lists.entity.filters.branch = this.branches[0];

                resolve(true);

            });

        },
        // Entity forms
        async listEntity({url = null}) {

            let filters = Utils.cloneJson(this.lists.entity.filters);
            const filterJson = {branch_id: filters?.branch?.code};

            this.lists.entity.extras.loading = true;
            this.lists.entity.records        = (await Requests.get({route: url || this.lists.entity.extras.route, data: filterJson}))?.data;
            this.lists.entity.extras.loading = false;

        },
        // Forms - To branch
        hasAssetToBranch(record) {

            return !this.lists.entity.records.data.some(e => e.asset_id == record.id && ["active", "maintenance"].includes(e.status));

        },
        async assignAssetToBranch({record = null}) {

            let el = this;

            Swal.fire({
                html: `<span class="d-block mb-1">¿Deseas agregar el activo a la sucursal?</span>
                       <div class="d-flex justify-content-start align-items-center mt-3">
                            <div class="form-group text-start">
                                <label class="form-label colon-at-end required">Cantidad</label>
                                <label class="text-danger ms-1 fw-bold">*</label>
                                <div class="input-group">
                                    <input type="number" class="form-control input-number-no-range" id="quantityId"/>
                                </div>
                            </div>
                       </div>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: "Sí, agregar",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: "btn btn-success waves-effect",
                    cancelButton: "btn btn-secondary waves-effect ms-3"
                },
                preConfirm: () => {

                    const quantity = document.getElementById("quantityId").value.trim();

                    if(!el.isDefined({value: quantity})) {

                        Swal.showValidationMessage("La cantidad es obligatoria");
                        return false;

                    }else if(!(Number(quantity) > 0.1)) {

                        Swal.showValidationMessage(`La cantidad ${el.config.forms.errors.functions.min.numeric(0.1).toLowerCase()}`);
                        return false;

                    }

                    return { quantity };

                }
            })
            .then(async function(result) {

                if(result.isConfirmed) {

                    const functionName = "assignAssetToBranch";

                    Alerts.swals({});
                    el.formErrors({functionName, type: "clear"});

                    let form = Utils.cloneJson({branch_id: el.lists.entity.filters.branch?.code, branch_assets: [{asset_id: record.id, quantity: result.value.quantity}]});

                    const validateForm = el.validateForm({functionName, form, extras: {type: "descriptive"}});

                    if(validateForm?.bool) {

                        let assignAssetToBranch = await Requests.post({route: el.config.entity.routes.assignAssetToBranch, data: form});

                        if(Requests.valid({result: assignAssetToBranch})) {

                            // Alerts.toastrs({type: "success", subtitle: assignAssetToBranch?.data?.msg});
                            // Alerts.swals({show: false});
                            Alerts.generateAlert({type: "success", msgContent: assignAssetToBranch?.data?.msg});

                            // el.clearForm({functionName});
                            el.listEntity({url: `${el.lists.entity.extras.route}?page=${el.lists.entity.records?.current_page ?? 1}`});

                        }else {

                            el.formErrors({functionName, type: "set", errors: assignAssetToBranch?.errors ?? []});
                            Alerts.toastrs({type: "error", subtitle: assignAssetToBranch?.data?.msg});
                            Alerts.swals({show: false});

                        }

                    }else {

                        // el.formErrors({functionName, type: "set", errors: validateForm});
                        // Alerts.toastrs({type: "error", subtitle: el.config.messages.errorValidate});
                        // Alerts.swals({show: false});
                        Alerts.generateAlert({messages: validateForm?.msg, msgContent: `<div class="fw-semibold mb-2">${el.config.messages.errorValidate}</div>`});

                    }

                }else if(result.isDismissed) {

                    //

                }

            });

        },
        async unassignAssetFromBranch({record = null}) {

            let el = this;

            Swal.fire({
                html: `<span>¿Deseas quitar el activo de la sucursal?</span>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: "Sí, quitar",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: "btn btn-danger waves-effect",
                    cancelButton: "btn btn-secondary waves-effect ms-3"
                }
            })
            .then(async function(result) {

                if(result.isConfirmed) {

                    const functionName = "unassignAssetFromBranch";

                    Alerts.swals({});
                    el.formErrors({functionName, type: "clear"});

                    let form = Utils.cloneJson({branch_id: el.lists.entity.filters.branch?.code, branch_assets: [{id: record.id, asset_id: record.asset_id}]});

                    const validateForm = el.validateForm({functionName, form, extras: {type: "descriptive"}});

                    if(validateForm?.bool) {

                        let unassignAssetFromBranch = await Requests.post({route: el.config.entity.routes.unassignAssetFromBranch, data: form});

                        if(Requests.valid({result: unassignAssetFromBranch})) {

                            // Alerts.toastrs({type: "success", subtitle: unassignAssetFromBranch?.data?.msg});
                            // Alerts.swals({show: false});
                            Alerts.generateAlert({type: "success", msgContent: unassignAssetFromBranch?.data?.msg});

                            // el.clearForm({functionName});
                            el.listEntity({url: `${el.lists.entity.extras.route}?page=${el.lists.entity.records?.current_page ?? 1}`});

                        }else {

                            el.formErrors({functionName, type: "set", errors: unassignAssetFromBranch?.errors ?? []});
                            Alerts.toastrs({type: "error", subtitle: unassignAssetFromBranch?.data?.msg});
                            Alerts.swals({show: false});

                        }

                    }else {

                        // el.formErrors({functionName, type: "set", errors: validateForm});
                        // Alerts.toastrs({type: "error", subtitle: el.config.messages.errorValidate});
                        // Alerts.swals({show: false});
                        Alerts.generateAlert({messages: validateForm?.msg, msgContent: `<div class="fw-semibold mb-2">${el.config.messages.errorValidate}</div>`});

                    }

                }else if(result.isDismissed) {

                    //

                }

            });

        },
        // Forms - In branch
        modalAssetInBranch({record = null}) {

            const functionName = "modalAssetInBranch";

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                let status = this.statuses.find(e => e.code === record?.status);

                this.forms.entity.assetInBranch.data = {...record, status};

            }else {

                //

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.assetInBranch.extras.modals.default.id});
            this.tooltips({show: true, time: 500});

        },
        async assetInBranch() {

            const functionName = "assetInBranch";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.assetInBranch.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive"}});

            if(validateForm?.bool) {

                form.status = form?.status?.code;

                delete form.asset;
                delete form.formatted_status;
                delete form.created_at;
                delete form.created_by;
                delete form.updated_at;
                delete form.updated_by;

                let assetInBranch = await (this.isDefined({value: form.id}) ? Requests.patch({route: this.config.entity.routes.assetInBranch, data: form, id: form.id}) :
                                                                              Requests.post({route: this.config.entity.routes.assetInBranch, data: form}));

                if(Requests.valid({result: assetInBranch})) {

                    Alerts.modals({type: "hide", id: this.forms.entity.assetInBranch.extras.modals.default.id});
                    // Alerts.toastrs({type: "success", subtitle: assetInBranch?.data?.msg});
                    // Alerts.swals({show: false});
                    Alerts.generateAlert({type: "success", msgContent: assetInBranch?.data?.msg});

                    // this.clearForm({functionName});
                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: assetInBranch?.errors ?? []});
                    Alerts.toastrs({type: "error", subtitle: assetInBranch?.data?.msg});
                    Alerts.swals({show: false});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                // Alerts.swals({show: false});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

        },
        // Forms - To user
        async modalAssignToUser({record = null}) {

            const functionName = "modalAssignToUser";

            Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                const filterJson = {branch_id: record?.branch_id, asset_id: record?.asset_id};

                const getAssetAssignments = await Requests.get({route: this.config.entity.routes.getAssetAssignments, data: filterJson});
                const assignments = Requests.valid({result: getAssetAssignments}) ? getAssetAssignments?.data?.assignments : [];

                let status = this.statuses.find(e => e.code === record?.status);

                this.forms.entity.assignToUser.data = {branchAsset: {...record, status}, assignments};

            }else {

                return;

            }

            Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.assignToUser.extras.modals.default.id, timeout: 1});
            this.tooltips({show: true, time: 500});

        },
        addAssignment() {

            this.forms.entity.assignToUser.data.assignments.push({id: Utils.uuid(), user: {code: "", label: ""}, quantity: 0});

        },
        async assignToUser() {

            const functionName = "assignToUser";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.assignToUser.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive"}});

            if(validateForm?.bool) {

                form.assignments.forEach(record => {

                    if(!this.isNumber({value: record.id})) {

                        record.user_id = record?.user?.code;

                    }

                    delete record.user;

                });

                form.branch_asset_id = form.branchAsset.id;
                form.branch_id       = form.branchAsset.branch_id;
                form.asset_id        = form.branchAsset.asset_id;

                delete form.branchAsset;

                let assignToUser = await Requests.post({route: this.config.entity.routes.assignToUser, data: form});

                if(Requests.valid({result: assignToUser})) {

                    Alerts.modals({type: "hide", id: this.forms.entity.assignToUser.extras.modals.default.id});
                    // Alerts.toastrs({type: "success", subtitle: assignToUser?.data?.msg});
                    // Alerts.swals({show: false});
                    Alerts.generateAlert({type: "success", msgContent: assignToUser?.data?.msg});

                    this.clearForm({functionName});
                    // this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: assignToUser?.errors ?? []});
                    Alerts.toastrs({type: "error", subtitle: assignToUser?.data?.msg});
                    Alerts.swals({show: false});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                // Alerts.swals({show: false});
                Alerts.generateAlert({messages: validateForm?.msg, msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

        },
        async unassignToUser({record, keyRecord}) {

            let el = this;

            Swal.fire({
                html: `<span>¿Desea retirar la asignación al colaborador?</span>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: "Sí, retirar",
                cancelButtonText: "Cancelar",
                customClass: {
                    confirmButton: "btn btn-danger waves-effect",
                    cancelButton: "btn btn-secondary waves-effect ms-3"
                }
            })
            .then(async function(result) {

                if(result.isConfirmed) {

                    if(el.isNumber({value: record.id})) {

                        const functionName = "unassignToUser";

                        Alerts.swals({});
                        el.formErrors({functionName, type: "clear"});

                        let form = Utils.cloneJson({assignments: [{id: record.id, user_id: record.user_id}]});

                        form.branch_asset_id = el.forms.entity.assignToUser.data.branchAsset.id;
                        form.branch_id       = record.branch_id;
                        form.asset_id        = record.asset_id;

                        const validateForm = el.validateForm({functionName, form, extras: {type: "descriptive"}});

                        if(validateForm?.bool) {

                            let unassignToUser = await Requests.post({route: el.config.entity.routes.unassignToUser, data: form});

                            if(Requests.valid({result: unassignToUser})) {

                                // Alerts.toastrs({type: "success", subtitle: unassignToUser?.data?.msg});
                                // Alerts.swals({show: false});
                                Alerts.generateAlert({type: "success", msgContent: unassignToUser?.data?.msg});

                                // el.clearForm({functionName});
                                el.forms.entity.assignToUser.data.assignments.splice(keyRecord, 1);

                            }else {

                                el.formErrors({functionName, type: "set", errors: unassignToUser?.errors ?? []});
                                Alerts.toastrs({type: "error", subtitle: unassignToUser?.data?.msg});
                                Alerts.swals({show: false});

                            }

                        }else {

                            // el.formErrors({functionName, type: "set", errors: validateForm});
                            // Alerts.toastrs({type: "error", subtitle: el.config.messages.errorValidate});
                            // Alerts.swals({show: false});
                            Alerts.generateAlert({messages: validateForm?.msg, msgContent: `<div class="fw-semibold mb-2">${el.config.messages.errorValidate}</div>`});

                        }

                    }else {

                        el.forms.entity.assignToUser.data.assignments.splice(keyRecord, 1);

                    }

                }else if(result.isDismissed) {

                    //

                }

            });

        },
        // Forms utils
        clearForm({functionName}) {

            switch(functionName) {
                case "modalAssetInBranch":
                    //
                    break;

                case "modalAssignToUser":
                case "assignToUser":
                    this.forms.entity.assignToUser.data.branchAsset = null;
                    this.forms.entity.assignToUser.data.assignments = [];
                    break;
            }

        },
        formErrors({functionName, type = "clear", errors = []}) {

            if(["modalAssetInBranch", "assetInBranch"].includes(functionName)) {

                this.forms.entity.assetInBranch.errors = ["set"].includes(type) ? errors : [];

            }else if(["modalAssignToUser", "assignToUser"].includes(functionName)) {

                this.forms.entity.assignToUser.errors = ["set"].includes(type) ? errors : [];

            }

        },
        validateForm({functionName, form = null, extras = null}) {

            let result = {
                bool: true
            };

            if(["assignAssetToBranch"].includes(functionName)) {

                result.msg = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                if(!this.isDefined({value: form?.branch_id})) {

                    result.msg.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.branch_assets}) || (form.branch_assets).length === 0) {

                    result.msg.push(`${isDescriptive ? "Activos:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }else {

                    for(let [keyDetail, detail] of Object.entries(form?.branch_assets)) {

                        let errorDetails = [];

                        if(!this.isDefined({value: detail?.asset_id})) {

                            errorDetails.push(`${isDescriptive ? "Activo:" : ""} ${this.config.forms.errors.labels.required}`);
                            result.bool = false;

                        }

                        if(!(Number(detail?.quantity) > 0.1)) {

                            errorDetails.push(`${isDescriptive ? "Cantidad:" : ""} ${this.config.forms.errors.functions.min.numeric(0.1)}`);
                            result.bool = false;

                        }

                        if(errorDetails.length > 0) {

                            result.msg.push(`<p class="mb-1">Detalle <b>#${parseInt(keyDetail) + 1}</b>:</p>${errorDetails.map(e => "<li>"+e+"</li>").join("")}`);

                        }

                    }

                }

            }else if(["unassignAssetFromBranch"].includes(functionName)) {

                result.msg = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                if(!this.isDefined({value: form?.branch_id})) {

                    result.msg.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.branch_assets}) || (form.branch_assets).length === 0) {

                    result.msg.push(`${isDescriptive ? "Activos:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }else {

                    for(let [keyDetail, detail] of Object.entries(form?.branch_assets)) {

                        let errorDetails = [];

                        if(!this.isDefined({value: detail?.id})) {

                            errorDetails.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                            result.bool = false;

                        }

                        if(!this.isDefined({value: detail?.asset_id})) {

                            errorDetails.push(`${isDescriptive ? "Activo:" : ""} ${this.config.forms.errors.labels.required}`);
                            result.bool = false;

                        }

                        if(errorDetails.length > 0) {

                            result.msg.push(`<p class="mb-1">Detalle <b>#${parseInt(keyDetail) + 1}</b>:</p>${errorDetails.map(e => "<li>"+e+"</li>").join("")}`);

                        }

                    }

                }

            }else if(["assetInBranch"].includes(functionName)) {

                result.msg = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                if(!this.isDefined({value: form?.id})) {

                    result.msg.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.branch_id})) {

                    result.msg.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.asset_id})) {

                    result.msg.push(`${isDescriptive ? "Activo:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!(Number(form?.quantity) > 0.1)) {

                    result.msg.push(`${isDescriptive ? "Cantidad:" : ""} ${this.config.forms.errors.functions.min.numeric(0.1)}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.status})) {

                    result.msg.push(`${isDescriptive ? "Estado:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

            }else if(["assignToUser"].includes(functionName)) {

                result.msg = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                if(!this.isDefined({value: form?.branchAsset?.id})) {

                    result.msg.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.branchAsset?.branch_id})) {

                    result.msg.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.branchAsset?.asset_id})) {

                    result.msg.push(`${isDescriptive ? "Activo:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(false && ["stock"].includes(form?.branchAsset?.asset?.management_type)) {

                    if(!this.isDefined({value: form?.user})) {

                        result.msg.push(`${isDescriptive ? "Colaborador:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }else if(["unit"].includes(form?.branchAsset?.asset?.management_type)) {

                    let totalQuantity = form.assignments.reduce((acumulator, currentValue) => acumulator + Number(currentValue?.quantity), 0);

                    if(totalQuantity == 0) {

                        result.msg.push(`La suma de las cantidades asignadas debe ser mayor a 0.`);
                        result.bool = false;

                    }else if(totalQuantity > Number(form?.branchAsset?.quantity)) {

                        result.msg.push(`La suma de las cantidades asignadas debe ser menor o igual a la cantidad total del activo.`);
                        result.bool = false;

                    }

                    for(let [keyDetail, detail] of Object.entries(form?.assignments)) {

                        let errorDetails = [];

                        if(!this.isDefined({value: detail?.user?.code}) && !this.isDefined({value: detail?.user?.id})) {

                            errorDetails.push(`Colaborador: ${this.config.forms.errors.labels.required}`);
                            result.bool = false;

                        }

                        if(!(Number(detail?.quantity) > 0.1)) {

                            errorDetails.push(`${isDescriptive ? "Cantidad:" : ""} ${this.config.forms.errors.functions.min.numeric(0.1)}`);
                            result.bool = false;

                        }

                        if(errorDetails.length > 0) {

                            result.msg.push(`<p class="mb-1">Asignación <b>#${parseInt(keyDetail) + 1}</b>:</p>${errorDetails.map(e => "<li>"+e+"</li>").join("")}`);

                        }

                    }

                }

            }else if(["unassignToUser"].includes(functionName)) {

                result.msg = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                if(!this.isDefined({value: form?.branch_asset_id})) {

                    result.msg.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.branch_id})) {

                    result.msg.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.asset_id})) {

                    result.msg.push(`${isDescriptive ? "Activo:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.assignments}) || (form.assignments).length === 0) {

                    result.msg.push(`${isDescriptive ? "Asignaciones:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }else {

                    for(let [keyDetail, detail] of Object.entries(form?.assignments)) {

                        let errorDetails = [];

                        if(!this.isDefined({value: detail?.id})) {

                            errorDetails.push(`Registro: ${this.config.forms.errors.labels.required}`);
                            result.bool = false;

                        }

                        if(!this.isDefined({value: detail?.user_id})) {

                            errorDetails.push(`Colaborador: ${this.config.forms.errors.labels.required}`);
                            result.bool = false;

                        }

                        if(errorDetails.length > 0) {

                            result.msg.push(`<p class="mb-1">Asignación <b>#${parseInt(keyDetail) + 1}</b>:</p>${errorDetails.map(e => "<li>"+e+"</li>").join("")}`);

                        }

                    }

                }

            }

            return result;

        },
        // Others
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        isNumber({value, minValue = 0}) {

            return Utils.isNumber({value, minValue});

        },
        fixedNumber(value, decimals = null) {

            return Utils.fixedNumber(value, decimals);

        },
        separatorNumber(value) {

            return Utils.separatorNumber(value);

        },
        tooltips({show = true, time = 10}) {

            Alerts.tooltips({show, time});

        }
    },
    computed: {
        breadcrumbTitles: function() {

            return [{title: "Infraestructura"}, this.config.entity.page];

        },
        assets: function() {

            return this.options?.assets?.records.map(e => ({code: e.id, label: e.name, data: e}));

        },
        availableAssets: function() {

            return this.options?.assets?.records;

        },
        branches: function() {

            return this.options?.branches?.records.map(e => ({code: e.id, label: e.name, data: e}));

        },
        users: function() {

            return this.options?.users?.records.map(e => ({code: e.id, label: e.name, data: e}));

        },
        statuses: function() {

            return this.options?.branchAssets?.statuses.map(e => ({code: e.code, label: e.label}));

        },
        assignmentStatuses: function() {

            return this.options?.assetAssignments?.statuses.map(e => ({code: e.code, label: e.label}));

        },
        isAssign: function() {

            return this.isDefined({value: this.forms.entity.assetInBranch.data?.id});

        },
        assignmentsSum: function() {

            let total = 0;

            for(let detail of this.forms.entity.assignToUser.data.assignments) {

                total += Number(detail?.quantity);

            }

            return this.fixedNumber(total);

        },
        assignmentsPending: function() {

            let calculate = Number(this.forms.entity.assignToUser.data?.branchAsset?.quantity ?? 0) - Number(this.assignmentsSum);

            return this.fixedNumber(calculate > 0 ? calculate : 0);

        },
        statusBadgeAssetMaintenancePrimary() {
            return STATUS_BADGE_CUSTOM_ASSET_MAINTENANCE_PRIMARY;
        }
    },
    watch: {
        "lists.entity.filters.branch": function(newValue, oldValue) {

            if(this.isDefined({value: this.lists.entity.filters.branch?.code})) {

                this.listEntity({});

            }else {

                this.lists.entity.records = {
                    total: 0,
                    data: []
                };

            }

        }
    }
};
</script>
