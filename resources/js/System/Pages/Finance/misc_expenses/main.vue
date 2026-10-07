<template>
    <Breadcrumb :list="[{title: 'Caja y finanzas'}, {title: 'Gastos varios', active: true}]"/>

    <section class="br-misc-expenses">
        <header class="br-module-heading">
            <div>
                <span class="br-module-heading__eyebrow">Control operativo</span>
                <h1>Gastos varios</h1>
                <p>Registra egresos no asociados a compras y conserva su responsable y trazabilidad de caja.</p>
            </div>
            <button type="button" class="br-btn br-btn-action-create" @click="openCreate">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Registrar gasto</span>
            </button>
        </header>

        <section class="br-filter-bar">
            <div class="br-filter-bar__field">
                <label class="form-label">Búsqueda</label>
                <input v-model.trim="filters.word" type="search" class="form-control" placeholder="Concepto o referencia" @keyup.enter="loadExpenses()">
            </div>
            <div class="br-filter-bar__field">
                <label class="form-label">Estado</label>
                <select v-model="filters.status" class="form-select" @change="loadExpenses()">
                    <option value="">Todos</option>
                    <option value="active">Activo</option>
                    <option value="canceled">Anulado</option>
                </select>
            </div>
            <div class="br-filter-bar__field">
                <label class="form-label">Sucursal</label>
                <select v-model="filters.branch_id" class="form-select" @change="loadExpenses()">
                    <option value="">Todas</option>
                    <option v-for="branch in options.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                </select>
            </div>
            <div class="br-filter-bar__actions">
                <button type="button" class="br-btn br-btn-search" @click="loadExpenses()">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span>Buscar</span>
                </button>
            </div>
        </section>

        <section class="br-entity-panel">
            <div v-if="loading" class="br-loading-state">Cargando gastos...</div>
            <div v-else-if="expenses.data?.length" class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Sucursal / caja</th>
                            <th>Responsable</th>
                            <th class="text-end">Importe</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in expenses.data" :key="expense.id">
                            <td>{{ formatDate(expense.expense_date) }}</td>
                            <td>
                                <strong>{{ expense.concept }}</strong>
                                <small class="d-block text-muted">{{ expense.category?.name || expense.reference || 'Sin categoría' }}</small>
                            </td>
                            <td>
                                {{ expense.branch?.name || "Sin sucursal" }}
                                <small v-if="expense.cash_session_id" class="d-block text-muted">Vinculado a caja</small>
                            </td>
                            <td>{{ expense.responsible_user?.name || "Sin responsable" }}</td>
                            <td class="text-end br-amount-inline__amount">{{ money(expense) }}</td>
                            <td class="text-center">
                                <span :class="['br-status-label', expense.status === 'active' ? 'br-status-label--success' : 'br-status-label--danger']">
                                    {{ expense.status === "active" ? "Activo" : "Anulado" }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button v-if="expense.status === 'active'" type="button" class="br-icon-action br-icon-action-danger" title="Anular gasto" @click="cancelExpense(expense)">
                                    <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <Paginator :links="expenses.links || []" @clickPage="loadExpenses($event.url)"/>
            </div>
            <WithoutData v-else/>
        </section>
    </section>

    <div class="modal fade" id="miscExpenseModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content br-entity-modal">
                <div class="modal-header br-entity-modal__header">
                    <div>
                        <p class="br-entity-modal__eyebrow mb-1">Egreso operativo</p>
                        <h5 class="modal-title br-entity-modal__title">Registrar gasto</h5>
                    </div>
                    <button type="button" class="br-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body br-entity-modal__body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Concepto *</label>
                            <input v-model.trim="form.concept" type="text" class="form-control" maxlength="255">
                            <small v-if="error('concept')" class="text-danger">{{ error("concept") }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha *</label>
                            <input v-model="form.expense_date" type="date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Importe *</label>
                            <input v-model="form.amount" type="number" min="0.001" step="0.001" class="form-control">
                            <small v-if="error('amount')" class="text-danger">{{ error("amount") }}</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Moneda *</label>
                            <select v-model="form.currency_id" class="form-select">
                                <option v-for="currency in options.currencies" :key="currency.id" :value="currency.id">{{ currency.name || currency.code }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <select v-model="form.misc_expense_category_id" class="form-select">
                                <option :value="null">Sin categoría</option>
                                <option v-for="category in options.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sucursal</label>
                            <select v-model="form.branch_id" class="form-select">
                                <option :value="null">Sin sucursal</option>
                                <option v-for="branch in options.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sesión de caja</label>
                            <select v-model="form.cash_session_id" class="form-select">
                                <option :value="null">No afectar caja</option>
                                <option v-for="session in options.cashSessions" :key="session.id" :value="session.id">{{ session.register?.name || `Sesión ${session.id}` }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Método de pago</label>
                            <select v-model="form.payment_method_id" class="form-select">
                                <option :value="null">Efectivo / caja</option>
                                <option v-for="method in options.paymentMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsable</label>
                            <select v-model="form.responsible_user_id" class="form-select">
                                <option :value="null">Sin responsable asignado</option>
                                <option v-for="user in options.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referencia</label>
                            <input v-model.trim="form.reference" type="text" class="form-control" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observación</label>
                            <input v-model.trim="form.observation" type="text" class="form-control" maxlength="2000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer br-entity-modal__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="br-btn br-btn-action-create" :disabled="saving" @click="submit">Registrar gasto</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

export default {
    data() {
        return {
            routes: Requests.config({entity: "misc_expenses"}).routes,
            expenses: {data: [], links: []},
            filters: {word: "", status: "", branch_id: ""},
            options: {branches: [], cashSessions: [], paymentMethods: [], currencies: [], categories: [], users: []},
            form: {},
            errors: {},
            loading: false,
            saving: false
        };
    },
    mounted() {
        Utils.navbarItem("menu-parent-cash", {addClass: "open"});
        Utils.navbarItem("menu-misc-expenses", {addClass: "active"});
        this.initialize();
    },
    methods: {
        async initialize() {
            const [params] = await Promise.all([Requests.get({route: this.routes.initParams}), this.loadExpenses()]);
            if(params.bool) this.options = {...this.options, ...(params.data.data || {})};
        },
        async loadExpenses(url = null) {
            this.loading = true;
            const result = await Requests.get({route: url || this.routes.list, data: this.filters});
            this.loading = false;
            if(result.bool) this.expenses = result.data;
        },
        openCreate() {
            const today = new Date().toISOString().slice(0, 10);
            this.errors = {};
            this.form = {
                concept: "", expense_date: today, amount: "",
                currency_id: this.options.currencies[0]?.id || null,
                misc_expense_category_id: null, branch_id: this.options.branches[0]?.id || null,
                cash_session_id: null, payment_method_id: null,
                responsible_user_id: null, reference: "", observation: ""
            };
            bootstrap.Modal.getOrCreateInstance(document.getElementById("miscExpenseModal")).show();
        },
        async submit() {
            this.saving = true;
            const result = await Requests.post({route: this.routes.store, data: this.form});
            this.saving = false;
            if(!result.bool) {
                this.errors = result.data?.errors || {};
                Alerts.toastrs({type: "error", subtitle: result.data?.msg || "Revisa los campos ingresados."});
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById("miscExpenseModal"))?.hide();
            Alerts.toastrs({type: "success", subtitle: result.data.msg});
            this.loadExpenses();
        },
        async cancelExpense(expense) {
            const confirmation = await Swal.fire({
                title: "¿Anular este gasto?", text: "La salida vinculada a caja también quedará anulada.",
                icon: "warning", showCancelButton: true, confirmButtonText: "Sí, anular", cancelButtonText: "Cancelar"
            });
            if(!confirmation.isConfirmed) return;
            const result = await Requests.patch({route: `${this.routes.consult}/${expense.id}/cancel`});
            Alerts.toastrs({type: result.bool ? "success" : "error", subtitle: result.data.msg});
            if(result.bool) this.loadExpenses();
        },
        error(field) {
            const value = this.errors?.[field];
            return Array.isArray(value) ? value[0] : value || "";
        },
        formatDate(value) {
            if(!value) return "-";
            return new Intl.DateTimeFormat("es-PE").format(new Date(`${String(value).slice(0, 10)}T00:00:00`));
        },
        money(expense) {
            return `${expense.currency?.sign || expense.currency?.code || "S/"} ${Number(expense.amount || 0).toFixed(3)}`;
        }
    }
};
</script>
