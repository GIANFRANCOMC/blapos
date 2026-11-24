<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <section class="fg-receivables">
                <header class="fg-receivables__header">
                    <div>
                        <p class="fg-receivables__eyebrow">Gestión de compras</p>
                        <h1>Cuentas por pagar</h1>
                        <p>Controla saldos, vencimientos y pagos generados por compras a crédito.</p>
                    </div>
                    <div class="fg-receivables__header-meta">
                        <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                        <span><strong>{{ summary.accounts || 0 }}</strong> cuentas registradas</span>
                    </div>
                </header>

                <div class="fg-receivables__metrics">
                    <article v-for="metric in metrics" :key="metric.key" class="fg-receivables-metric" :class="`fg-receivables-metric--${metric.tone}`">
                        <span class="fg-receivables-metric__icon"><i :class="metric.icon" aria-hidden="true"></i></span>
                        <div>
                            <small>{{ metric.label }}</small>
                            <strong class="fg-amount-inline__amount">{{ summaryAmount(metric.key) }}</strong>
                            <span v-if="metric.key === 'overdue'">{{ summary.overdue_accounts || 0 }} cuentas vencidas</span>
                            <span v-else>{{ metric.description }}</span>
                        </div>
                    </article>
                </div>

                <section class="fg-filter-bar fg-receivables__filters">
                    <label class="fg-filter-bar__field fg-receivables__search">
                        <span class="form-label">Búsqueda</span>
                        <span class="fg-receivables__search-control">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <SearchInput v-model.trim="filters.search" placeholder="Proveedor o documento de compra"/>
                        </span>
                    </label>
                    <label class="fg-filter-bar__field">
                        <span class="form-label">Estado</span>
                        <select v-model="filters.status" class="form-select">
                            <option value="">Todos</option>
                            <option value="pending">Pendientes</option>
                            <option value="partial">Pago parcial</option>
                            <option value="overdue">Vencidas</option>
                            <option value="paid">Pagadas</option>
                            <option value="canceled">Anuladas</option>
                        </select>
                    </label>
                    <label class="fg-filter-bar__field">
                        <span class="form-label">Desde</span>
                        <input v-model="filters.date_from" type="date" class="form-control">
                    </label>
                    <label class="fg-filter-bar__field">
                        <span class="form-label">Hasta</span>
                        <input v-model="filters.date_to" type="date" class="form-control">
                    </label>
                    <div class="fg-filter-bar__actions">
                        <SearchButton @click="searchAccounts" :disabled="loading.list"/>
                        <ClearFiltersButton button-class="fg-button fg-button--secondary" :disabled="!hasFilters" @clear="clearFilters">
                            <span>Limpiar</span>
                        </ClearFiltersButton>
                    </div>
                </section>

                <section class="fg-receivables__panel">
                    <Loader v-if="loading.list"/>
                    <template v-else-if="accounts.data?.length">
                        <div class="table-responsive">
                            <table class="table fg-entity-table fg-receivables-table mb-0">
                                <thead class="fg-table-header-surface">
                                    <tr>
                                        <th>Compra</th>
                                        <th>Proveedor</th>
                                        <th>Vencimiento</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Pagado</th>
                                        <th class="text-end">Saldo</th>
                                        <th>Estado</th>
                                        <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="account in accounts.data" :key="account.id">
                                        <td>
                                            <strong class="fg-receivables-table__document">{{ account.document }}</strong>
                                            <small>{{ account.branch }} · {{ account.warehouse }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ account.supplier.name }}</strong>
                                            <small>{{ account.supplier.document_number }}</small>
                                        </td>
                                        <td>{{ formatDate(account.due_date) }}</td>
                                        <td class="text-end fg-amount-inline__amount">{{ money(account, account.total_amount) }}</td>
                                        <td class="text-end fg-amount-inline__amount">{{ money(account, account.paid_amount) }}</td>
                                        <td class="text-end fg-amount-inline__amount fg-receivables-table__pending">{{ money(account, account.pending_amount) }}</td>
                                        <td>
                                            <span class="fg-receivables-status" :class="`fg-receivables-status--${account.status}`">{{ statusLabel(account.status) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="fg-icon-action fg-icon-action-edit" title="Ver cronograma" :aria-expanded="selectedAccount?.id === account.id" @click="openDetail(account, $event)">
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </template>
                    <WithoutData v-else type="image"/>
                    <Paginator :links="accounts.links" :pagination="accounts" :loading="loading.list" @clickPage="loadAccounts"/>
                </section>

                <section v-if="selectedAccount" class="fg-receivables__panel mt-3" aria-labelledby="payable-schedule-title">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <p class="fg-receivables__eyebrow mb-1">Detalle de cuenta</p>
                            <h2 id="payable-schedule-title" ref="detailHeading" class="h5 mb-1" tabindex="-1">{{ selectedAccount.document }} · {{ selectedAccount.supplier.name }}</h2>
                            <span class="fg-receivables-status" :class="`fg-receivables-status--${selectedAccount.status}`">{{ statusLabel(selectedAccount.status) }}</span>
                        </div>
                        <button type="button" class="fg-modal-close" aria-label="Cerrar detalle" @click="closeDetail">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                    <Loader v-if="loading.detail"/>
                    <div v-else class="table-responsive">
                        <table class="table fg-entity-table mb-0">
                            <thead class="fg-table-header-surface">
                                <tr><th>Cuota</th><th>Vencimiento</th><th class="text-end">Importe</th><th class="text-end">Pagado</th><th class="text-end">Pendiente</th><th>Estado</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="installment in selectedAccount.installments" :key="installment.id">
                                    <td>#{{ installment.number }}</td>
                                    <td>{{ formatDate(installment.due_date) }}</td>
                                    <td class="text-end fg-amount-inline__amount">{{ money(selectedAccount, installment.amount) }}</td>
                                    <td class="text-end fg-amount-inline__amount">{{ money(selectedAccount, installment.paid_amount) }}</td>
                                    <td class="text-end fg-amount-inline__amount">{{ money(selectedAccount, installment.pending_amount) }}</td>
                                    <td><span class="fg-receivables-status" :class="`fg-receivables-status--${installment.status}`">{{ statusLabel(installment.status) }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </div>
    </InitialLoadTransition>
</template>

<script>
import {navbarItem} from "@System/Layout/Menu.js";
import SearchButton from "@/Shared/SearchButton.vue";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import ClearFiltersButton from "@System/Components/Generics/ClearFiltersButton.vue";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    mixins: [InitialPageMixin],
    components: {SearchButton, ClearFiltersButton, SearchInput},
    name: "AccountsPayableMain",
    data() {
        return {
            config: Requests.config({entity: "accounts_payable"}),
            accounts: {data: [], links: []},
            summary: {accounts: 0, overdue_accounts: 0, amounts: []},
            selectedAccount: null,
            detailRequest: 0,
            detailTrigger: null,
            filters: {search: "", status: "", date_from: "", date_to: ""},
            appliedFilters: null,
            loading: {list: false, detail: false},
            metrics: [
                {key: "total", label: "Total financiado", description: "Capital más recargos", icon: "fa-solid fa-file-invoice-dollar", tone: "primary"},
                {key: "paid", label: "Pagado", description: "Pagos aplicados", icon: "fa-solid fa-circle-check", tone: "success"},
                {key: "pending", label: "Pendiente", description: "Saldo por pagar", icon: "fa-solid fa-hourglass-half", tone: "warning"},
                {key: "overdue", label: "Vencido", description: "Saldo fuera de fecha", icon: "fa-solid fa-triangle-exclamation", tone: "danger"}
            ]
        };
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Compras"}, {title: "Cuentas por pagar", active: true}];
        },
        hasFilters() {
            return Object.values(this.filters).some(value => String(value || "").trim() !== "");
        }
    },
    async mounted() {
        navbarItem("menu-parent-purchases", {addClass: "open"});
        navbarItem("menu-purchases-accounts-payable", {addClass: "active"});
        await this.loadInitialParams(() => this.loadAccounts());
    },
    methods: {
        searchAccounts() {
            if(this.loading.list) return;
            this.appliedFilters = {...this.filters};
            return this.loadAccounts();
        },
        async loadAccounts(pageUrl = null) {
            this.appliedFilters ??= {...this.filters};
            pageUrl = typeof pageUrl === "object" ? pageUrl?.url : pageUrl;
            this.loading.list = true;

            try {
                this.accounts = Requests.emptyPagination();
                this.summary = {accounts: 0, overdue_accounts: 0, amounts: []};
                const result = await Requests.get({route: pageUrl || this.config.routes.list, data: this.appliedFilters, showAlert: true});

                if(!Requests.valid({result})) return;

                this.accounts = result.data.data || {data: [], links: []};
                this.summary = result.data.summary || {accounts: 0, overdue_accounts: 0, amounts: []};
            } finally {
                this.loading.list = false;
            }
        },
        async openDetail(account, event) {
            const request = ++this.detailRequest;
            this.detailTrigger = event.currentTarget;
            this.selectedAccount = account;
            this.loading.detail = true;
            await this.$nextTick();
            this.$refs.detailHeading?.focus();

            const result = await Requests.get({route: `${this.config.routes.consult}/${account.id}`, showAlert: true});
            if(request !== this.detailRequest) return;
            this.loading.detail = false;

            if(Requests.valid({result})) this.selectedAccount = result.data.data;
        },
        closeDetail() {
            this.detailRequest++;
            this.selectedAccount = null;
            this.loading.detail = false;
            this.detailTrigger?.focus();
            this.detailTrigger = null;
        },
        clearFilters() {
            this.filters = {search: "", status: "", date_from: "", date_to: ""};
            return Alerts.showFiltersCleared();
        },
        summaryAmount(key) {
            return Utils.formatCurrencyTotals(this.summary.amounts, key);
        },
        money(account, value) {
            return Utils.formatCurrencyAmount(value, account.currency);
        },
        formatDate(value) {
            if(!value) return "Sin fecha";

            return new Intl.DateTimeFormat("es-PE", {day: "2-digit", month: "2-digit", year: "numeric"})
                .format(new Date(`${String(value).slice(0, 10)}T00:00:00`));
        },
        statusLabel(status) {
            return {pending: "Pendiente", partial: "Pago parcial", paid: "Pagada", overdue: "Vencida", canceled: "Anulada"}[status] || "Pendiente";
        }
    }
};
</script>
