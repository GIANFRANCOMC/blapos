<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <section class="fg-cash">
                <div class="fg-cash__context">
                    <div class="fg-cash__heading">
                        <p class="fg-cash__eyebrow mb-1">Operación de caja</p>
                        <h1 class="fg-cash__title mb-0">{{ activeViewMeta.label }}</h1>
                        <p class="fg-cash__subtitle mb-0">{{ activeViewMeta.description }}</p>
                    </div>
                    <div class="fg-cash__selector">
                        <FgField label="Caja de trabajo" input-id="cash-work-register">
                            <FgSelect
                                v-model="registerFilterId"
                                :options="registerOptions"
                                :reduce="register => register.id"
                                :clearable="false"
                                :searchable="false"
                                placeholder="Seleccione una caja"/>
                        </FgField>
                        <p class="fg-operational-scope mt-2 mb-0">
                            <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                            <span>{{ selectedRegister ? `${selectedRegister.name} · ${selectedRegister.branch?.name || 'Sucursal no definida'}` : 'Seleccione una caja para definir el alcance' }}</span>
                        </p>
                    </div>
                </div>

                <section class="fg-filter-bar fg-filter-bar--compact fg-cash__toolbar">
                    <div class="fg-filter-bar__field">
                        <SearchInput
                            v-model="filters.search"
                            title="Búsqueda"
                            :placeholder="activeView === 'registers' ? 'Nombre, código o sucursal' : 'Caja, responsable o referencia'"/>
                    </div>
                    <div class="fg-filter-bar__actions">
                        <SearchButton @click="searchActiveView"/>
                        <button
                            v-if="activeView === 'registers'"
                            type="button"
                            class="fg-button"
                            data-bs-toggle="tooltip"
                            title="Agregar caja a una sucursal"
                            @click="openModal('register')">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Agregar caja</span>
                        </button>
                        <button
                            v-if="activeView === 'movements'"
                            type="button"
                            class="fg-button"
                            data-bs-toggle="tooltip"
                            title="Registrar ingreso, salida o ajuste manual"
                            :disabled="!currentSession"
                            @click="openModal('movement')">
                            <i class="fa-solid fa-right-left" aria-hidden="true"></i>
                            <span>Registrar movimiento</span>
                        </button>
                        <button
                            type="button"
                            class="fg-button fg-button--template"
                            aria-label="Descargar movimientos"
                            data-bs-toggle="tooltip"
                            title="Descargar movimientos"
                            @click="downloadMovements">
                            <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                            <span class="d-inline d-lg-none">Descargar</span>
                        </button>
                    </div>
                </section>

                <section v-if="activeView === 'registers'" class="fg-cash__panel">
                    <Loader v-if="loading.registers"/>
                    <div v-else-if="visibleRegisters.length" class="table-responsive">
                        <table class="table fg-entity-table mb-0">
                            <thead class="fg-table-header-surface">
                                <tr>
                                    <th>Caja</th>
                                    <th>Sucursal</th>
                                    <th>Sesión actual</th>
                                    <th class="text-end">Esperado</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="register in visibleRegisters" :key="register.id">
                                    <td>
                                        <strong>{{ register.name }}</strong>
                                        <span class="fg-cash__muted d-block">{{ register.code }}</span>
                                    </td>
                                    <td>{{ register.branch?.name || 'Sin sucursal' }}</td>
                                    <td>
                                        <span v-if="register.is_open" class="fg-status fg-status-active">Abierta</span>
                                        <span v-else class="fg-status fg-status-inactive">Sin apertura</span>
                                    </td>
                                    <td class="text-end fw-medium">S/ {{ separatorNumber(register.current_amount) }}</td>
                                    <td class="text-center">
                                        <span :class="['fg-badge', register.status === 'active' ? 'fg-status-active' : 'fg-status-inactive']">
                                            {{ register.status === 'active' ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            v-if="register.is_open"
                                            type="button"
                                            class="fg-icon-action fg-icon-action-edit"
                                            data-bs-toggle="tooltip"
                                            title="Cerrar caja"
                                            @click="openModal('close', register)">
                                            <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            class="fg-icon-action fg-icon-action-primary"
                                            data-bs-toggle="tooltip"
                                            title="Abrir caja"
                                            @click="openModal('open', register)">
                                            <i class="fa-solid fa-door-open" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <WithoutData v-else/>
                </section>

                <section v-if="activeView === 'sessions'" class="fg-cash__panel">
                    <Loader v-if="loading.sessions"/>
                    <div v-else-if="sessions.data?.length" class="table-responsive">
                        <table class="table fg-entity-table mb-0">
                            <thead class="fg-table-header-surface">
                                <tr>
                                    <th>Caja</th>
                                    <th>Apertura</th>
                                    <th>Cierre</th>
                                    <th class="text-end">Esperado</th>
                                    <th class="text-end">Contado</th>
                                    <th class="text-end">Diferencia</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="session in sessions.data" :key="session.id">
                                    <td>
                                        <strong>{{ session.register?.name }}</strong>
                                        <span class="fg-cash__muted d-block">{{ session.branch?.name }}</span>
                                    </td>
                                    <td>{{ formatDateTime(session.opened_at) }}</td>
                                    <td>{{ session.closed_at ? formatDateTime(session.closed_at) : 'En curso' }}</td>
                                    <td class="text-end">S/ {{ separatorNumber(session.expected_amount) }}</td>
                                    <td class="text-end">S/ {{ separatorNumber(session.counted_amount) }}</td>
                                    <td class="text-end" :class="differenceClass(session.difference_amount)">
                                        S/ {{ separatorNumber(session.difference_amount) }}
                                    </td>
                                    <td class="text-center">
                                        <span :class="['fg-badge', session.status === 'open' ? 'fg-status-active' : 'fg-status-inactive']">
                                            {{ session.status === 'open' ? 'Abierta' : 'Cerrada' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                    <WithoutData v-else/>
                    <Paginator :links="sessions.links" :pagination="sessions" :loading="loading.sessions" @clickPage="getSessions"/>
                </section>

                <section v-if="activeView === 'summary'" class="fg-cash__panel">
                    <Loader v-if="loading.summary"/>
                    <div v-else class="fg-cash-summary">
                        <article class="fg-cash-summary__card">
                            <span>Fondo inicial</span>
                            <strong>S/ {{ separatorNumber(summary.totals.opening) }}</strong>
                        </article>
                        <article class="fg-cash-summary__card">
                            <span>Esperado</span>
                            <strong>S/ {{ separatorNumber(summary.totals.expected) }}</strong>
                        </article>
                        <article class="fg-cash-summary__card">
                            <span>Contado</span>
                            <strong>S/ {{ separatorNumber(summary.totals.counted) }}</strong>
                        </article>
                        <article class="fg-cash-summary__card">
                            <span>Diferencia de arqueos</span>
                            <strong :class="differenceClass(summary.totals.difference)">S/ {{ separatorNumber(summary.totals.difference) }}</strong>
                        </article>
                    </div>
                    <div v-if="summary.payments.length" class="table-responsive mt-3">
                        <table class="table fg-entity-table mb-0">
                            <thead class="fg-table-header-surface">
                                <tr>
                                    <th>Método de pago</th>
                                    <th class="text-end">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in summary.payments" :key="payment.payment_method_id || 'opening'">
                                    <td>{{ payment.payment_method?.name || 'Efectivo / apertura' }}</td>
                                    <td class="text-end fw-medium">S/ {{ separatorNumber(payment.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section v-if="activeView === 'movements'" class="fg-cash__panel">
                    <Loader v-if="loading.movements"/>
                    <div v-else-if="movements.data?.length" class="table-responsive">
                        <table class="table fg-entity-table mb-0">
                            <thead class="fg-table-header-surface">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Caja</th>
                                    <th>Tipo</th>
                                    <th>Método</th>
                                    <th>Referencia</th>
                                    <th class="text-end">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="movement in movements.data" :key="movement.id">
                                    <td>{{ formatDateTime(movement.occurred_at) }}</td>
                                    <td>{{ movement.cash_session?.register?.name || '-' }}</td>
                                    <td>{{ movementTypeLabel(movement.movement_type) }}</td>
                                    <td>{{ movement.payment_method?.name || 'Efectivo / caja' }}</td>
                                    <td>
                                        <strong>{{ movement.reference || '-' }}</strong>
                                        <span v-if="movement.user?.name" class="fg-cash__muted d-block">{{ movement.user.name }}</span>
                                    </td>
                                    <td class="text-end fw-medium">S/ {{ separatorNumber(movement.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                    <WithoutData v-else/>
                    <Paginator :links="movements.links" :pagination="movements" :loading="loading.movements" @clickPage="getMovements"/>
                </section>
            </section>

        </div>
    </InitialLoadTransition>
    <div class="modal fade fg-modal fg-modal--compact" id="cashRegisterModal" tabindex="-1" aria-labelledby="cashRegisterModalTitle" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="cashRegisterModalTitle" class="modal-title fg-modal__title">Agregar caja</h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body fg-stack">
                    <FgField label="Sucursal" required :error="errors.branch_id">
                        <FgSelect
                            v-model="forms.register.branch"
                            :options="branchOptions"
                            :clearable="false"
                            :searchable="branchOptions.length > 6"
                            placeholder="Seleccione sucursal"/>
                    </FgField>
                    <InputText
                        v-model="forms.register.name"
                        hasDiv
                        title="Nombre de caja"
                        :titleClass="['form-label']"
                        isRequired
                        maxlength="100"
                        hasTextBottom
                        :textBottomInfo="errors.name"
                        placeholder="Ej. Caja mostrador 2"/>
                    <InputText
                        v-model="forms.register.code"
                        hasDiv
                        title="Código interno"
                        :titleClass="['form-label']"
                        maxlength="30"
                        hasTextBottom
                        :textBottomInfo="errors.code"
                        placeholder="Se genera automáticamente si lo dejas vacío"/>
                    <FgField label="Estado" required :error="errors.status">
                        <FgSelect v-model="forms.register.status" :options="statusOptions" :clearable="false" :searchable="false"/>
                    </FgField>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="submitRegister">
                        Agregar caja
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade fg-modal fg-modal--compact" id="cashOpenModal" tabindex="-1" aria-labelledby="cashOpenModalTitle" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="cashOpenModalTitle" class="modal-title fg-modal__title">Abrir caja</h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body fg-stack">
                    <FgField label="Caja" required :error="errors.cash_register_id">
                        <FgSelect
                            v-model="forms.open.cash_register"
                            :options="registerOptions"
                            :clearable="false"
                            :searchable="false"
                            placeholder="Seleccione una caja"/>
                    </FgField>
                    <InputNumber
                        v-model="forms.open.opening_amount"
                        hasDiv
                        title="Fondo inicial"
                        :titleClass="['form-label']"
                        hasTextBottom
                        :textBottomInfo="errors.opening_amount"/>
                    <InputText
                        v-model="forms.open.observation"
                        hasDiv
                        title="Observación"
                        :titleClass="['form-label']"
                        maxlength="300"/>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="submitOpen">Abrir caja</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade fg-modal" id="cashCloseModal" tabindex="-1" aria-labelledby="cashCloseModalTitle" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="cashCloseModalTitle" class="modal-title fg-modal__title">Cerrar caja</h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body fg-stack">
                    <div class="fg-cash-close-summary">
                        <span>Esperado</span>
                        <strong>S/ {{ separatorNumber(forms.close.expected_amount) }}</strong>
                    </div>
                    <div class="row g-3">
                        <InputNumber
                            v-for="payment in forms.close.payments"
                            :key="payment.payment_method_id || 'opening'"
                            v-model="payment.counted_amount"
                            hasDiv
                            :title="payment.label"
                            :titleClass="['form-label']"
                            xl="6"
                            lg="6"/>
                    </div>
                    <section v-if="forms.close.requires_inventory_count" class="fg-cash-inventory-count mt-3">
                        <header class="fg-cash-inventory-count__header">
                            <div>
                                <strong>Conteo físico de inventario</strong>
                                <span>Revisa las existencias reales antes de cerrar la caja principal.</span>
                            </div>
                            <button
                                type="button"
                                class="fg-button fg-button--small fg-button--secondary"
                                @click="fillInventoryCountWithSystemStock">
                                <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                                <span>Usar saldo sistema</span>
                            </button>
                        </header>
                        <div class="fg-cash-inventory-count__summary">
                            <span>{{ forms.close.inventory_counts.length }} productos por almacén</span>
                            <strong :class="differenceClass(inventoryCountDifferenceTotal)">
                                Diferencia neta: {{ separatorNumber(inventoryCountDifferenceTotal) }}
                            </strong>
                        </div>
                        <div v-if="forms.close.inventory_counts.length" class="table-responsive">
                            <table class="table fg-entity-table mb-0">
                                <thead class="fg-table-header-surface">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Almacén</th>
                                        <th class="text-end">Sistema</th>
                                        <th class="text-end">Conteo real</th>
                                        <th class="text-end">Diferencia</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in forms.close.inventory_counts" :key="`${row.warehouse_id}-${row.item_id}`">
                                        <td>
                                            <strong>{{ row.item_name }}</strong>
                                            <span class="fg-cash__muted d-block">
                                                {{ [row.item_internal_code, row.brand_name].filter(Boolean).join(' · ') || 'Producto sin referencia' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span>{{ row.warehouse_name }}</span>
                                            <span class="fg-cash__muted d-block">{{ row.branch_name }}</span>
                                        </td>
                                        <td class="text-end fw-medium">{{ separatorNumber(row.system_quantity) }}</td>
                                        <td class="text-end">
                                            <InputNumber
                                                v-model="row.counted_quantity"
                                                title=""
                                                :titleClass="[]"
                                                :inputClass="['form-control', {'is-invalid': row.counted_quantity === '' || row.counted_quantity === null}]"
                                                :minValue="0"/>
                                        </td>
                                        <td class="text-end fw-medium" :class="differenceClass(inventoryRowDifference(row))">
                                            {{ separatorNumber(inventoryRowDifference(row)) }}
                                        </td>
                                        <td>
                                            <input
                                                v-model.trim="row.observation"
                                                type="text"
                                                class="form-control"
                                                maxlength="500"
                                                placeholder="Opcional"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="fg-cash-inventory-count__empty mb-0">
                            No hay productos con stock configurado para la sucursal de esta caja.
                        </p>
                    </section>
                    <InputText
                        v-model="forms.close.observation"
                        hasDiv
                        title="Observación"
                        :titleClass="['form-label']"
                        maxlength="300"/>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="submitClose">Cerrar caja</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade fg-modal fg-modal--compact" id="cashMovementModal" tabindex="-1" aria-labelledby="cashMovementModalTitle" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="cashMovementModalTitle" class="modal-title fg-modal__title">Registrar operación</h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body fg-stack">
                    <FgField label="Tipo de movimiento" required :error="errors.movement_type">
                        <FgSelect
                            v-model="forms.movement.type"
                            :options="movementTypeOptions"
                            :clearable="false"
                            :searchable="false"
                            placeholder="Seleccione"/>
                    </FgField>
                    <FgField label="Método de pago" :error="errors.payment_method_id">
                        <FgSelect
                            v-model="forms.movement.payment_method"
                            :options="paymentMethodOptions"
                            :searchable="false"
                            placeholder="Efectivo / caja"/>
                    </FgField>
                    <InputNumber
                        v-model="forms.movement.amount"
                        hasDiv
                        title="Importe"
                        :titleClass="['form-label']"
                        isRequired
                        hasTextBottom
                        :textBottomInfo="errors.amount"/>
                    <InputText
                        v-model="forms.movement.reference"
                        hasDiv
                        title="Referencia"
                        :titleClass="['form-label']"
                        maxlength="120"/>
                    <InputText
                        v-model="forms.movement.note"
                        hasDiv
                        title="Nota interna"
                        :titleClass="['form-label']"
                        maxlength="300"/>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="submitMovement">Registrar operación</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {navbarItem} from "@System/Layout/Menu.js";
import SearchButton from "@/Shared/SearchButton.vue";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";

const MODULE = {
    entity: "cash_registers",
    pageTitle: "Caja",
    breadcrumbParent: "Cajas"
};

const VIEW_MENU_MAP = {
    registers: "menu-cash-registers",
    sessions: "menu-cash-sessions",
    movements: "menu-cash-movements",
    summary: "menu-cash-summary"
};

export default {
    mixins: [InitialPageMixin],
    components: {SearchButton, SearchInput, FgField, FgSelect},
    props: {
        initialView: {
            type: String,
            default: "registers",
            validator: value => ["registers", "sessions", "movements", "summary"].includes(value)
        }
    },
    data() {
        return {
            config: Requests.config({entity: MODULE.entity}),
            sessionConfig: Requests.config({entity: "cash-sessions"}),
            movementConfig: Requests.config({entity: "cash-movements"}),
            summaryConfig: Requests.config({entity: "cash-summary"}),
            registers: [],
            sessions: {data: [], links: []},
            movements: {data: [], links: []},
            summary: {sessions: [], payments: [], totals: {opening: 0, expected: 0, counted: 0, difference: 0}},
            selectedRegisterId: null,
            registerFilterId: null,
            activeView: this.initialView,
            filters: {search: ""},
            appliedSearch: "",
            options: {
                registers: [],
                branches: [],
                inventoryItems: [],
                paymentMethods: [],
                statuses: [],
                movementTypes: []
            },
            forms: {
                register: {branch: null, name: "", code: "", status: null},
                open: {cash_register: null, opening_amount: "", observation: ""},
                close: {cash_session_id: null, expected_amount: 0, payments: [], inventory_counts: [], requires_inventory_count: false, observation: ""},
                movement: {
                    type: null,
                    payment_method: null,
                    amount: "",
                    reference: "",
                    note: ""
                }
            },
            errors: {},
            saving: false,
            loading: {
                init: false,
                registers: false,
                sessions: false,
                movements: false,
                summary: false
            },
            views: [
                {id: "registers", label: "Cajas", description: "Apertura y estado", icon: "fa-solid fa-vault"},
                {id: "sessions", label: "Aperturas y cierres", description: "Turnos y arqueos", icon: "fa-solid fa-clock-rotate-left"},
                {id: "summary", label: "Resumen", description: "Métodos de pago", icon: "fa-solid fa-chart-pie"},
                {id: "movements", label: "Movimientos", description: "Trazabilidad", icon: "fa-solid fa-right-left"}
            ]
        };
    },
    computed: {
        breadcrumbTitles() {
            return [{title: MODULE.breadcrumbParent}, {title: this.activeViewMeta.label, active: true}];
        },
        registerOptions() {
            return this.options.registers.map(register => ({
                ...register,
                label: `${register.name} - ${register.branch?.name || 'Sin sucursal'}`
            }));
        },
        selectedRegister() {
            return this.registerOptions.find(register => register.id === this.selectedRegisterId) || null;
        },
        visibleRegisters() {
            const search = this.appliedSearch.trim().toLocaleLowerCase("es-PE");
            if(!search) return this.registers;

            return this.registers.filter(register =>
                [register.name, register.code, register.branch?.name]
                    .some(value => String(value ?? "").toLocaleLowerCase("es-PE").includes(search)));
        },
        branchOptions() {
            return this.options.branches.map(branch => ({...branch, label: branch.name}));
        },
        statusOptions() {
            return [
                {id: "active", label: "Activa"},
                {id: "inactive", label: "Inactiva"}
            ];
        },
        currentSession() {
            return this.selectedRegister?.open_session ?? null;
        },
        movementTypeOptions() {
            return [
                {id: "income", label: "Ingreso"},
                {id: "expense", label: "Salida"},
                {id: "adjustment", label: "Ajuste"}
            ];
        },
        paymentMethodOptions() {
            return this.options.paymentMethods.map(method => ({...method, label: method.name}));
        },
        activeViewMeta() {
            return this.views.find(view => view.id === this.activeView) || this.views[0];
        },
        inventoryCountDifferenceTotal() {
            return Utils.sum(this.forms.close.inventory_counts.map(row => this.inventoryRowDifference(row)));
        }
    },
    async mounted() {
        navbarItem("menu-parent-cash", {addClass: "open"});
        navbarItem(this.activeMenuId(), {addClass: "active"});
        const initialized = await this.loadInitialParams(() => this.initParams());
        if(initialized) await this.refreshAll();
    },
    methods: {
        activeMenuId() {
            return VIEW_MENU_MAP[this.activeView] || VIEW_MENU_MAP.registers;
        },
        async initParams() {
            this.loading.init = true;
            const result = await Requests.get({route: this.config.routes.initParams});
            this.loading.init = false;

            if(!Requests.valid({result})) return;

            const data = result.data.config;
            this.options.registers = data.registers || [];
            this.options.branches = data.branches || [];
            this.options.inventoryItems = data.inventoryItems || [];
            this.options.paymentMethods = data.paymentMethods || [];
            this.options.statuses = data.statuses || [];
            this.options.movementTypes = data.movementTypes || [];
            if(!this.selectedRegister) {
                this.selectedRegisterId = this.registerOptions[0]?.id ?? null;
                this.registerFilterId = this.selectedRegisterId;
            }

            return true;
        },
        async refreshAll() {
            await this.getRegisters();
            await this.refreshActiveView();
            Alerts.tooltips({});
        },
        searchActiveView() {
            this.selectedRegisterId = this.registerFilterId;
            this.appliedSearch = this.filters.search;
            return this.refreshActiveView();
        },
        async refreshActiveView(pageUrl = null) {
            if(this.activeView === "sessions") return this.getSessions(pageUrl);
            if(this.activeView === "movements") return this.getMovements(pageUrl);
            if(this.activeView === "summary") return this.getSummary();

            return this.getRegisters();
        },
        baseFilters() {
            return {
                filter: {
                    cash_register_id: this.selectedRegister?.id || null,
                    search: this.appliedSearch || null
                }
            };
        },
        async getRegisters() {
            this.loading.registers = true;
            this.registers = [];

            try {
                const result = await Requests.get({route: this.config.routes.list});
                if(!Requests.valid({result})) return;

                this.registers = result.data.data || [];
                this.options.registers = this.registers;
            } finally {
                this.loading.registers = false;
            }
        },
        async getSessions(pageUrl = null) {
            pageUrl = typeof pageUrl === "object" ? pageUrl?.url : pageUrl;
            this.loading.sessions = true;

            try {
                this.sessions = Requests.emptyPagination();
                const result = await Requests.get({
                    route: pageUrl || this.sessionConfig.routes.list,
                    data: this.baseFilters()
                });

                if(Requests.valid({result})) this.sessions = result.data.data;
            } finally {
                this.loading.sessions = false;
            }
        },
        async getMovements(pageUrl = null) {
            pageUrl = typeof pageUrl === "object" ? pageUrl?.url : pageUrl;
            this.loading.movements = true;

            try {
                this.movements = Requests.emptyPagination();
                const result = await Requests.get({
                    route: pageUrl || this.movementConfig.routes.list,
                    data: this.baseFilters()
                });

                if(Requests.valid({result})) this.movements = result.data.data;
            } finally {
                this.loading.movements = false;
            }
        },
        async getSummary() {
            this.loading.summary = true;
            this.summary = {sessions: [], payments: [], totals: {opening: 0, expected: 0, counted: 0, difference: 0}};

            try {
                const result = await Requests.get({
                    route: this.summaryConfig.routes.data,
                    data: this.baseFilters()
                });
                if(Requests.valid({result})) this.summary = result.data.data;
            } finally {
                this.loading.summary = false;
            }
        },
        openModal(type, register = null) {
            if(register) {
                this.selectedRegisterId = register.id;
                this.registerFilterId = register.id;
            }
            this.errors = {};

            if(type === "register") {
                this.forms.register = {
                    branch: this.branchOptions[0] || null,
                    name: "",
                    code: "",
                    status: this.statusOptions[0]
                };
                this.showModal("cashRegisterModal");
                return;
            }

            if(type === "open") {
                this.forms.open = {cash_register: this.selectedRegister, opening_amount: "", observation: ""};
                this.showModal("cashOpenModal");
                return;
            }

            if(type === "movement") {
                this.forms.movement = {
                    type: this.movementTypeOptions[0],
                    payment_method: null,
                    amount: "",
                    reference: "",
                    note: ""
                };
                this.showModal("cashMovementModal");
                return;
            }

            const session = register?.open_session || this.currentSession;
            const closeRegister = register || this.selectedRegister || {};
            this.forms.close = {
                cash_session_id: session?.id,
                expected_amount: session?.expected_amount || 0,
                observation: "",
                payments: this.closePaymentRows(),
                requires_inventory_count: Boolean(closeRegister?.is_main),
                inventory_counts: this.inventoryCountRows(closeRegister)
            };
            this.showModal("cashCloseModal");
        },
        inventoryCountRows(register = {}) {
            if(!register?.is_main) return [];

            const branchId = Number(register?.branch?.id || register?.branch_id || register?.open_session?.branch_id || 0);

            return (this.options.inventoryItems || [])
                .filter(row => Number(row.branch_id) === branchId)
                .map(row => ({
                    ...row,
                    system_quantity: Number(row.system_quantity || 0),
                    counted_quantity: "",
                    observation: ""
                }));
        },
        fillInventoryCountWithSystemStock() {
            this.forms.close.inventory_counts = this.forms.close.inventory_counts.map(row => ({
                ...row,
                counted_quantity: Number(row.system_quantity || 0)
            }));
        },
        inventoryRowDifference(row) {
            if(row.counted_quantity === "" || row.counted_quantity === null) return 0;

            return Utils.subtract(row.counted_quantity, row.system_quantity);
        },
        inventoryCountsAreComplete() {
            if(!this.forms.close.requires_inventory_count) return true;

            return this.forms.close.inventory_counts.every(row => row.counted_quantity !== "" && row.counted_quantity !== null);
        },
        closePaymentRows() {
            const rows = this.options.paymentMethods.map(method => ({
                payment_method_id: method.id,
                label: method.name,
                counted_amount: ""
            }));

            return [{payment_method_id: null, label: "Efectivo / fondo", counted_amount: ""}, ...rows];
        },
        async submitRegister() {
            this.saving = true;
            const result = await Requests.post({
                route: this.config.routes.store || "/cash_registers",
                data: {
                    branch_id: this.forms.register.branch?.id,
                    name: this.forms.register.name,
                    code: this.forms.register.code,
                    status: this.forms.register.status?.id || "active"
                }
            });
            this.saving = false;

            if(!Requests.valid({result})) return this.handleError(result);

            this.hideModal("cashRegisterModal");
            Alerts.toastrs({type: "success", subtitle: result.data.msg});
            await this.initParams();
            await this.refreshAll();
        },
        async submitOpen() {
            this.saving = true;
            const result = await Requests.post({
                route: this.sessionConfig.routes.open,
                data: {
                    cash_register_id: this.forms.open.cash_register?.id,
                    opening_amount: this.forms.open.opening_amount,
                    observation: this.forms.open.observation
                }
            });
            this.saving = false;

            if(!Requests.valid({result})) return this.handleError(result);

            this.hideModal("cashOpenModal");
            Alerts.toastrs({type: "success", subtitle: result.data.msg});
            await this.refreshAll();
        },
        async submitClose() {
            if(!this.inventoryCountsAreComplete()) {
                Alerts.toastrs({
                    type: "warning",
                    subtitle: "Completa el conteo físico de inventario antes de cerrar la caja principal."
                });
                return;
            }

            this.saving = true;
            const payload = {
                ...this.forms.close,
                inventory_counts: this.forms.close.requires_inventory_count
                    ? this.forms.close.inventory_counts.map(row => ({
                        warehouse_id: row.warehouse_id,
                        item_id: row.item_id,
                        counted_quantity: Number(row.counted_quantity || 0),
                        observation: row.observation || null
                    }))
                    : []
            };
            delete payload.requires_inventory_count;
            const result = await Requests.post({
                route: this.sessionConfig.routes.close,
                data: payload
            });
            this.saving = false;

            if(!Requests.valid({result})) return this.handleError(result);

            this.hideModal("cashCloseModal");
            Alerts.toastrs({type: "success", subtitle: result.data.msg});
            await this.refreshAll();
        },
        async submitMovement() {
            this.saving = true;
            const result = await Requests.post({
                route: this.movementConfig.routes.store,
                data: {
                    cash_session_id: this.currentSession?.id,
                    movement_type: this.forms.movement.type?.id,
                    payment_method_id: this.forms.movement.payment_method?.id,
                    amount: this.forms.movement.amount,
                    reference: this.forms.movement.reference,
                    note: this.forms.movement.note
                }
            });
            this.saving = false;

            if(!Requests.valid({result})) return this.handleError(result);

            this.hideModal("cashMovementModal");
            Alerts.toastrs({type: "success", subtitle: result.data.msg});
            await this.refreshAll();
        },
        async downloadMovements() {
            Alerts.swals({type: "loading", message: "Preparando descarga"});

            const result = await Requests.download({
                route: this.movementConfig.routes.export,
                data: this.baseFilters(),
                fileName: "caja_movimientos.csv",
                showAlert: true
            });

            Alerts.swals({show: false});

            if(result.bool) {
                Alerts.toastrs({type: "success", subtitle: "Descarga preparada correctamente."});
            }
        },
        handleError(result) {
            this.errors = result.errors || {};
            Alerts.toastrs({
                type: "error",
                subtitle: result.data?.msg || "No fue posible completar la acción."
            });
        },
        showModal(id) {
            const element = document.getElementById(id);
            window.bootstrap.Modal.getOrCreateInstance(element, {backdrop: "static", keyboard: false}).show();
        },
        hideModal(id) {
            const element = document.getElementById(id);
            window.bootstrap.Modal.getOrCreateInstance(element).hide();
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value || 0);
        },
        formatDateTime(value) {
            if(!value) return "-";
            return new Date(value).toLocaleString("es-PE");
        },
        differenceClass(value) {
            const sign = Utils.compareNumbers(value, 0);
            if(sign > 0) return "text-success";
            if(sign < 0) return "text-danger";
            return "text-muted";
        },
        movementTypeLabel(type) {
            return this.options.movementTypes.find(item => item.id === type)?.label || type || "-";
        }
    }
};
</script>
