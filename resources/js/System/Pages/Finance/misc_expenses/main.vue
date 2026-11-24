<template>
    <Breadcrumb :list="[{title: 'Caja'}, {title: 'Gastos varios', active: true}]"/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <section class="fg-misc-expenses">
                <header class="fg-module-heading">
                    <div>
                        <span class="fg-module-heading__eyebrow">Control operativo</span>
                        <h1>Gastos varios</h1>
                        <p>Registra egresos no asociados a compras y conserva su responsable y trazabilidad de caja.</p>
                    </div>
                    <button type="button" class="fg-button" @click="openCreate">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span>Registrar gasto</span>
                    </button>
                </header>

                <section class="fg-filter-bar">
                    <div class="fg-filter-bar__field">
                        <SearchInput v-model.trim="filters.word" title="Búsqueda" placeholder="Concepto o referencia"/>
                    </div>
                    <div class="fg-filter-bar__field">
                        <label for="expense-filter-status" class="form-label">Estado</label>
                        <select id="expense-filter-status" v-model="filters.status" class="form-select">
                            <option value="">Todos</option>
                            <option value="active">Activo</option>
                            <option value="canceled">Anulado</option>
                        </select>
                    </div>
                    <div class="fg-filter-bar__field">
                        <label for="expense-filter-branch" class="form-label">Sucursal</label>
                        <select id="expense-filter-branch" v-model="filters.branch_id" class="form-select">
                            <option value="">Todas</option>
                            <option v-for="branch in options.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                    </div>
                    <div class="fg-filter-bar__actions">
                        <SearchButton @click="searchExpenses" :disabled="loading"/>
                    </div>
                </section>

                <section class="fg-entity-panel">
                    <div v-if="loading" class="fg-loading-state">Cargando gastos...</div>
                    <div v-else-if="expenses.data?.length" class="table-responsive">
                        <table class="table fg-entity-table mb-0">
                            <thead class="fg-table-header-surface">
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
                                    <td class="text-end fg-amount-inline__amount">{{ money(expense) }}</td>
                                    <td class="text-center">
                                        <span :class="['fg-badge', expense.status === 'active' ? 'fg-badge--success' : 'fg-badge--danger']">
                                            {{ expense.status === "active" ? "Activo" : "Anulado" }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button v-if="expense.status === 'active'" type="button" class="fg-icon-action fg-icon-action-danger" title="Anular gasto" @click="cancelExpense(expense)">
                                            <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                    <WithoutData v-else/>
                    <Paginator :links="expenses.links || []" :pagination="expenses" :loading="loading" @clickPage="loadExpenses($event.url)"/>
                </section>
            </section>

        </div>
    </InitialLoadTransition>
    <div class="modal fade fg-modal" id="miscExpenseModal" tabindex="-1" aria-labelledby="expense-modal-title" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="expense-modal-title" class="modal-title fg-modal__title">Registrar gasto</h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body fg-modal__body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="expense-concept" class="form-label">Concepto *</label>
                            <input id="expense-concept" v-model.trim="form.concept" type="text" class="form-control" maxlength="255" :aria-invalid="Boolean(error('concept'))" :aria-describedby="error('concept') ? 'expense-concept-error' : undefined">
                            <small v-if="error('concept')" id="expense-concept-error" class="text-danger">{{ error("concept") }}</small>
                        </div>
                        <div class="col-md-4">
                            <label for="expense-date" class="form-label">Fecha *</label>
                            <input id="expense-date" v-model="form.expense_date" type="date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <InputNumber v-model="form.amount" title="Importe" is-required :has-div="false" :min-value="0"
                                         :has-text-bottom="Boolean(error('amount'))" :text-bottom-info="[error('amount')]"/>
                        </div>
                        <div class="col-md-4">
                            <label for="expense-currency" class="form-label">Moneda *</label>
                            <select id="expense-currency" v-model="form.currency_id" class="form-select">
                                <option v-for="currency in options.currencies" :key="currency.id" :value="currency.id">{{ currency.name || currency.code }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="expense-category" class="form-label">Categoría</label>
                            <select id="expense-category" v-model="form.misc_expense_category_id" class="form-select">
                                <option :value="null">Sin categoría</option>
                                <option v-for="category in options.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expense-branch" class="form-label">Sucursal</label>
                            <select id="expense-branch" v-model="form.branch_id" class="form-select">
                                <option :value="null">Sin sucursal</option>
                                <option v-for="branch in options.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expense-cash-session" class="form-label">Sesión de caja</label>
                            <select id="expense-cash-session" v-model="form.cash_session_id" class="form-select">
                                <option :value="null">No afectar caja</option>
                                <option v-for="session in options.cashSessions" :key="session.id" :value="session.id">{{ session.register?.name || `Sesión ${session.id}` }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expense-payment-method" class="form-label">Método de pago</label>
                            <select id="expense-payment-method" v-model="form.payment_method_id" class="form-select">
                                <option :value="null">Efectivo / caja</option>
                                <option v-for="method in options.paymentMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expense-responsible" class="form-label">Responsable</label>
                            <select id="expense-responsible" v-model="form.responsible_user_id" class="form-select">
                                <option :value="null">Sin responsable asignado</option>
                                <option v-for="user in options.users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="expense-reference" class="form-label">Referencia</label>
                            <input id="expense-reference" v-model.trim="form.reference" type="text" class="form-control" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="expense-observation" class="form-label">Observación</label>
                            <input id="expense-observation" v-model.trim="form.observation" type="text" class="form-control" maxlength="2000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="fg-button" :disabled="saving" @click="submit">Registrar gasto</button>
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
import InputNumber from "@System/Components/InputNumber.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    mixins: [InitialPageMixin],
    components: {SearchButton, InputNumber, SearchInput},
    data() {
        return {
            routes: Requests.config({entity: "misc_expenses"}).routes,
            expenses: {data: [], links: []},
            filters: {word: "", status: "", branch_id: ""},
            appliedFilters: null,
            options: {branches: [], cashSessions: [], paymentMethods: [], currencies: [], categories: [], users: []},
            form: {},
            errors: {},
            loading: false,
            saving: false
        };
    },
    async mounted() {
        navbarItem("menu-parent-cash", {addClass: "open"});
        navbarItem("menu-misc-expenses", {addClass: "active"});
        const initialized = await this.loadInitialParams(() => this.initialize());
        if(initialized) await this.loadExpenses();
    },
    methods: {
        async initialize() {
            const params = await Requests.get({route: this.routes.initParams});
            if(params.bool) this.options = {...this.options, ...(params.data.data || {})};
            return params.bool;
        },
        searchExpenses() {
            if(this.loading) return;
            this.appliedFilters = {...this.filters};
            return this.loadExpenses();
        },
        async loadExpenses(url = null) {
            this.appliedFilters ??= {...this.filters};
            this.loading = true;

            try {
                this.expenses = Requests.emptyPagination();
                const result = await Requests.get({route: url || this.routes.list, data: this.appliedFilters});
                if(result.bool) this.expenses = result.data;
            } finally {
                this.loading = false;
            }
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
                allowEscapeKey: false,
                title: "¿Anular gasto?", text: "La salida vinculada a caja también quedará anulada.",
                icon: "warning", showCancelButton: true, showCloseButton: true, closeButtonAriaLabel: "Cerrar confirmación", confirmButtonText: "Anular gasto"
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
            return Utils.formatCurrencyAmount(expense.amount, expense.currency);
        }
    }
};
</script>
