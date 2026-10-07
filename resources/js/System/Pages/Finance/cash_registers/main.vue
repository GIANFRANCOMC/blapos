<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-cash">
        <div class="br-cash__context">
            <div class="br-cash__heading">
                <p class="br-cash__eyebrow mb-1">Operación de caja</p>
                <h1 class="br-cash__title mb-0">{{ activeViewMeta.label }}</h1>
                <p class="br-cash__subtitle mb-0">{{ activeViewMeta.description }}</p>
            </div>
            <div class="br-cash__selector">
                <label class="form-label">Caja de trabajo</label>
                <v-select
                    v-model="selectedRegister"
                    :options="registerOptions"
                    class="bg-white"
                    :clearable="false"
                    :searchable="false"
                    append-to-body
                    placeholder="Seleccione una caja"
                    @option:selected="refreshAll"/>
                <p class="br-operational-scope mt-2 mb-0">
                    <i class="fa-solid fa-cash-register" aria-hidden="true"></i>
                    <span>{{ selectedRegister ? `${selectedRegister.name} · ${selectedRegister.branch?.name || 'Sucursal no definida'}` : 'Seleccione una caja para definir el alcance' }}</span>
                </p>
            </div>
        </div>

        <section class="br-filter-bar br-cash__toolbar">
            <div class="br-filter-bar__field">
                <label class="form-label">Búsqueda</label>
                <InputText
                    v-model="filters.search"
                    hasDiv
                    title=""
                    placeholder="Buscar caja, responsable o referencia"
                    @keyup.enter="refreshActiveView"/>
            </div>
            <div class="br-filter-bar__actions">
                <button type="button" class="br-btn br-btn-search" @click="refreshActiveView">
                    <span>Buscar</span>
                </button>
                <button
                    v-if="activeView === 'registers'"
                    type="button"
                    class="br-btn br-btn-action-create"
                    data-bs-toggle="tooltip"
                    title="Agregar caja a una sucursal"
                    @click="openModal('register')">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Agregar caja</span>
                </button>
                <button
                    v-if="activeView === 'movements'"
                    type="button"
                    class="br-btn br-btn-action-update"
                    data-bs-toggle="tooltip"
                    title="Registrar ingreso, salida o ajuste manual"
                    :disabled="!currentSession"
                    @click="openModal('movement')">
                    <i class="fa-solid fa-right-left" aria-hidden="true"></i>
                    <span>Registrar movimiento</span>
                </button>
                <button
                    type="button"
                    class="br-btn br-btn-action-download"
                    data-bs-toggle="tooltip"
                    title="Descargar movimientos"
                    @click="downloadMovements">
                    <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                    <span class="d-inline d-lg-none">Descargar</span>
                </button>
            </div>
        </section>

        <section v-if="activeView === 'registers'" class="br-cash__panel">
            <Loader v-if="loading.registers"/>
            <div v-else-if="registers.length" class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
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
                        <tr v-for="register in registers" :key="register.id">
                            <td>
                                <strong>{{ register.name }}</strong>
                                <span class="br-cash__muted d-block">{{ register.code }}</span>
                            </td>
                            <td>{{ register.branch?.name || 'Sin sucursal' }}</td>
                            <td>
                                <span v-if="register.is_open" class="br-status br-status-active">Abierta</span>
                                <span v-else class="br-status br-status-inactive">Sin apertura</span>
                            </td>
                            <td class="text-end fw-semibold">S/ {{ separatorNumber(register.current_amount) }}</td>
                            <td class="text-center">
                                <span :class="['br-status-label', register.status === 'active' ? 'br-status-active' : 'br-status-inactive']">
                                    {{ register.status === 'active' ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button
                                    v-if="register.is_open"
                                    type="button"
                                    class="br-icon-action br-icon-action-edit"
                                    data-bs-toggle="tooltip"
                                    title="Cerrar caja"
                                    @click="openModal('close', register)">
                                    <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="br-icon-action br-icon-action-primary"
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

        <section v-if="activeView === 'sessions'" class="br-cash__panel">
            <Loader v-if="loading.sessions"/>
            <div v-else-if="sessions.data?.length" class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
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
                                <span class="br-cash__muted d-block">{{ session.branch?.name }}</span>
                            </td>
                            <td>{{ formatDateTime(session.opened_at) }}</td>
                            <td>{{ session.closed_at ? formatDateTime(session.closed_at) : 'En curso' }}</td>
                            <td class="text-end">S/ {{ separatorNumber(session.expected_amount) }}</td>
                            <td class="text-end">S/ {{ separatorNumber(session.counted_amount) }}</td>
                            <td class="text-end" :class="differenceClass(session.difference_amount)">
                                S/ {{ separatorNumber(session.difference_amount) }}
                            </td>
                            <td class="text-center">
                                <span :class="['br-status-label', session.status === 'open' ? 'br-status-active' : 'br-status-inactive']">
                                    {{ session.status === 'open' ? 'Abierta' : 'Cerrada' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <Paginator :links="sessions.links" @clickPage="getSessions"/>
            </div>
            <WithoutData v-else/>
        </section>

        <section v-if="activeView === 'summary'" class="br-cash__panel">
            <Loader v-if="loading.summary"/>
            <div v-else class="br-cash-summary">
                <article class="br-cash-summary__card">
                    <span>Fondo inicial</span>
                    <strong>S/ {{ separatorNumber(summary.totals.opening) }}</strong>
                </article>
                <article class="br-cash-summary__card">
                    <span>Esperado</span>
                    <strong>S/ {{ separatorNumber(summary.totals.expected) }}</strong>
                </article>
                <article class="br-cash-summary__card">
                    <span>Contado</span>
                    <strong>S/ {{ separatorNumber(summary.totals.counted) }}</strong>
                </article>
                <article class="br-cash-summary__card">
                    <span>Diferencia</span>
                    <strong :class="differenceClass(summary.totals.difference)">S/ {{ separatorNumber(summary.totals.difference) }}</strong>
                </article>
            </div>
            <div v-if="summary.payments.length" class="table-responsive mt-3">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Método de pago</th>
                            <th class="text-end">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in summary.payments" :key="payment.payment_method_id || 'opening'">
                            <td>{{ payment.payment_method?.name || 'Efectivo / apertura' }}</td>
                            <td class="text-end fw-semibold">S/ {{ separatorNumber(payment.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="activeView === 'movements'" class="br-cash__panel">
            <Loader v-if="loading.movements"/>
            <div v-else-if="movements.data?.length" class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
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
                                <span v-if="movement.user?.name" class="br-cash__muted d-block">{{ movement.user.name }}</span>
                            </td>
                            <td class="text-end fw-semibold">S/ {{ separatorNumber(movement.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
                <Paginator :links="movements.links" @clickPage="getMovements"/>
            </div>
            <WithoutData v-else/>
        </section>
    </section>

    <div class="modal fade" id="cashRegisterModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content br-entity-modal">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Caja</p>
                        <h5 class="modal-title br-entity-modal__title">Agregar caja</h5>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <InputSlot hasDiv title="Sucursal" :titleClass="['form-label']" isRequired hasTextBottom :textBottomInfo="errors.branch_id">
                        <template v-slot:input>
                            <v-select
                                v-model="forms.register.branch"
                                :options="branchOptions"
                                class="bg-white"
                                :clearable="false"
                                :searchable="branchOptions.length > 6"
                                placeholder="Seleccione sucursal"/>
                        </template>
                    </InputSlot>
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
                    <InputSlot hasDiv title="Estado" :titleClass="['form-label']" isRequired hasTextBottom :textBottomInfo="errors.status">
                        <template v-slot:input>
                            <v-select
                                v-model="forms.register.status"
                                :options="statusOptions"
                                class="bg-white"
                                :clearable="false"
                                :searchable="false"/>
                        </template>
                    </InputSlot>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="saving" @click="submitRegister">
                        Agregar caja
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cashOpenModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content br-entity-modal">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Caja</p>
                        <h5 class="modal-title br-entity-modal__title">Abrir caja</h5>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <InputSlot hasDiv title="Caja" :titleClass="['form-label']" isRequired hasTextBottom :textBottomInfo="errors.cash_register_id">
                        <template v-slot:input>
                            <v-select
                                v-model="forms.open.cash_register"
                                :options="registerOptions"
                                class="bg-white"
                                :clearable="false"
                                :searchable="false"
                                placeholder="Seleccione una caja"/>
                        </template>
                    </InputSlot>
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
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="saving" @click="submitOpen">Abrir caja</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cashCloseModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content br-entity-modal">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Arqueo</p>
                        <h5 class="modal-title br-entity-modal__title">Cerrar caja</h5>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="br-cash-close-summary">
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
                    <section v-if="forms.close.requires_inventory_count" class="br-cash-inventory-count mt-3">
                        <header class="br-cash-inventory-count__header">
                            <div>
                                <strong>Conteo físico de inventario</strong>
                                <span>Revisa las existencias reales antes de cerrar la caja principal.</span>
                            </div>
                            <button
                                type="button"
                                class="br-btn br-btn-sm br-btn-outline-secondary"
                                @click="fillInventoryCountWithSystemStock">
                                <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                                <span>Usar saldo sistema</span>
                            </button>
                        </header>
                        <div class="br-cash-inventory-count__summary">
                            <span>{{ forms.close.inventory_counts.length }} productos por almacén</span>
                            <strong :class="differenceClass(inventoryCountDifferenceTotal)">
                                Diferencia neta: {{ separatorNumber(inventoryCountDifferenceTotal) }}
                            </strong>
                        </div>
                        <div v-if="forms.close.inventory_counts.length" class="table-responsive">
                            <table class="table br-entity-table mb-0">
                                <thead class="br-table-header-surface">
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
                                            <span class="br-cash__muted d-block">
                                                {{ [row.item_internal_code, row.brand_name].filter(Boolean).join(' · ') || 'Producto sin referencia' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span>{{ row.warehouse_name }}</span>
                                            <span class="br-cash__muted d-block">{{ row.branch_name }}</span>
                                        </td>
                                        <td class="text-end fw-semibold">{{ separatorNumber(row.system_quantity) }}</td>
                                        <td class="text-end">
                                            <InputNumber
                                                v-model="row.counted_quantity"
                                                title=""
                                                :titleClass="[]"
                                                :inputClass="['form-control', {'is-invalid': row.counted_quantity === '' || row.counted_quantity === null}]"
                                                :minValue="0"/>
                                        </td>
                                        <td class="text-end fw-semibold" :class="differenceClass(inventoryRowDifference(row))">
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
                        <p v-else class="br-cash-inventory-count__empty mb-0">
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
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-update" :disabled="saving" @click="submitClose">Cerrar caja</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cashMovementModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content br-entity-modal">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Movimiento manual</p>
                        <h5 class="modal-title br-entity-modal__title">Registrar operación</h5>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <InputSlot hasDiv title="Tipo de movimiento" :titleClass="['form-label']" isRequired hasTextBottom :textBottomInfo="errors.movement_type">
                        <template v-slot:input>
                            <v-select
                                v-model="forms.movement.type"
                                :options="movementTypeOptions"
                                class="bg-white"
                                :clearable="false"
                                :searchable="false"
                                placeholder="Seleccione"/>
                        </template>
                    </InputSlot>
                    <InputSlot hasDiv title="Método de pago" :titleClass="['form-label']" hasTextBottom :textBottomInfo="errors.payment_method_id">
                        <template v-slot:input>
                            <v-select
                                v-model="forms.movement.payment_method"
                                :options="paymentMethodOptions"
                                class="bg-white"
                                :clearable="true"
                                :searchable="false"
                                placeholder="Efectivo / caja"/>
                        </template>
                    </InputSlot>
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
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-update" :disabled="saving" @click="submitMovement">Registrar operación</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

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
            sessionConfig: Requests.config({entity: "cash_sessions"}),
            movementConfig: Requests.config({entity: "cash_movements"}),
            summaryConfig: Requests.config({entity: "cash_summary"}),
            registers: [],
            sessions: {data: [], links: []},
            movements: {data: [], links: []},
            summary: {sessions: [], payments: [], totals: {opening: 0, expected: 0, counted: 0, difference: 0}},
            selectedRegister: null,
            activeView: this.initialView,
            filters: {search: ""},
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
            return this.forms.close.inventory_counts.reduce((total, row) => total + this.inventoryRowDifference(row), 0);
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-cash", {addClass: "open"});
        Utils.navbarItem(this.activeMenuId(), {addClass: "active"});
        this.initParams();
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
            this.selectedRegister = this.registerOptions[0] || null;

            await this.refreshAll();
        },
        async refreshAll() {
            await this.getRegisters();
            await this.refreshActiveView();
            Alerts.tooltips({});
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
                    search: this.filters.search || null
                }
            };
        },
        async getRegisters() {
            this.loading.registers = true;
            const result = await Requests.get({route: this.config.routes.list});
            this.loading.registers = false;

            if(!Requests.valid({result})) return;

            this.registers = result.data.data || [];
            this.options.registers = this.registers;

            if(this.selectedRegister) {
                this.selectedRegister = this.registerOptions.find(register => register.id === this.selectedRegister.id) || this.selectedRegister;
            }
        },
        async getSessions(pageUrl = null) {
            this.loading.sessions = true;
            const result = await Requests.get({
                route: pageUrl || this.sessionConfig.routes.list,
                data: this.baseFilters()
            });
            this.loading.sessions = false;

            if(Requests.valid({result})) this.sessions = result.data.data;
        },
        async getMovements(pageUrl = null) {
            this.loading.movements = true;
            const result = await Requests.get({
                route: pageUrl || this.movementConfig.routes.list,
                data: this.baseFilters()
            });
            this.loading.movements = false;

            if(Requests.valid({result})) this.movements = result.data.data;
        },
        async getSummary() {
            this.loading.summary = true;
            const result = await Requests.get({
                route: this.summaryConfig.routes.data,
                data: this.baseFilters()
            });
            this.loading.summary = false;

            if(Requests.valid({result})) this.summary = result.data.data;
        },
        openModal(type, register = null) {
            if(register) this.selectedRegister = this.registerOptions.find(item => item.id === register.id) || register;
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

            return Number(row.counted_quantity || 0) - Number(row.system_quantity || 0);
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
            const amount = Number(value || 0);
            if(amount > 0) return "text-success";
            if(amount < 0) return "text-danger";
            return "text-muted";
        },
        movementTypeLabel(type) {
            return this.options.movementTypes.find(item => item.id === type)?.label || type || "-";
        }
    }
};
</script>
