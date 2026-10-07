<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-receivables">
        <header class="br-receivables__header">
            <div>
                <p class="br-receivables__eyebrow">Gestión de compras</p>
                <h1>Cuentas por pagar</h1>
                <p>Controla saldos, vencimientos y pagos generados por compras a crédito.</p>
            </div>
            <div class="br-receivables__header-meta">
                <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                <span><strong>{{ summary.accounts || 0 }}</strong> cuentas registradas</span>
            </div>
        </header>

        <div class="br-receivables__metrics">
            <article v-for="metric in metrics" :key="metric.key" class="br-receivables-metric" :class="`br-receivables-metric--${metric.tone}`">
                <span class="br-receivables-metric__icon"><i :class="metric.icon" aria-hidden="true"></i></span>
                <div>
                    <small>{{ metric.label }}</small>
                    <strong class="br-amount-inline__amount">{{ summaryAmount(metric.key) }}</strong>
                    <span v-if="metric.key === 'overdue'">{{ summary.overdue_accounts || 0 }} cuentas vencidas</span>
                    <span v-else>{{ metric.description }}</span>
                </div>
            </article>
        </div>

        <section class="br-filter-bar br-receivables__filters">
            <label class="br-filter-bar__field br-receivables__search">
                <span class="form-label">Búsqueda</span>
                <span class="br-receivables__search-control">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input v-model.trim="filters.search" type="search" class="form-control" placeholder="Proveedor o documento de compra" @keyup.enter="loadAccounts()">
                </span>
            </label>
            <label class="br-filter-bar__field">
                <span class="form-label">Estado</span>
                <select v-model="filters.status" class="form-select" @change="loadAccounts()">
                    <option value="">Todos</option>
                    <option value="pending">Pendientes</option>
                    <option value="partial">Pago parcial</option>
                    <option value="overdue">Vencidas</option>
                    <option value="paid">Pagadas</option>
                    <option value="canceled">Anuladas</option>
                </select>
            </label>
            <label class="br-filter-bar__field">
                <span class="form-label">Desde</span>
                <input v-model="filters.date_from" type="date" class="form-control">
            </label>
            <label class="br-filter-bar__field">
                <span class="form-label">Hasta</span>
                <input v-model="filters.date_to" type="date" class="form-control">
            </label>
            <div class="br-filter-bar__actions">
                <button type="button" class="br-btn br-btn-search" @click="loadAccounts()">
                    <span>Buscar</span>
                </button>
                <button type="button" class="br-btn br-btn-cancel" :disabled="!hasFilters" @click="clearFilters">
                    <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                    <span>Limpiar</span>
                </button>
            </div>
        </section>

        <section class="br-receivables__panel">
            <Loader v-if="loading.list"/>
            <template v-else-if="accounts.data?.length">
                <div class="table-responsive">
                    <table class="table br-entity-table br-receivables-table mb-0">
                        <thead class="br-table-header-surface">
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
                                    <strong class="br-receivables-table__document">{{ account.document }}</strong>
                                    <small>{{ account.branch }} · {{ account.warehouse }}</small>
                                </td>
                                <td>
                                    <strong>{{ account.supplier.name }}</strong>
                                    <small>{{ account.supplier.document_number }}</small>
                                </td>
                                <td>{{ formatDate(account.due_date) }}</td>
                                <td class="text-end br-amount-inline__amount">{{ money(account, account.total_amount) }}</td>
                                <td class="text-end br-amount-inline__amount">{{ money(account, account.paid_amount) }}</td>
                                <td class="text-end br-amount-inline__amount br-receivables-table__pending">{{ money(account, account.pending_amount) }}</td>
                                <td>
                                    <span class="br-receivables-status" :class="`br-receivables-status--${account.status}`">{{ statusLabel(account.status) }}</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="br-icon-action br-icon-action-edit" title="Ver cronograma" @click="openDetail(account)">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="accounts.links" class="d-flex justify-content-center mt-3">
                    <Paginator :links="accounts.links" @clickPage="loadAccounts"/>
                </div>
            </template>
            <WithoutData v-else type="image"/>
        </section>

        <section v-if="selectedAccount" class="br-receivables__panel mt-3">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <p class="br-receivables__eyebrow mb-1">Detalle de cuenta</p>
                    <h2 class="h5 mb-1">{{ selectedAccount.document }} · {{ selectedAccount.supplier.name }}</h2>
                    <span class="br-receivables-status" :class="`br-receivables-status--${selectedAccount.status}`">{{ statusLabel(selectedAccount.status) }}</span>
                </div>
                <button type="button" class="br-modal-close" aria-label="Cerrar detalle" @click="selectedAccount = null">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <Loader v-if="loading.detail"/>
            <div v-else class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <thead class="br-table-header-surface">
                        <tr><th>Cuota</th><th>Vencimiento</th><th class="text-end">Importe</th><th class="text-end">Pagado</th><th class="text-end">Pendiente</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="installment in selectedAccount.installments" :key="installment.id">
                            <td>#{{ installment.number }}</td>
                            <td>{{ formatDate(installment.due_date) }}</td>
                            <td class="text-end br-amount-inline__amount">{{ money(selectedAccount, installment.amount) }}</td>
                            <td class="text-end br-amount-inline__amount">{{ money(selectedAccount, installment.paid_amount) }}</td>
                            <td class="text-end br-amount-inline__amount">{{ money(selectedAccount, installment.pending_amount) }}</td>
                            <td><span class="br-receivables-status" :class="`br-receivables-status--${installment.status}`">{{ statusLabel(installment.status) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>

<script>
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";

export default {
    name: "AccountsPayableMain",
    data() {
        return {
            config: Requests.config({entity: "accounts_payable"}),
            accounts: {data: [], links: []},
            summary: {accounts: 0, overdue_accounts: 0, amounts: []},
            selectedAccount: null,
            filters: {search: "", status: "", date_from: "", date_to: ""},
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
    mounted() {
        Utils.navbarItem("menu-parent-purchases", {addClass: "open"});
        Utils.navbarItem("menu-purchases-accounts-payable", {addClass: "active"});
        this.loadAccounts();
    },
    methods: {
        async loadAccounts(pageUrl = null) {
            this.loading.list = true;
            const result = await Requests.get({route: pageUrl || this.config.routes.list, data: this.filters, showAlert: true});
            this.loading.list = false;

            if(!Requests.valid({result})) return;

            this.accounts = result.data.data || {data: [], links: []};
            this.summary = result.data.summary || {accounts: 0, overdue_accounts: 0, amounts: []};
        },
        async openDetail(account) {
            this.selectedAccount = account;
            this.loading.detail = true;
            const result = await Requests.get({route: `${this.config.routes.consult}/${account.id}`, showAlert: true});
            this.loading.detail = false;

            if(Requests.valid({result})) this.selectedAccount = result.data.data;
        },
        clearFilters() {
            this.filters = {search: "", status: "", date_from: "", date_to: ""};
            this.loadAccounts();
        },
        summaryAmount(key) {
            if(!this.summary.amounts?.length) return "S/ 0.000";

            return this.summary.amounts.map(amount => `${amount.sign || amount.code} ${Utils.separatorNumber(amount[key] || 0)}`).join(" · ");
        },
        money(account, value) {
            return `${account.currency?.sign || account.currency?.code || ""} ${Utils.separatorNumber(value || 0)}`.trim();
        },
        formatDate(value) {
            if(!value) return "Sin fecha";

            return new Intl.DateTimeFormat("es-PE").format(new Date(`${String(value).slice(0, 10)}T00:00:00`));
        },
        statusLabel(status) {
            return {pending: "Pendiente", partial: "Pago parcial", paid: "Pagada", overdue: "Vencida", canceled: "Anulada"}[status] || "Pendiente";
        }
    }
};
</script>
