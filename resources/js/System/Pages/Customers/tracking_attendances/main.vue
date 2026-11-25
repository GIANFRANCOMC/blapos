<template>
    <Breadcrumb :list="breadcrumbTitles"/>
    <FgPageHeader title="Asistencias de clientes" description="Registra ingresos y salidas según la membresía vigente.">
        <template #actions>
            <FgButton icon="fa-solid fa-check" :disabled="initialLoading || !parametersReady" @click="selectModeEntity('manual', true)">
                Registrar asistencia
            </FgButton>
            <FgButton tone="secondary" icon="fa-solid fa-globe" :disabled="initialLoading || !parametersReady" @click="modalAutomaticEntity({type: 'generate'})">
                Modo público
            </FgButton>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content fg-attendance-page">
            <FgEmptyState v-if="!parametersReady" title="No pudimos preparar las asistencias"
                description="Vuelve a cargar las sucursales y los clientes disponibles.">
                <FgButton tone="secondary" @click="initialize">Volver a intentar</FgButton>
            </FgEmptyState>
            <template v-else>
                <form class="fg-filters fg-filter-grid fg-attendance-filters" role="search" aria-label="Filtrar asistencias"
                    @submit.prevent="listEntity({applyFilters: true})">
                    <FgField label="Sucursal">
                        <FgSelect v-model="lists.entity.filters.branch" :options="branches" :clearable="false"
                            :searchable="false" placeholder="Elige una sucursal"/>
                    </FgField>
                    <FgField label="Cliente">
                        <FgSelect v-model="lists.entity.filters.customer" :options="customers" placeholder="Documento o nombre"/>
                    </FgField>
                    <FgField label="Desde" v-slot="{id}">
                        <div class="fg-field__control">
                            <input :id="id" v-model="lists.entity.filters.start_date" class="fg-input" type="date"
                                :max="lists.entity.filters.end_date || undefined"/>
                        </div>
                    </FgField>
                    <FgField label="Hasta" v-slot="{id}">
                        <div class="fg-field__control">
                            <input :id="id" v-model="lists.entity.filters.end_date" class="fg-input" type="date"
                                :min="lists.entity.filters.start_date || undefined"/>
                        </div>
                    </FgField>
                    <FgField label="Estado">
                        <FgSelect :model-value="lists.entity.filters.status || 'all'" :options="attendanceStatusOptions"
                            :reduce="option => option.code" :clearable="false" :searchable="false"
                            @update:model-value="lists.entity.filters.status = $event === 'all' ? '' : $event"/>
                    </FgField>
                    <div class="fg-filter-grid__actions">
                        <FgFilterActions submit :loading="lists.entity.extras.loading" @clear="clearFilters"/>
                    </div>
                </form>

                <section class="fg-attendance-results" aria-labelledby="attendance-results-title">
                    <div class="fg-list-heading fg-attendance-list-heading">
                        <h2 id="attendance-results-title" class="fg-section-title">Registro de ingresos y salidas</h2>
                        <FgButton tone="secondary" icon="fa-solid fa-download"
                            :disabled="lists.entity.extras.loading || lists.entity.extras.error || !lists.entity.appliedFilters?.branch"
                            @click="exportEntity">Exportar CSV</FgButton>
                    </div>
                    <ResponsiveTable>
                        <table class="fg-table fg-record-table fg-attendance-table" role="table" aria-labelledby="attendance-results-title">
                            <thead role="rowgroup">
                                <tr role="row">
                                    <th scope="col" role="columnheader">Cliente</th>
                                    <th scope="col" role="columnheader">Sucursal</th>
                                    <th scope="col" role="columnheader">Ingreso</th>
                                    <th scope="col" role="columnheader">Salida</th>
                                    <th scope="col" role="columnheader">Registro</th>
                                    <th scope="col" role="columnheader">Estado</th>
                                    <th scope="col" role="columnheader"><span class="fg-sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="row" v-if="lists.entity.extras.loading" class="fg-table__state">
                                    <td role="cell" colspan="7"><FgListLoading label="Buscando asistencias…"/></td>
                                </tr>
                                <tr role="row" v-else-if="lists.entity.extras.error" class="fg-table__state">
                                    <td role="cell" colspan="7">
                                        <FgEmptyState title="No pudimos consultar las asistencias" :description="lists.entity.extras.error">
                                            <FgButton tone="secondary" @click="listEntity({url: lists.entity.extras.lastUrl})">Volver a intentar</FgButton>
                                        </FgEmptyState>
                                    </td>
                                </tr>
                                <tr role="row" v-else-if="!lists.entity.records.data?.length" class="fg-table__state">
                                    <td role="cell" colspan="7">
                                        <FgEmptyState title="No hay asistencias para esta consulta"
                                            description="Prueba con otra sucursal, cliente o periodo."/>
                                    </td>
                                </tr>
                                <template v-else>
                                    <tr role="row" v-for="record in lists.entity.records.data" :key="record.id">
                                        <td role="cell" class="fg-record-table__identity">
                                            <div class="fg-attendance-client">
                                                <strong>{{ record.customer?.name || 'Cliente no disponible' }}</strong>
                                                <small v-if="record.customer?.document_number" class="fg-help">Documento {{ record.customer.document_number }}</small>
                                            </div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Sucursal</span>
                                            <span class="fg-record-table__value">{{ record.branch?.name || '—' }}</span>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Ingreso</span>
                                            <span class="fg-record-table__value">{{ record.start_date ? legibleFormatDate({dateString: record.start_date, type: 'datetime'}) : 'Pendiente' }}</span>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Salida</span>
                                            <span class="fg-record-table__value">{{ record.end_date ? legibleFormatDate({dateString: record.end_date, type: 'datetime'}) : 'Pendiente' }}</span>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Registro</span>
                                            <div class="fg-record-table__value fg-attendance-client">
                                                <span>{{ getAttendanceDeviceLabel(record) }}</span>
                                                <small v-if="latestAttendanceCorrection(record)" class="fg-help">{{ latestAttendanceCorrectionLabel(record) }}</small>
                                            </div>
                                        </td>
                                        <td role="cell" class="fg-record-table__state">
                                            <StatusBadge :status="record.status" :formatted-status="record.formatted_status"/>
                                        </td>
                                        <td role="cell" class="fg-record-table__actions">
                                            <ActionsDropdown v-if="['active', 'finalized'].includes(record.status)"
                                                :label="'Acciones de ' + (record.customer?.name || 'la asistencia')">
                                                <li v-if="record.status === 'active'"><button type="button" class="dropdown-item fg-menu__item"
                                                    @click="modalCreateUpdateEntity({record, type: 'finalized'})">Concluir asistencia</button></li>
                                                <li><button type="button" class="dropdown-item fg-menu__item"
                                                    @click="requestCorrectionEntity(record)">Solicitar corrección</button></li>
                                                <li><button type="button" class="dropdown-item fg-menu__item fg-menu__item--danger"
                                                    @click="modalCreateUpdateEntity({record, type: 'canceled'})">Anular asistencia</button></li>
                                            </ActionsDropdown>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </ResponsiveTable>
                    <Paginator v-if="!lists.entity.extras.error" :links="lists.entity.records.links"
                        :pagination="lists.entity.records" :loading="lists.entity.extras.loading" @click-page="listEntity"/>
                </section>

                <details v-if="lists.entity.records.data?.length" class="fg-attendance-activity">
                    <summary class="fg-section-title">Actividad de los registros visibles</summary>
                    <div class="fg-attendance-activity__body">
                        <ul class="fg-attendance-activity__counts" aria-label="Asistencias por estado">
                            <li v-for="status in attendanceActivity.statuses" :key="status.code">
                                <StatusBadge :status="status.code" :formatted-status="status.label"/>
                                <strong>{{ status.total }}</strong>
                            </li>
                        </ul>
                        <ResponsiveTable>
                            <table class="fg-table" aria-label="Ingresos y salidas agrupados por hora">
                                <thead><tr><th scope="col">Franja horaria</th><th scope="col">Ingresos</th><th scope="col">Salidas</th></tr></thead>
                                <tbody>
                                    <tr v-for="interval in attendanceActivity.intervals" :key="interval.label">
                                        <td>{{ interval.label }}</td><td>{{ interval.entries }}</td><td>{{ interval.exits }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </ResponsiveTable>
                    </div>
                </details>
            </template>
        </div>
    </InitialLoadTransition>

    <FormModal
        :modal-id="forms.entity.createUpdate.extras.modals.default.id"
        :title="forms.entity.createUpdate.extras.modals.default.titles[forms.entity.createUpdate.extras.modals.default.type] || 'Registrar asistencia'"
        :submit-button-text="forms.entity.createUpdate.extras.modals.default.config.labels[forms.entity.createUpdate.extras.modals.default.type]"
        :submit-button-class="forms.entity.createUpdate.extras.modals.default.type === 'canceled' ? 'fg-button--danger' : ''"
        dialog-class="fg-attendance-modal"
        body-class="fg-attendance-modal__body"
        @submit="forms.entity.createUpdate.extras.modals.default.type === 'canceled' ? cancelEntity({}) : createUpdateEntity()">
        <AttendanceModeTabs v-if="forms.entity.createUpdate.extras.modals.default.type === 'store'"
            :modes="formModes" current="manual" @select="selectModeEntity($event, false)"/>
        <div class="fg-attendance-modal__fields">
            <FgField label="Sucursal" required v-slot="{id}">
                <div v-if="forms.entity.createUpdate.data.id" class="fg-field__control">
                    <input :id="id" class="fg-input" type="text"
                        :value="forms.entity.createUpdate.data.branch?.label || 'Sucursal no disponible'" readonly/>
                </div>
                <FgSelect v-else v-model="forms.entity.createUpdate.data.branch" :options="branches" :clearable="false"
                    :searchable="false" placeholder="Elige una sucursal"/>
            </FgField>
            <FgField label="Cliente" required v-slot="{id}">
                <div v-if="forms.entity.createUpdate.data.id" class="fg-field__control">
                    <input :id="id" class="fg-input" type="text"
                        :value="forms.entity.createUpdate.data.customer?.label || 'Cliente no disponible'" readonly/>
                </div>
                <FgSelect v-else v-model="forms.entity.createUpdate.data.customer" :options="customers" placeholder="Documento o nombre"/>
            </FgField>
            <FgField v-if="forms.entity.createUpdate.data.start_date"
                :label="forms.entity.createUpdate.extras.modals.default.type === 'store' ? 'Hora de registro' : 'Ingreso'" v-slot="{id}">
                <div class="fg-field__control">
                    <input :id="id" class="fg-input" type="text" readonly
                        :value="legibleFormatDate({dateString: forms.entity.createUpdate.data.start_date})"/>
                </div>
            </FgField>
            <FgField v-if="forms.entity.createUpdate.data.end_date" label="Salida" v-slot="{id}">
                <div class="fg-field__control">
                    <input :id="id" class="fg-input" type="text" readonly
                        :value="legibleFormatDate({dateString: forms.entity.createUpdate.data.end_date})"/>
                </div>
            </FgField>
        </div>
        <p v-if="forms.entity.createUpdate.extras.modals.default.type === 'store'" class="fg-attendance-modal__notice">
            La membresía debe estar vigente en la sucursal seleccionada. El sistema identifica si corresponde un ingreso o una salida.
        </p>
    </FormModal>

    <FormModal
        :modal-id="forms.entity.qrCamera.extras.modals.default.id"
        title="Registrar asistencia" :show-submit="false" dialog-class="fg-attendance-modal" body-class="fg-attendance-modal__body">
        <AttendanceModeTabs :modes="formModes" current="qrCamera" @select="selectModeEntity($event, false)"/>
        <FgField label="Sucursal" required>
            <FgSelect v-model="forms.entity.qrCamera.data.branch" :options="branches" :clearable="false"
                :searchable="false" placeholder="Elige una sucursal"/>
        </FgField>
        <FgListLoading v-if="forms.entity.qrCamera.config.isProcessing" label="Finalizando escaneo…"/>
        <div class="fg-attendance-camera" v-show="!forms.entity.qrCamera.config.isProcessing">
            <CodeScanner
                ref="scannerQr" :show-controls="true" :qrbox="240" :fps="23" :limit-scan="-1"
                :can-process="forms.entity.qrCamera.config.isCurrentActive && !forms.entity.qrCamera.config.isProcessing"
                @result="onResultQrCamera"/>
        </div>
        <ResponsiveTable v-if="forms.entity.qrCamera.data.customers.length > 1">
            <table class="fg-table" aria-label="Clientes escaneados">
                <thead><tr><th scope="col">Cliente</th><th scope="col">Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="(record, keyRecord) in forms.entity.qrCamera.data.customers" :key="record.id">
                        <td>{{ record.label }}</td>
                        <td><FgButton tone="link" @click="deleteQrCameraEntity({record, keyRecord})">Quitar</FgButton></td>
                    </tr>
                </tbody>
            </table>
        </ResponsiveTable>
    </FormModal>

    <FormModal
        :modal-id="forms.entity.qrScanner.extras.modals.default.id"
        title="Registrar asistencia" :show-submit="false" dialog-class="fg-attendance-modal" body-class="fg-attendance-modal__body">
        <AttendanceModeTabs :modes="formModes" current="qrScanner" @select="selectModeEntity($event, false)"/>
        <FgField label="Sucursal" required>
            <FgSelect v-model="forms.entity.qrScanner.data.branch" :options="branches" :clearable="false"
                :searchable="false" placeholder="Elige una sucursal"/>
        </FgField>
        <FgListLoading v-if="forms.entity.qrScanner.config.isProcessing" label="Finalizando escaneo…"/>
        <template v-else>
            <fieldset class="fg-attendance-document">
                <legend class="fg-field__label">Tipo de documento</legend>
                <div class="fg-attendance-mode">
                    <FgButton v-for="record in qrCameraModes" :key="record.code" tone="secondary" :icon="record.icon"
                        :aria-pressed="forms.entity.qrScanner.config.currentMode === record.code"
                        @click="setModeQrScanner(record.code)">{{ record.label }}</FgButton>
                </div>
            </fieldset>
            <FgField label="Código de cliente"
                :help="qrCameraModes.find(mode => mode.code === forms.entity.qrScanner.config.currentMode)?.tooltip"
                v-slot="{id, describedBy}">
                <div class="fg-field__control">
                    <input :id="id" ref="qrInput" v-model="forms.entity.qrScanner.data.code" class="fg-input"
                        type="text" placeholder="Escanea o ingresa el código" autocomplete="off"
                        :aria-describedby="describedBy" @input="onResultQrScanner" @change="onResultQrScanner"
                        @keyup.enter="onResultQrScanner"/>
                </div>
            </FgField>
        </template>
    </FormModal>

    <FormModal
        :modal-id="forms.entity.automatic.extras.modals.default.id" title="Modo público"
        :show-submit="false" dialog-class="fg-attendance-modal" body-class="fg-attendance-modal__body">
        <p class="fg-help">Elige la sucursal para abrir el registro público de asistencias.</p>
        <ul v-if="forms.entity.automatic.extras.modals.default.config.branches.length" class="fg-attendance-branches">
            <li v-for="record in forms.entity.automatic.extras.modals.default.config.branches" :key="record.code">
                <div class="fg-attendance-client">
                    <strong>{{ record.label }}</strong>
                    <small v-if="record.data?.address" class="fg-help">{{ record.data.address }}</small>
                </div>
                <FgButton tone="secondary" icon="fa-solid fa-camera" @click="openAutomaticEntity({record})">Iniciar escaneo</FgButton>
            </li>
        </ul>
        <FgEmptyState v-else title="No hay sucursales disponibles"/>
    </FormModal>
</template>

<script>
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import ActionsDropdown from "@System/Components/Generics/ActionsDropdown.vue";
import FormModal from "@System/Components/Generics/FormModal.vue";
import FgButton from "@System/Components/Figma/FgButton.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgFilterActions from "@System/Components/Figma/FgFilterActions.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import AttendanceModeTabs from "./AttendanceModeTabs.vue";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts    from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests  from "@System/Helpers/Requests.js";
import * as Utils     from "@System/Helpers/Utils.js";

export default {
    mixins: [InitialPageMixin],
    components: {ActionsDropdown, AttendanceModeTabs, FgButton, FgEmptyState, FgField, FgFilterActions, FgListLoading, FgPageHeader, FgSelect, FormModal, ResponsiveTable},
    mounted() {
        navbarItem("menu-parent-operations", {addClass: "open"});
        navbarItem(this.config.entity.page.menu.id, {});

        const camera = document.getElementById(this.forms.entity.qrCamera.extras.modals.default.id);
        camera.addEventListener("shown.bs.modal", this.activateCamera);
        camera.addEventListener("hidden.bs.modal", this.deactivateCamera);

        this.initialize();
    },
    beforeUnmount() {
        const camera = document.getElementById(this.forms.entity.qrCamera.extras.modals.default.id);
        camera?.removeEventListener("shown.bs.modal", this.activateCamera);
        camera?.removeEventListener("hidden.bs.modal", this.deactivateCamera);
    },
    data() {
        return {
            parametersReady: false,
            lists: {
                entity: {
                    extras: {
                        loading: false,
                        error: "",
                        lastUrl: null,
                        route: Requests.config({entity: "tracking_attendances", type: "list"})
                    },
                    appliedFilters: null,
                    filters: {
                        branch: null,
                        customer: null,
                        start_date: "",
                        end_date: "",
                        status: ""
                    },
                    records: Requests.emptyPagination()
                }
            },
            forms: {
                entity: {
                    createUpdate: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        store: "Registrar asistencia",
                                        update: "Editar asistencia",
                                        finalized: "Concluir asistencia",
                                        canceled: "Anular asistencia"
                                    },
                                    config: {
                                        buttons: {
                                            store: "btn-primary",
                                            update: "btn-warning",
                                            finalized: "btn-warning",
                                            canceled: "btn-danger"
                                        },
                                        icons: {
                                            store: "fa fa-check",
                                            update: "fa fa-save",
                                            finalized: "fa-solid fa-person-walking-arrow-right",
                                            canceled: "fa fa-times"
                                        },
                                        labels: {
                                            store: "Registrar asistencia",
                                            update: "Editar asistencia",
                                            finalized: "Concluir asistencia",
                                            canceled: "Anular asistencia"
                                        }
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            branch: null,
                            customer: null,
                            start_date: "",
                            end_date: "",
                            status: null
                        },
                        config: {
                            rememberMode: true,
                            currentMode: "",
                            historyMode: []
                        },
                        errors: {}
                    },
                    qrCamera: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        store: "Registrar",
                                        update: "Editar",
                                        finalized: "Concluir",
                                        canceled: "Anular"
                                    },
                                    config: {
                                        buttons: {
                                            store: "btn-primary",
                                            update: "btn-warning",
                                            finalized: "btn-warning",
                                            canceled: "btn-danger"
                                        },
                                        icons: {
                                            store: "fa fa-check",
                                            update: "fa fa-save",
                                            finalized: "fa-solid fa-person-walking-arrow-right",
                                            canceled: "fa fa-times"
                                        },
                                        labels: {
                                            store: "Registrar asistencia",
                                            update: "Editar asistencia",
                                            finalized: "Concluir asistencia",
                                            canceled: "Anular asistencia"
                                        }
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            branch: null,
                            customers: [],
                            start_date: "",
                            end_date: "",
                            status: null
                        },
                        config: {
                            firstInit: false,
                            isCurrentActive: false,
                            isProcessing: true
                        },
                        errors: {}
                    },
                    qrScanner: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        store: "Registrar",
                                        update: "Editar",
                                        finalized: "Concluir",
                                        canceled: "Anular"
                                    },
                                    config: {
                                        buttons: {
                                            store: "btn-primary",
                                            update: "btn-warning",
                                            finalized: "btn-warning",
                                            canceled: "btn-danger"
                                        },
                                        icons: {
                                            store: "fa fa-check",
                                            update: "fa fa-save",
                                            finalized: "fa-solid fa-person-walking-arrow-right",
                                            canceled: "fa fa-times"
                                        },
                                        labels: {
                                            store: "Registrar asistencia",
                                            update: "Editar asistencia",
                                            finalized: "Concluir asistencia",
                                            canceled: "Anular asistencia"
                                        }
                                    }
                                }
                            }
                        },
                        data: {
                            id: null,
                            code: "",
                            branch: null,
                            customers: [],
                            start_date: "",
                            end_date: "",
                            status: null
                        },
                        config: {
                            currentMode: "",
                            isProcessing: true
                        },
                        errors: {}
                    },
                    automatic: {
                        extras: {
                            modals: {
                                default: {
                                    id: Utils.uuid(),
                                    type: "",
                                    titles: {
                                        generate: "Sucursales"
                                    },
                                    config: {
                                        branches: []
                                    }
                                }
                            }
                        },
                        data: {},
                        errors: {}
                    }
                }
            },
            options: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "tracking_attendances"}),
                    page: {
                        title: "Asistencias de clientes",
                        active: true,
                        menu: {
                            id: "menu-customers-attendances"
                        }
                    }
                }
            }
        };
    },
    methods: {
        // ============================================
        // Initialization Methods
        // ============================================
        async initParams({}) {

            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            if(initParams?.data?.config) {

                this.options.branches  = initParams.data.config.branches;
                this.options.customers = initParams.data.config.customers;

            }

            return Requests.valid({result: initParams});

        },
        activateCamera() {
            const camera = this.forms.entity.qrCamera.config;
            camera.isCurrentActive = true;
            if(!camera.firstInit) {
                this.$refs.scannerQr.startScanner();
                camera.firstInit = true;
            }
        },
        deactivateCamera() {
            this.forms.entity.qrCamera.config.isCurrentActive = false;
        },
        async initialize() {
            this.initialLoading = true;
            this.parametersReady = await this.loadInitialParams(() => this.initParams({}));
            if(!this.parametersReady) return;

            this.clearFilters({notify: false});
            await this.listEntity({applyFilters: true});
        },
        clearFilters({notify = true} = {}) {
            this.lists.entity.filters = {
                branch: this.branches[0] ?? null,
                customer: null,
                start_date: Utils.getCurrentDate("date"),
                end_date: "",
                status: ""
            };
            if(notify) Alerts.showFiltersCleared();
        },
        // ============================================
        // Entity List Methods
        // ============================================
        async listEntity({url = null, applyFilters = false} = {}) {
            const list = this.lists.entity;
            if(list.extras.loading) return;
            if(applyFilters || !list.appliedFilters) {
                list.appliedFilters = {...list.filters};
            }

            const filters = list.appliedFilters;
            list.extras.lastUrl = url;
            list.extras.loading = true;
            list.extras.error = "";
            list.records = Requests.emptyPagination();

            try {
                const response = await Requests.get({
                    route: url || list.extras.route,
                    data: {
                        branch_id: filters?.branch?.code,
                        customer_id: filters?.customer?.code,
                        start_date: filters?.start_date,
                        end_date: filters?.end_date,
                        status: filters?.status
                    }
                });
                if(response?.bool && Array.isArray(response.data?.data)) {
                    list.records = response.data;
                }else {
                    list.extras.error = response?.data?.msg || "Comprueba tu conexión y vuelve a intentar.";
                }
            }catch {
                list.extras.error = "Comprueba tu conexión y vuelve a intentar.";
            }finally {
                list.extras.loading = false;
            }
        },
        async exportEntity() {

            const filters = this.lists.entity.appliedFilters;
            const params = {
                branch_id: filters?.branch?.code,
                customer_id: filters?.customer?.code,
                start_date: filters?.start_date,
                end_date: filters?.end_date,
                status: filters?.status
            };

            Alerts.swals({type: "loading", message: "Preparando exportación"});

            try {

                await Requests.download({
                    route: this.config.entity.routes.export,
                    data: params,
                    fileName: "asistencias-clientes.csv",
                    showAlert: true
                });

            }finally {

                Alerts.swals({show: false});

            }

        },
        async requestCorrectionEntity(record) {

            const self = this;

            const result = await Swal.fire({
                allowEscapeKey: false,
                title: "¿Solicitar corrección?",
                html: `<div class="text-start">
                           <p class="small text-muted mb-3">Indica las fechas correctas solo si deben cambiar. El motivo es obligatorio para auditoría.</p>
                           <div class="row g-2">
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="attendanceCorrectionStart">Ingreso corregido</label>
                                   <input type="datetime-local" class="form-control" id="attendanceCorrectionStart">
                               </div>
                               <div class="col-12 col-md-6">
                                   <label class="form-label" for="attendanceCorrectionEnd">Salida corregida</label>
                                   <input type="datetime-local" class="form-control" id="attendanceCorrectionEnd">
                               </div>
                               <div class="col-12">
                                   <label class="form-label" for="attendanceCorrectionReason">Motivo</label>
                                   <textarea class="form-control no-resize" maxlength="500" rows="3" id="attendanceCorrectionReason" required></textarea>
                               </div>
                           </div>
                       </div>`,
                icon: "question",
                allowOutsideClick: false,
                showCancelButton: true,
                showCloseButton: true,
                closeButtonAriaLabel: "Cerrar confirmación",
                confirmButtonText: "Solicitar corrección",
                customClass: {
                    confirmButton: "btn btn-primary waves-effect",
                    cancelButton: "btn btn-secondary waves-effect ms-3"
                },
                preConfirm() {

                    const startDate = document.getElementById("attendanceCorrectionStart")?.value;
                    const endDate = document.getElementById("attendanceCorrectionEnd")?.value;
                    const reason = document.getElementById("attendanceCorrectionReason")?.value?.trim();

                    if(!reason) {

                        Swal.showValidationMessage("Ingresa el motivo de la corrección.");
                        return false;

                    }

                    if(startDate && endDate && startDate >= endDate) {

                        Swal.showValidationMessage("La salida corregida debe ser mayor al ingreso corregido.");
                        return false;

                    }

                    return {
                        start_date: startDate ? startDate.replace("T", " ") : null,
                        end_date: endDate ? endDate.replace("T", " ") : null,
                        reason
                    };

                }
            });

            if(!result.isConfirmed) return;

            Alerts.swals({type: "loading", message: "Registrando corrección"});

            try {

                const route = `${this.config.entity.routes.consult}/${record.id}/corrections`;
                const response = await Requests.post({route, data: result.value});

                if(Requests.valid({result: response})) {

                    Alerts.swals({show: false});
                    Alerts.generateAlert({type: "success", msgContent: response?.data?.msg || "Corrección registrada correctamente."});
                    await self.listEntity({});

                }else {

                    Alerts.swals({show: false});
                    Alerts.generateAlert({type: "warning", msgContent: response?.data?.msg || "No fue posible registrar la corrección."});

                }

            }finally {

                Alerts.swals({show: false});

            }

        },

        // ============================================
        // Mode Management Methods
        // ============================================
        rememberModeEntity(mode = "manual") {

            this.forms.entity.createUpdate.config.rememberMode = !this.forms.entity.createUpdate.config.rememberMode;

            if(this.forms.entity.createUpdate.config.rememberMode) {

                this.addModeEntity(mode);

            }

        },
        addModeEntity(mode = "manual") {

            this.forms.entity.createUpdate.config.historyMode.push(mode);

        },
        selectModeEntity(mode = "manual", validateRemember = true) {
            const config = this.forms.entity.createUpdate.config;
            const selectedMode = validateRemember && config.rememberMode
                ? config.historyMode.at(-1) ?? "manual"
                : mode;
            this.addModeEntity(selectedMode);
            config.currentMode = selectedMode;
            this.toogleProcessingEntity({type: "qrCamera", isProcessing: true});

            const entities = this.forms.entity;
            const activeModal = [entities.createUpdate, entities.qrCamera, entities.qrScanner]
                .map(form => document.getElementById(form.extras.modals.default.id))
                .find(element => element?.classList.contains("show"));
            const openMode = () => {
                if(selectedMode === "manual") {
                    this.modalCreateUpdateEntity({type: "store"});
                }else if(selectedMode === "qrCamera") {
                    this.modalQrCameraEntity({type: "store"});
                }else if(selectedMode === "qrScanner") {
                    if(!this.isDefined({value: entities.qrScanner.config.currentMode})) {
                        this.setModeQrScanner(this.qrCameraModes[0]?.code);
                    }
                    this.modalQrScannerEntity({type: "store"});
                }
            };

            if(activeModal) {
                activeModal.addEventListener("hidden.bs.modal", openMode, {once: true});
                Alerts.modals({type: "hide", id: activeModal.id});
            }else {
                openMode();
            }
        },
        toogleProcessingEntity({type, isProcessing, time = 0}) {

            let el = this;

            if(time > 0) {

                setTimeout(() => el.forms.entity[type].config.isProcessing = isProcessing, time);

            }else {

                this.forms.entity[type].config.isProcessing = isProcessing;

            };

        },
        modalCreateUpdateEntity({record = null, type = "store"}) {

            const functionName = "modalCreateUpdateEntity";

            this.forms.entity.createUpdate.extras.modals.default.type = type;

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                const branch   = this.branches.filter(e => e.code === record?.branch?.id)[0],
                      customer = this.customers.filter(e => e.code === record?.customer?.id)[0];

                this.forms.entity.createUpdate.data.id         = record?.id;
                this.forms.entity.createUpdate.data.branch     = branch;
                this.forms.entity.createUpdate.data.customer   = customer;
                this.forms.entity.createUpdate.data.start_date = record.start_date;
                this.forms.entity.createUpdate.data.end_date   = this.isDefined({value: record.end_date}) ? record.end_date : Utils.getCurrentDate("datetime");

            }else {

                this.forms.entity.createUpdate.data.branch     = this.lists.entity.filters.branch;
                this.forms.entity.createUpdate.data.start_date = Utils.getCurrentDate("datetime");

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.createUpdate.extras.modals.default.id});

        },
        async createUpdateEntity() {

            const functionName = "createUpdateEntity";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.createUpdate.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive", modal: this.forms.entity.createUpdate.extras.modals.default}});

            if(validateForm?.bool) {

                form.branch_id   = form?.branch?.code;
                form.customer_id = form?.customer?.code;

                delete form.branch;
                delete form.customer;

                const createUpdate = await (this.isDefined({value: form.id}) ? Requests.patch({route: this.config.entity.routes.update, data: form, id: form.id}) :
                                                                               Requests.post({route: this.config.entity.routes.store, data: form}));

                const isValid = Requests.valid({result: createUpdate});

                if(isValid) {

                    if(["finalized"].includes(this.forms.entity.createUpdate.extras.modals.default.type)) {

                        Alerts.modals({type: "hide", id: this.forms.entity.createUpdate.extras.modals.default.id});

                    }

                    // Alerts.toastrs({type: "success", subtitle: createUpdate?.data?.msg});
                    // Alerts.swals({show: false});

                    this.clearForm({functionName});
                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: createUpdate?.errors ?? []});
                    // Alerts.toastrs({type: "error", subtitle: createUpdate?.data?.msg});
                    // Alerts.swals({show: false});

                }

                // Show response
                if((createUpdate?.data?.attendances ?? []).length === 0) {

                    Alerts.generateAlert({type: isValid ? "success" : "error", msgContent: createUpdate?.data?.msg});

                }else if((createUpdate?.data?.attendances ?? []).length === 1) {

                    Alerts.generateAlert({type: createUpdate?.data?.attendances[0]?.bool ? "success" : "error", msgContent: createUpdate?.data?.attendances[0]?.msg});

                }else {

                    Alerts.generateAlert({type: isValid ? "success" : "error", messages: createUpdate?.data?.attendances.map(e => `${e.bool ? "<i class='fa fa-check-circle text-success'></i>" : "<i class='fa fa-times-circle text-danger'></i>"} <span class='ms-1'>${e.msg}</span>`), msgContent: `<div class="fw-semibold mb-2">${createUpdate?.data?.msg}</div>`});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

        },
        // QrCamera
        modalQrCameraEntity({record = null, type = "store"}) {

            const functionName = "modalQrCameraEntity";

            this.forms.entity.qrCamera.extras.modals.default.type = type;
            this.toogleProcessingEntity({type: "qrCamera", isProcessing: false});

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                /* const branch   = this.branches.filter(e => e.code === record?.branch?.id)[0],
                      customer = this.customers.filter(e => e.code === record?.customer?.id)[0];

                this.forms.entity.qrCamera.data.id         = record?.id;
                this.forms.entity.qrCamera.data.branch     = branch;
                this.forms.entity.qrCamera.data.customer   = customer;
                this.forms.entity.qrCamera.data.start_date = record.start_date;
                this.forms.entity.qrCamera.data.end_date   = this.isDefined({value: record.end_date}) ? record.end_date : Utils.getCurrentDate("datetime"); */

            }else {

                this.forms.entity.qrCamera.data.branch     = this.lists.entity.filters.branch;
                this.forms.entity.qrCamera.data.start_date = Utils.getCurrentDate("datetime");

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.qrCamera.extras.modals.default.id});

        },
        onResultQrCamera(decodedText, decodedResult) {

            const functionName = "onResultQrCamera";

            if(!this.forms.entity.qrCamera.config.isCurrentActive) return;
            if(this.forms.entity.qrCamera.config.isProcessing) return;

            try {

                this.clearForm({functionName});

                console.log(decodedText, decodedResult);
                let dataScan = JSON.parse(decodedResult?.result?.text);

                const dataScanBp = Utils.decodeBase64UTF8(dataScan?.bp);
                const bp = JSON.parse(dataScanBp);

                if(!this.isDefined({value: dataScanBp}) || !this.isDefined({value: bp})) {

                    Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el QR. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});
                    this.$refs.scannerQr.decrementScanCounter();

                }else {

                    const id = parseInt(bp?.id);

                    if(id > 0) {

                        this.toogleProcessingEntity({type: "qrCamera", isProcessing: true});

                        if(this.forms.entity.qrCamera.data.customers.some(e => e.code == id)) {

                            Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block">Cliente escaneado: ${dataScan.name}.</span><span class="d-block fw-semibold mt-1">Ya se encuentra en los clientes escaneados.</span>`});
                            this.$refs.scannerQr.decrementScanCounter();
                            this.toogleProcessingEntity({type: "qrCamera", isProcessing: false, time: 4000});

                        }else {

                            this.forms.entity.qrCamera.data.customers.push({code: id, label: "", data: {id}});
                            this.qrCameraEntity();

                        }

                    }else {

                        Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});
                        this.$refs.scannerQr.decrementScanCounter();

                    }

                }

            }catch(e) {

                console.log(e);
                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el QR. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});
                this.$refs.scannerQr.decrementScanCounter();

            }

        },
        async qrCameraEntity() {

            const functionName = "qrCameraEntity";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.qrCamera.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive", modal: this.forms.entity.qrCamera.extras.modals.default}});

            if(validateForm?.bool) {

                form.branch_id = form?.branch?.code;

                delete form.branch;

                form.customers.forEach(customer => {

                    customer.customer_id = customer.code;

                    delete customer.code;
                    delete customer.label;
                    delete customer.data;

                });

                const qrCamera = await Requests.post({route: this.config.entity.routes.qrCamera, data: form});

                const isValid = Requests.valid({result: qrCamera});

                if(isValid) {

                    if(["finalized"].includes(this.forms.entity.qrCamera.extras.modals.default.type)) {

                        Alerts.modals({type: "hide", id: this.forms.entity.qrCamera.extras.modals.default.id});

                    }

                    // Alerts.toastrs({type: "success", subtitle: qrCamera?.data?.msg});
                    // Alerts.swals({show: false});

                    // this.clearForm({functionName});
                    this.forms.entity.qrCamera.data.customers = [];

                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: qrCamera?.errors ?? []});
                    // Alerts.toastrs({type: "error", subtitle: qrCamera?.data?.msg});
                    // Alerts.swals({show: false});

                }

                this.clearForm({functionName});

                // Show response
                if((qrCamera?.data?.attendances ?? []).length === 0) {

                    Alerts.generateAlert({type: isValid ? "success" : "error", msgContent: qrCamera?.data?.msg});

                }else if((qrCamera?.data?.attendances ?? []).length === 1) {

                    Alerts.generateAlert({type: qrCamera?.data?.attendances[0]?.bool ? "success" : "error", msgContent: qrCamera?.data?.attendances[0]?.msg});

                }else {

                    Alerts.generateAlert({type: isValid ? "success" : "error", messages: qrCamera?.data?.attendances.map(e => `${e.bool ? "<i class='fa fa-check-circle text-success'></i>" : "<i class='fa fa-times-circle text-danger'></i>"} <span class='ms-1'>${e.msg}</span>`), msgContent: `<div class="fw-semibold mb-2">${qrCamera?.data?.msg}</div>`});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            this.toogleProcessingEntity({type: "qrCamera", isProcessing: false, time: 4000});

        },
        deleteQrCameraEntity({record, keyRecord}) {

            const functionName = "deleteQrCameraEntity";

            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(record);

            const validateForm = this.validateForm({functionName, form});

            if(validateForm?.bool) {

                let el = this;

                Swal.fire({
                    allowEscapeKey: false,
                    title: "¿Quitar cliente escaneado?",
                    html: `<span><b>${form?.label}</b> se quitará de la lista de clientes escaneados.</span>`,
                    icon: "warning",
                    allowOutsideClick: false,
                    showCancelButton: true,
                    showCloseButton: true,
                    closeButtonAriaLabel: "Cerrar confirmación",
                    confirmButtonText: "Quitar cliente",
                    customClass: {
                        confirmButton: "btn btn-danger waves-effect",
                        cancelButton: "btn btn-secondary waves-effect ms-3"
                    }
                })
                .then(function(result) {

                    if(result.isConfirmed) {

                        (el.forms.entity.qrCamera.data.customers).splice(keyRecord, 1);

                        Alerts.toastrs({type: "success", subtitle: `<b>${form?.label}</b> ha sido eliminado de los clientes escaneados.`});

                    }else if(result.isDismissed) {

                        //

                    }

                });

            }

        },
        // QrScanner
        setModeQrScanner(mode) {

            if(this.isDefined({value: mode})) {

                this.forms.entity.qrScanner.config.currentMode = mode;

                this.$refs.qrInput?.focus();

            }

        },
        modalQrScannerEntity({record = null, type = "store"}) {

            const functionName = "modalQrScannerEntity";

            this.forms.entity.qrScanner.extras.modals.default.type = type;
            this.toogleProcessingEntity({type: "qrScanner", isProcessing: false});

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            if(this.isDefined({value: record})) {

                /* const branch   = this.branches.filter(e => e.code === record?.branch?.id)[0],
                      customer = this.customers.filter(e => e.code === record?.customer?.id)[0];

                this.forms.entity.qrScanner.data.id         = record?.id;
                this.forms.entity.qrScanner.data.branch     = branch;
                this.forms.entity.qrScanner.data.customer   = customer;
                this.forms.entity.qrScanner.data.start_date = record.start_date;
                this.forms.entity.qrScanner.data.end_date   = this.isDefined({value: record.end_date}) ? record.end_date : Utils.getCurrentDate("datetime"); */

            }else {

                this.forms.entity.qrScanner.data.branch     = this.lists.entity.filters.branch;
                this.forms.entity.qrScanner.data.start_date = Utils.getCurrentDate("datetime");

            }

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.qrScanner.extras.modals.default.id});

            let el = this;

            if(this.isDefined({value: this.forms.entity.qrScanner.config.currentMode})) {

                setTimeout(() => el.$refs.qrInput?.focus(), 600);

            }

        },
        onResultQrScanner() {

            const functionName = "onResultQrScanner";

            if(this.forms.entity.qrScanner.config.isProcessing) return;

            try {

                const decodedResult = this.forms.entity.qrScanner.data.code.trim();
                const currentMode = this.forms.entity.qrScanner.config.currentMode;

                if(!this.isDefined({value: decodedResult})) return;

                this.clearForm({functionName});

                console.log(currentMode, decodedResult);

                if(["carnet"].includes(currentMode)) {

                    let dataScan = JSON.parse(decodedResult);

                    const dataScanBp = Utils.decodeBase64UTF8(dataScan?.bp);
                    const bp = JSON.parse(dataScanBp);

                    if(!this.isDefined({value: dataScanBp}) || !this.isDefined({value: bp})) {

                        Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el QR. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                    }else {

                        const id = parseInt(bp?.id);

                        if(id > 0) {

                            this.toogleProcessingEntity({type: "qrScanner", isProcessing: true});

                            if(this.forms.entity.qrScanner.data.customers.some(e => e.code == id)) {

                                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block">Cliente escaneado: ${dataScan.name}.</span><span class="d-block fw-semibold mt-1">Ya se encuentra en los clientes escaneados.</span>`});
                                this.toogleProcessingEntity({type: "qrScanner", isProcessing: false, time: 4000});

                            }else {

                                this.forms.entity.qrScanner.data.customers.push({code: id, label: "", customer_attendance_type: currentMode, data: {id}});
                                this.qrScannerEntity();

                            }

                        }else {

                            Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                        }

                    }

                }else if(["document_number"].includes(currentMode)) {

                    let dataScan = decodedResult;

                    const dataScanBp = dataScan;
                    const code = dataScanBp;

                    if(!this.isDefined({value: code})) {

                        Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar el documento. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                    }else {

                        const id = parseInt(code);

                        if(id > 0) {

                            this.toogleProcessingEntity({type: "qrScanner", isProcessing: true});

                            if(this.forms.entity.qrScanner.data.customers.some(e => e.code == id)) {

                                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block">Cliente escaneado: ${dataScan.name}.</span><span class="d-block fw-semibold mt-1">Ya se encuentra en los clientes escaneados.</span>`});
                                this.toogleProcessingEntity({type: "qrScanner", isProcessing: false, time: 4000});

                            }else {

                                this.forms.entity.qrScanner.data.customers.push({code: id, label: "", customer_attendance_type: currentMode, data: {id}});
                                this.qrScannerEntity();

                            }

                        }else {

                            Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

                        }

                    }

                }

            }catch(e) {

                console.log(e);
                Alerts.generateAlert({type: "warning", msgContent: `<span class="d-block fw-semibold">No pudimos validar lo escaneado. Intenta escanearlo nuevamente o verifica que sea el correcto.</span>`});

            }

        },
        async qrScannerEntity() {

            const functionName = "qrScannerEntity";

            Alerts.swals({});
            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.qrScanner.data);

            const validateForm = this.validateForm({functionName, form, extras: {type: "descriptive", modal: this.forms.entity.qrScanner.extras.modals.default}});

            if(validateForm?.bool) {

                form.branch_id = form?.branch?.code;

                delete form.code;
                delete form.branch;

                form.customers.forEach(customer => {

                    if(["carnet"].includes(customer?.customer_attendance_type)) {

                        customer.customer_id = customer.code;

                    }else if(["document_number", "dni", "dnie"].includes(customer?.customer_attendance_type)) {

                        customer.customer_document_number = customer.code;

                    }

                    delete customer.code;
                    delete customer.label;
                    delete customer.data;

                });

                const qrScanner = await Requests.post({route: this.config.entity.routes.qrScanner, data: form});

                const isValid = Requests.valid({result: qrScanner});

                if(isValid) {

                    if(["finalized"].includes(this.forms.entity.qrScanner.extras.modals.default.type)) {

                        Alerts.modals({type: "hide", id: this.forms.entity.qrScanner.extras.modals.default.id});

                    }

                    // Alerts.toastrs({type: "success", subtitle: qrScanner?.data?.msg});
                    // Alerts.swals({show: false});

                    // this.clearForm({functionName});
                    this.forms.entity.qrScanner.data.customers = [];

                    this.listEntity({url: `${this.lists.entity.extras.route}?page=${this.lists.entity.records?.current_page ?? 1}`});

                }else {

                    this.formErrors({functionName, type: "set", errors: qrScanner?.errors ?? []});
                    // Alerts.toastrs({type: "error", subtitle: qrScanner?.data?.msg});
                    // Alerts.swals({show: false});

                }

                this.clearForm({functionName});

                // Show response
                if((qrScanner?.data?.attendances ?? []).length === 0) {

                    Alerts.generateAlert({type: isValid ? "success" : "error", msgContent: qrScanner?.data?.msg});

                }else if((qrScanner?.data?.attendances ?? []).length === 1) {

                    Alerts.generateAlert({type: qrScanner?.data?.attendances[0]?.bool ? "success" : "error", msgContent: qrScanner?.data?.attendances[0]?.msg});

                }else {

                    Alerts.generateAlert({type: isValid ? "success" : "error", messages: qrScanner?.data?.attendances.map(e => `${e.bool ? "<i class='fa fa-check-circle text-success'></i>" : "<i class='fa fa-times-circle text-danger'></i>"} <span class='ms-1'>${e.msg}</span>`), msgContent: `<div class="fw-semibold mb-2">${qrScanner?.data?.msg}</div>`});

                }

            }else {

                // this.formErrors({functionName, type: "set", errors: validateForm});
                // Alerts.toastrs({type: "error", subtitle: this.config.messages.errorValidate});
                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            this.toogleProcessingEntity({type: "qrScanner", isProcessing: false, time: 4000});

        },
        cancelEntity({}) {

            const functionName = "cancelEntity";

            this.formErrors({functionName, type: "clear"});

            let form = Utils.cloneJson(this.forms.entity.createUpdate.data);

            const validateForm = this.validateForm({functionName, form});

            Alerts.modals({type: "hide", id: this.forms.entity.createUpdate.extras.modals.default.id});

            if(validateForm?.bool) {

                let el = this;

                Swal.fire({
                    allowEscapeKey: false,
                    title: "¿Anular asistencia?",
                    html: `<span class="d-block my-1">La asistencia de <b>${form?.customer?.label}</b> quedará anulada. Indica el motivo.</span>
                           <div class="form-group text-start mt-2">
                                <label class="form-label colon-at-end">Motivo</label>
                                <div class="input-group">
                                    <textarea type="text" class="form-control no-resize" maxlength="999" id="motiveId"></textarea>
                                </div>
                           </div>`,
                    icon: "warning",
                    allowOutsideClick: false,
                    showCancelButton: true,
                    showCloseButton: true,
                    closeButtonAriaLabel: "Cerrar confirmación",
                    confirmButtonText: "Anular asistencia",
                    customClass: {
                        confirmButton: "btn btn-danger waves-effect",
                        cancelButton: "btn btn-secondary waves-effect ms-3"
                    }
                }).then(async function(result) {

                    if(result.isConfirmed) {

                        const motive = Swal.getHtmlContainer().querySelector("#motiveId").value;

                        Alerts.swals({});

                        let cancel = await Requests.patch({route: el.config.entity.routes.cancel, data: {motive}, id: form.id});

                        if(Requests.valid({result: cancel})) {

                            Alerts.toastrs({type: "success", subtitle: cancel?.data?.msg});
                            Alerts.swals({show: false});

                            el.listEntity({})

                        }else {

                            Alerts.toastrs({type: "error", subtitle: cancel?.data?.msg});
                            Alerts.swals({show: false});

                        }

                    }else if(result.isDismissed) {

                        //

                    }

                })

            }else {

                Alerts.generateAlert({messages: Utils.getErrors({errors: validateForm}), msgContent: `<div class="fw-semibold mb-2">${this.config.messages.errorValidate}</div>`});

            }

            Alerts.tooltips({show: false});

        },
        // Automatic: URL by Branch
        modalAutomaticEntity({type = "store"}) {

            const functionName = "modalAutomaticEntity";

            this.forms.entity.automatic.extras.modals.default.type = type;

            // Alerts.swals({});
            this.clearForm({functionName});
            this.formErrors({functionName, type: "clear"});

            this.forms.entity.automatic.extras.modals.default.config.branches = this.branches;

            // Alerts.swals({show: false});
            Alerts.modals({type: "show", id: this.forms.entity.automatic.extras.modals.default.id});

        },
        openAutomaticEntity({record = null}) {

            if(this.isDefined({value: record})) {

                const url = generateMyUrl(window.company, false, "my_web", {section: "tracking_attendances", branch: {id: record?.code}});

                window.open(url, "_blank");

            }

        },
        // Forms utils
        clearForm({functionName}) {

            switch(functionName) {
                case "modalCreateUpdateEntity":
                case "createUpdateEntity":
                    this.forms.entity.createUpdate.data.id       = null;
                    this.forms.entity.createUpdate.data.customer = null;
                    this.forms.entity.createUpdate.data.end_date = "";
                    this.forms.entity.createUpdate.data.status   = null;

                    if(["finalized"].includes(this.forms.entity.createUpdate.extras.modals.default.type)) {

                        this.forms.entity.createUpdate.data.branch     = null;
                        this.forms.entity.createUpdate.data.start_date = "";

                    }
                    break;

                case "modalQrCameraEntity":
                    break;

                case "onResultQrCamera":
                    this.forms.entity.qrCamera.data.customers = [];
                    break;

                case "qrCameraEntity":
                    // this.forms.entity.qrCamera.data.id        = null;
                    this.forms.entity.qrCamera.data.customers = [];
                    // this.forms.entity.qrCamera.data.end_date  = "";
                    // this.forms.entity.qrCamera.data.status    = null;

                    // if(["finalized"].includes(this.forms.entity.qrCamera.extras.modals.default.type)) {

                        // this.forms.entity.qrCamera.data.branch     = null;
                        // this.forms.entity.qrCamera.data.start_date = "";

                    // }
                    break;

                case "modalQrScannerEntity":
                    break;

                case "onResultQrScanner":
                    this.forms.entity.qrScanner.data.code = "";
                    this.forms.entity.qrScanner.data.customers = [];
                    break;

                case "qrScannerEntity":
                    // this.forms.entity.qrScanner.data.id        = null;
                    this.forms.entity.qrScanner.data.customers = [];
                    // this.forms.entity.qrScanner.data.end_date  = "";
                    // this.forms.entity.qrScanner.data.status    = null;

                    // if(["finalized"].includes(this.forms.entity.qrScanner.extras.modals.default.type)) {

                        // this.forms.entity.qrScanner.data.branch     = null;
                        // this.forms.entity.qrScanner.data.start_date = "";

                    // }
                    break;
            }

        },
        formErrors({functionName, type = "clear", errors = []}) {

            if(["modalCreateUpdateEntity", "createUpdateEntity"].includes(functionName)) {

                this.forms.entity.createUpdate.errors = ["set"].includes(type) ? errors : [];

            }else if(["modalQrCameraEntity", "qrCameraEntity"].includes(functionName)) {

                this.forms.entity.qrCamera.errors = ["set"].includes(type) ? errors : [];

            }else if(["modalQrScannerEntity", "qrScannerEntity"].includes(functionName)) {

                this.forms.entity.qrCamera.errors = ["set"].includes(type) ? errors : [];

            }

        },
        validateForm({functionName, form = null, extras = null}) {

            let result = {
                bool: true
            };

            if(["createUpdateEntity"].includes(functionName)) {

                result.id         = [];
                result.branch     = [];
                result.customer   = [];
                result.start_date = [];
                result.end_date   = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                const isFinalized = ["finalized"].includes(extras?.modal?.type);

                if(isFinalized) {

                    if(!this.isDefined({value: form?.id})) {

                        result.id.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

                if(!this.isDefined({value: form?.branch})) {

                    result.branch.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.customer})) {

                    result.customer.push(`${isDescriptive ? "Cliente:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.start_date})) {

                    result.start_date.push(`${isDescriptive ? "Ingreso:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(isFinalized) {

                    if(!this.isDefined({value: form?.end_date})) {

                        result.end_date.push(`${isDescriptive ? "Salida:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

            }else if(["qrCameraEntity"].includes(functionName)) {

                result.id         = [];
                result.branch     = [];
                result.customers  = [];
                result.start_date = [];
                result.end_date   = [];

                const isDescriptive = ["descriptive"].includes(extras?.type);

                const isFinalized = ["finalized"].includes(extras?.modal?.type);

                if(isFinalized) {

                    if(!this.isDefined({value: form?.id})) {

                        result.id.push(`${isDescriptive ? "Registro:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

                if(!this.isDefined({value: form?.branch})) {

                    result.branch.push(`${isDescriptive ? "Sucursal:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.customers}) || (form?.customers).length === 0) {

                    result.customers.push(`${isDescriptive ? "Clientes:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(!this.isDefined({value: form?.start_date})) {

                    result.start_date.push(`${isDescriptive ? "Ingreso:" : ""} ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

                if(isFinalized) {

                    if(!this.isDefined({value: form?.end_date})) {

                        result.end_date.push(`${isDescriptive ? "Salida:" : ""} ${this.config.forms.errors.labels.required}`);
                        result.bool = false;

                    }

                }

            }else if(["cancelEntity"].includes(functionName)) {

                result.msg = [];

                if(!this.isDefined({value: form?.id})) {

                    result.msg.push(`Registro: ${this.config.forms.errors.labels.required}`);
                    result.bool = false;

                }

            }

            return result;

        },
        // Others
        getAttendanceDeviceLabel(record) {

            const device = record?.biometric_device || record?.biometricDevice;
            const source = record?.source || record?.formatted_source;

            if(this.isDefined({value: device?.name})) return `Dispositivo: ${device.name}`;
            if(this.isDefined({value: source})) return `Origen: ${source}`;

            return "Registro manual";

        },
        latestAttendanceCorrection(record) {

            return (record?.corrections ?? [])[0] ?? null;

        },
        latestAttendanceCorrectionLabel(record) {

            const correction = this.latestAttendanceCorrection(record);
            const status = correction?.status ?? "pending";

            const labels = {
                pending: "Corrección pendiente",
                approved: "Corrección aprobada",
                rejected: "Corrección rechazada"
            };

            return labels[status] ?? "Corrección registrada";

        },
        isDefined({value}) {

            return Utils.isDefined({value});

        },
        legibleFormatDate({dateString = null, type = "datetime"}) {

            return Utils.legibleFormatDate({dateString, type});

        }
    },
    computed: {
        attendanceStatusOptions() {
            return [
                {code: "all", label: "Todos"},
                {code: "active", label: "En curso"},
                {code: "finalized", label: "Concluida"},
                {code: "canceled", label: "Anulada"}
            ];
        },
        attendanceActivity() {
            const records = this.lists.entity.records.data ?? [];
            const hours = hour => String(hour).padStart(2, "0") + ":00";
            const intervals = Array.from({length: 8}, (_, index) => ({
                label: hours(index * 3) + "–" + hours((index + 1) * 3),
                entries: 0,
                exits: 0
            }));
            const statuses = this.attendanceStatusOptions
                .filter(status => status.code !== "all")
                .map(status => ({...status, total: 0}));
            const statusIndex = new Map(statuses.map(status => [status.code, status]));

            for(const record of records) {
                const status = statusIndex.get(record.status);
                if(status) status.total++;
                for(const [date, kind] of [[record.start_date, "entries"], [record.end_date, "exits"]]) {
                    if(!date) continue;
                    const hour = new Date(date.replace(" ", "T")).getHours();
                    const interval = intervals[Math.floor(hour / 3)];
                    if(interval) interval[kind]++;
                }
            }
            return {intervals, statuses};
        },
        breadcrumbTitles: function() {

            return [{title: "Operaciones y clientes"}, {title: "Servicios y asistencias"}, this.config.entity.page];

        },
        branches: function() {

            return (this.options.branches?.records ?? []).map(e => ({code: e.id, label: e.name, data: e}));

        },
        customers: function() {

            return (this.options.customers?.records ?? []).map(e => ({code: e.id, label: `${e.document_number} - ${e.name}`, data: e}));

        },
        formModes: function() {

            return [
                {code: "manual", label: "Manual"},
                {code: "qrCamera", label: "Cámara QR"},
                {code: "qrScanner", label: "Lector QR"}
            ];

        },
        qrCameraModes: function() {

            return [
                {code: "carnet", label: "Carnet", tooltip: "Carnet generado por el sistema con código QR.", icon: "fa fa-id-badge", color: "primary"},
                {code: "document_number", label: "Documento", tooltip: "Escanea o escribe el número de documento del cliente.", icon: "fa fa-id-card", color: "info"}
            ];

        }
    }
};
</script>
