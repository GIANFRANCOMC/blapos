<template>
    <FgPageHeader title="Gastos varios" description="Consulta los egresos operativos y su trazabilidad en caja.">
        <template #actions>
            <button type="button" class="fg-button" :disabled="initialLoading" @click="openCreate">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Registrar gasto</span>
            </button>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <section class="fg-misc-expenses">
                <FgResponsiveFilters
                    id="cash-expense-filters"
                    label="Filtros de gastos varios"
                    :loading="loading"
                    @search="searchExpenses"
                    @clear="clearFilters">
                    <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                        <SearchInput v-model.trim="filters.word" title="Búsqueda" placeholder="Concepto o referencia"/>
                    </div>
                    <FgField class="col-12 col-sm-6 col-lg-3" label="Estado" input-id="expense-filter-status">
                        <select id="expense-filter-status" v-model="filters.status" class="fg-native-select">
                            <option value="">Todos</option>
                            <option value="active">Activo</option>
                            <option value="canceled">Anulado</option>
                        </select>
                    </FgField>
                    <FgField class="col-12 col-sm-6 col-lg-3" label="Sucursal" input-id="expense-filter-branch">
                        <select id="expense-filter-branch" v-model="filters.branch_id" class="fg-native-select">
                            <option value="">Todas</option>
                            <option v-for="branch in options.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                        </select>
                    </FgField>
                </FgResponsiveFilters>

                <section class="fg-entity-panel">
                    <FgListLoading v-if="loading" label="Buscando gastos…"/>
                    <FgEmptyState v-else-if="listError" title="No se pudieron consultar los gastos" description="Vuelve a intentar con los filtros aplicados.">
                        <button type="button" class="fg-button fg-button--secondary" @click="loadExpenses()">Reintentar</button>
                    </FgEmptyState>
                    <ResponsiveTable v-else-if="expenses.data?.length" class="fg-sales-table-scroll">
                        <table role="table" class="fg-table fg-record-table mb-0">
                            <thead role="rowgroup" class="fg-table-header-surface">
                                <tr role="row">
                                    <th scope="col" role="columnheader">Fecha</th>
                                    <th scope="col" role="columnheader">Concepto</th>
                                    <th scope="col" role="columnheader">Sucursal / caja</th>
                                    <th scope="col" role="columnheader">Responsable</th>
                                    <th scope="col" role="columnheader" class="text-end">Importe</th>
                                    <th scope="col" role="columnheader" class="text-center">Estado</th>
                                    <th scope="col" role="columnheader" class="text-center"><span class="visually-hidden">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="row" v-for="expense in expenses.data" :key="expense.id">
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Fecha</span>
                                        <div class="fg-record-table__value">{{ formatDate(expense.expense_date) }}</div>
                                    </td>
                                    <td role="cell" class="fg-record-table__identity">
                                        <strong>{{ expense.concept }}</strong>
                                        <small class="d-block text-muted">{{ expense.category?.name || expense.reference || 'Sin categoría' }}</small>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Sucursal / caja</span>
                                        <div class="fg-record-table__value">{{ expense.branch?.name || "Sin sucursal" }}<small v-if="expense.cash_session_id" class="d-block text-muted">Vinculado a caja</small></div>
                                    </td>
                                    <td role="cell">
                                        <span class="fg-record-table__label" aria-hidden="true">Responsable</span>
                                        <div class="fg-record-table__value">{{ expense.responsible_user?.name || "Sin responsable" }}</div>
                                    </td>
                                    <td role="cell" class="text-end fg-money__value">
                                        <span class="fg-record-table__label" aria-hidden="true">Importe</span>
                                        <div class="fg-record-table__value">{{ money(expense) }}</div>
                                    </td>
                                    <td role="cell" class="text-center fg-record-table__state">
                                        <span :class="['fg-badge', expense.status === 'active' ? 'fg-badge--success' : 'fg-badge--danger']">
                                            {{ expense.status === "active" ? "Activo" : "Anulado" }}
                                        </span>
                                    </td>
                                    <td role="cell" class="text-center fg-record-table__actions">
                                        <button v-if="expense.status === 'active'" type="button" class="fg-icon-action fg-icon-action-danger" title="Anular gasto" :aria-label="'Anular gasto: ' + expense.concept" @click="cancelExpense(expense)">
                                            <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </ResponsiveTable>
                    <FgEmptyState v-else :title="hasAppliedFilters ? 'No encontramos gastos' : 'Aún no hay gastos registrados'" :description="hasAppliedFilters ? 'Prueba con filtros diferentes.' : 'Los egresos operativos registrados se mostrarán aquí.'"/>
                    <Paginator v-if="!listError" :links="expenses.links || []" :pagination="expenses" :loading="loading" @clickPage="loadExpenses($event.url)"/>
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
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgResponsiveFilters from "@System/Components/Figma/FgResponsiveFilters.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import InputNumber from "@System/Components/InputNumber.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    mixins: [InitialPageMixin],
    components: {FgPageHeader, FgResponsiveFilters, FgListLoading, FgEmptyState, ResponsiveTable, FgField, InputNumber, SearchInput},
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
            listError: false,
            saving: false
        };
    },
    computed: {
        hasAppliedFilters() {
            return Object.values(this.appliedFilters ?? {}).some(value => String(value || "").trim() !== "");
        }
    },
    async mounted() {
        navbarItem("menu-parent-cash", {addClass: "open"});
        navbarItem("menu-misc-expenses", {addClass: "active"});
        const initialized = await this.loadInitialParams(() => this.initialize());
        if(initialized) await this.loadExpenses();
    },
    methods: {
        clearFilters() {
            this.filters = {word: "", status: "", branch_id: ""};
            return Alerts.showFiltersCleared();
        },
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
            if(this.loading) return;
            this.listError = false;
            this.appliedFilters ??= {...this.filters};
            this.loading = true;

            try {
                this.expenses = Requests.emptyPagination();
                const result = await Requests.get({route: url || this.routes.list, data: this.appliedFilters});
                this.listError = !result.bool;
                if(!this.listError) this.expenses = result.data;
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
            if(this.saving) return;
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
            const confirmed = await Alerts.confirmAction({
                title: "¿Anular gasto?",
                text: "Si el gasto está vinculado a caja, su salida también se anulará.",
                icon: "warning",
                confirmButtonText: "Anular gasto"
            });
            if(!confirmed) return;
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
