<template>
    <Breadcrumb :list="breadcrumbTitles"/>

    <section class="br-receivables">
        <header class="br-receivables__header">
            <div>
                <p class="br-receivables__eyebrow">Gestión financiera</p>
                <h1>Cuentas por cobrar</h1>
                <p>Controla los saldos y vencimientos generados por ventas a crédito.</p>
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
                    <input
                        v-model.trim="filters.search"
                        type="search"
                        class="form-control"
                        placeholder="Cliente, documento o número de venta"
                        @keyup.enter="loadAccounts()">
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
                                <th>Venta</th>
                                <th>Cliente</th>
                                <th>Próximo vencimiento</th>
                                <th class="text-end">Total por cobrar</th>
                                <th class="text-end">Cobrado</th>
                                <th class="text-end">Saldo</th>
                                <th>Avance</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="account in accounts.data" :key="account.id">
                                <td>
                                    <strong class="br-receivables-table__document">{{ account.document }}</strong>
                                    <small>{{ formatDate(account.issue_date) }}<span v-if="account.branch"> · {{ account.branch }}</span></small>
                                </td>
                                <td>
                                    <strong>{{ account.customer.name }}</strong>
                                    <small>{{ account.customer.document_number || 'Sin documento' }}</small>
                                </td>
                                <td>
                                    <template v-if="account.next_installment">
                                        <strong>Cuota {{ account.next_installment.number }}</strong>
                                        <small :class="{'text-danger': account.next_installment.status === 'overdue'}">
                                            {{ formatDate(account.next_installment.due_date) }}
                                        </small>
                                    </template>
                                    <span v-else class="br-receivables__muted">Sin saldo pendiente</span>
                                </td>
                                <td class="text-end br-amount-inline__amount">{{ money(account, account.total_amount) }}</td>
                                <td class="text-end br-amount-inline__amount">{{ money(account, account.paid_amount) }}</td>
                                <td class="text-end br-amount-inline__amount br-receivables-table__pending">{{ money(account, account.pending_amount) }}</td>
                                <td>
                                    <div class="br-receivables-progress" :title="`${paymentProgress(account)} % cobrado`">
                                        <span><i :style="{width: `${paymentProgress(account)}%`}"></i></span>
                                        <small>{{ paymentProgress(account) }} %</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="br-receivables-status" :class="`br-receivables-status--${account.status}`">
                                        {{ statusLabel(account.status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="br-icon-action br-icon-action-primary"
                                        data-bs-toggle="tooltip"
                                        title="Ver cronograma"
                                        @click="openDetail(account)">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Paginator :links="accounts.links || []" @clickPage="loadAccounts($event.url)"/>
            </template>
            <WithoutData v-else/>
        </section>
    </section>

    <div class="modal fade" :id="detailModalId" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content br-modal-shell br-receivable-detail">
                <div class="modal-header br-modal-shell__header">
                    <div>
                        <p class="br-receivable-detail__eyebrow">Cuenta por cobrar</p>
                        <h5 class="modal-title">{{ selectedAccount?.document || 'Detalle' }}</h5>
                    </div>
                    <button type="button" class="br-modal-close br-modal-shell__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body br-modal-shell__body">
                    <Loader v-if="loading.detail"/>
                    <template v-else-if="selectedAccount">
                        <div class="br-receivable-detail__identity">
                            <div>
                                <small>Cliente</small>
                                <strong>{{ selectedAccount.customer.name }}</strong>
                                <span>{{ selectedAccount.customer.document_number || 'Sin documento' }}</span>
                            </div>
                            <div>
                                <small>Modalidad</small>
                                <strong>{{ selectedAccount.payment_modality === 'installments' ? 'Crédito en cuotas' : 'Contraentrega' }}</strong>
                                <span>Emitida el {{ formatDate(selectedAccount.issue_date) }}</span>
                            </div>
                            <span class="br-receivables-status" :class="`br-receivables-status--${selectedAccount.status}`">
                                {{ statusLabel(selectedAccount.status) }}
                            </span>
                        </div>

                        <div class="br-receivable-detail__amounts">
                            <div><small>Capital financiado</small><strong class="br-amount-inline__amount">{{ money(selectedAccount, selectedAccount.original_amount) }}</strong></div>
                            <div><small>Recargo ({{ separatorNumber(selectedAccount.extra_percentage) }} %)</small><strong class="br-amount-inline__amount">{{ money(selectedAccount, selectedAccount.extra_amount) }}</strong></div>
                            <div><small>Total por cobrar</small><strong class="br-amount-inline__amount">{{ money(selectedAccount, selectedAccount.total_amount) }}</strong></div>
                            <div class="is-pending"><small>Saldo pendiente</small><strong class="br-amount-inline__amount">{{ money(selectedAccount, selectedAccount.pending_amount) }}</strong></div>
                        </div>

                        <section class="br-receivable-detail__section">
                            <div class="br-receivable-detail__section-title">
                                <h6>Cronograma de cuotas</h6>
                                <span>{{ selectedAccount.installments.length }} cuotas</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table br-entity-table mb-0">
                                    <thead class="br-table-header-surface">
                                        <tr>
                                            <th>Cuota</th>
                                            <th>Vencimiento</th>
                                            <th class="text-end">Importe</th>
                                            <th class="text-end">Cobrado</th>
                                            <th class="text-end">Pendiente</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="installment in selectedAccount.installments" :key="installment.id">
                                            <td><strong>Cuota {{ installment.number }}</strong></td>
                                            <td>{{ formatDate(installment.due_date) }}</td>
                                            <td class="text-end br-amount-inline__amount">{{ money(selectedAccount, installment.amount) }}</td>
                                            <td class="text-end br-amount-inline__amount">{{ money(selectedAccount, installment.paid_amount) }}</td>
                                            <td class="text-end br-amount-inline__amount">{{ money(selectedAccount, installment.pending_amount) }}</td>
                                            <td class="text-center">
                                                <span class="br-receivables-status" :class="`br-receivables-status--${installment.status}`">
                                                    {{ statusLabel(installment.status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section v-if="selectedAccount.payments?.length" class="br-receivable-detail__section">
                            <div class="br-receivable-detail__section-title"><h6>Pagos aplicados</h6></div>
                            <div class="br-receivable-detail__payments">
                                <div v-for="payment in selectedAccount.payments" :key="payment.id">
                                    <span><strong>{{ payment.method }}</strong><small>{{ formatDateTime(payment.paid_at) }}</small></span>
                                    <strong class="br-amount-inline__amount">{{ money(selectedAccount, payment.amount) }}</strong>
                                </div>
                            </div>
                        </section>
                    </template>
                </div>
                <div class="modal-footer br-modal-shell__footer">
                    <button type="button" class="br-btn br-btn-cancel" data-bs-dismiss="modal">Cerrar</button>
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
    name: "AccountsReceivableMain",
    data() {
        return {
            config: Requests.config({entity: "accounts_receivable"}),
            accounts: {data: [], links: []},
            summary: {accounts: 0, overdue_accounts: 0, amounts: []},
            selectedAccount: null,
            detailModalId: Utils.uuid(),
            filters: {search: "", status: "", date_from: "", date_to: ""},
            loading: {list: false, detail: false},
            metrics: [
                {key: "total", label: "Total financiado", description: "Capital más recargos", icon: "fa-solid fa-file-invoice-dollar", tone: "primary"},
                {key: "paid", label: "Cobrado", description: "Pagos aplicados al crédito", icon: "fa-solid fa-circle-check", tone: "success"},
                {key: "pending", label: "Pendiente", description: "Saldo aún por cobrar", icon: "fa-solid fa-hourglass-half", tone: "warning"},
                {key: "overdue", label: "Vencido", description: "Saldo fuera de fecha", icon: "fa-solid fa-triangle-exclamation", tone: "danger"}
            ]
        };
    },
    computed: {
        breadcrumbTitles() {
            return [{title: "Gestión de ventas"}, {title: "Cuentas por cobrar", active: true}];
        },
        hasFilters() {
            return Object.values(this.filters).some(value => String(value || "").trim() !== "");
        }
    },
    mounted() {
        Utils.navbarItem("menu-parent-sales", {addClass: "open"});
        Utils.navbarItem("menu-sales-accounts-receivable", {addClass: "active"});
        this.loadAccounts();
    },
    methods: {
        async loadAccounts(pageUrl = null) {
            this.loading.list = true;
            const result = await Requests.get({
                route: pageUrl || this.config.routes.list,
                data: this.filters,
                showAlert: true
            });
            this.loading.list = false;

            if(!Requests.valid({result})) return;

            this.accounts = result.data.data || {data: [], links: []};
            this.summary = result.data.summary || {accounts: 0, overdue_accounts: 0, amounts: []};
            this.$nextTick(() => Alerts.tooltips({}));
        },
        async openDetail(account) {
            this.selectedAccount = account;
            this.loading.detail = true;
            Alerts.modals({type: "show", id: this.detailModalId});

            const result = await Requests.get({
                route: `${this.config.routes.consult}/${account.id}`,
                showAlert: true
            });
            this.loading.detail = false;

            if(Requests.valid({result})) this.selectedAccount = result.data.data;
        },
        clearFilters() {
            this.filters = {search: "", status: "", date_from: "", date_to: ""};
            this.loadAccounts();
        },
        summaryAmount(key) {
            const amounts = this.summary.amounts || [];

            if(!amounts.length) return "S/ 0.000";

            return amounts.map(amount => `${amount.sign || amount.code} ${this.separatorNumber(amount[key] || 0)}`).join(" · ");
        },
        paymentProgress(account) {
            const total = Number(account.total_amount || 0);

            if(total <= 0) return 0;

            return Math.max(0, Math.min(100, Math.round((Number(account.paid_amount || 0) / total) * 100)));
        },
        money(account, value) {
            return `${account.currency?.sign || account.currency?.code || ""} ${this.separatorNumber(value || 0)}`.trim();
        },
        separatorNumber(value) {
            return Utils.separatorNumber(value);
        },
        formatDate(value) {
            if(!value) return "Sin fecha";

            return new Intl.DateTimeFormat("es-PE").format(new Date(`${String(value).slice(0, 10)}T00:00:00`));
        },
        formatDateTime(value) {
            if(!value) return "Sin fecha";

            return new Intl.DateTimeFormat("es-PE", {dateStyle: "short", timeStyle: "short"}).format(new Date(value));
        },
        statusLabel(status) {
            return {
                pending: "Pendiente",
                partial: "Pago parcial",
                paid: "Pagada",
                overdue: "Vencida",
                canceled: "Anulada"
            }[status] || "Pendiente";
        }
    }
};
</script>
