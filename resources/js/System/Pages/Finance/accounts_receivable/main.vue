<template>
    <FgPageHeader title="Cuentas por cobrar" description="Consulta los saldos y vencimientos de las ventas a crédito.">
        <template #actions>
            <span v-if="!initialLoading && !loading.list" class="fg-help">
                {{ summary.accounts || 0 }} {{ summary.accounts === 1 ? "cuenta registrada" : "cuentas registradas" }}
            </span>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else>
            <section class="fg-receivables">

                <div class="fg-receivables__metrics">
                    <article v-for="metric in metrics" :key="metric.key" class="fg-receivables-metric" :class="`fg-receivables-metric--${metric.tone}`">
                        <div class="fg-receivables-metric__heading">
                            <span class="fg-receivables-metric__icon"><i :class="metric.icon" aria-hidden="true"></i></span>
                            <span>{{ metric.label }}</span>
                        </div>
                        <strong class="fg-money__value">{{ loading.list ? "—" : summaryAmount(metric.key) }}</strong>
                        <span v-if="metric.key === 'overdue'">
                            {{ summary.overdue_accounts || 0 }} {{ summary.overdue_accounts === 1 ? "cuenta vencida" : "cuentas vencidas" }}
                        </span>
                        <span v-else>{{ metric.description }}</span>
                    </article>
                </div>

                <FgResponsiveFilters
                    id="sales-filters-receivables"
                    class="fg-responsive-filters--receivables"
                    label="Filtros de cuentas por cobrar"
                    :loading="loading.list"
                    @search="searchAccounts"
                    @clear="clearFilters">
                    <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                        <SearchInput title="Búsqueda" v-model.trim="filters.search" placeholder="Cliente, documento o número de venta" lg="4"/>
                    </div>
                    <FgField class="col-12 col-sm-6 col-lg-2" label="Estado" input-id="receivable-status">
                        <select id="receivable-status" v-model="filters.status" class="fg-native-select">
                            <option value="">Todos</option>
                            <option value="pending">Pendientes</option>
                            <option value="partial">Pago parcial</option>
                            <option value="overdue">Vencidas</option>
                            <option value="paid">Pagadas</option>
                            <option value="canceled">Anuladas</option>
                        </select>
                    </FgField>
                    <div class="fg-field-cell col-12 col-sm-6 col-lg-2">
                        <InputDate title="Desde" v-model="filters.date_from" lg="2"/>
                    </div>
                    <div class="fg-field-cell col-12 col-sm-6 col-lg-2">
                        <InputDate title="Hasta" v-model="filters.date_to" lg="2"/>
                    </div>
                </FgResponsiveFilters>

                <section class="fg-receivables__panel">
                        <ResponsiveTable fg class="fg-sales-table-scroll">
                            <table role="table" class="fg-receivables-table mb-0 fg-table fg-record-table fg-record-table--receivables">
                                <thead role="rowgroup">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Venta</th>
                                        <th scope="col" role="columnheader">Cliente</th>
                                        <th scope="col" role="columnheader">Próximo vencimiento</th>
                                        <th scope="col" role="columnheader" class="text-end">Total por cobrar</th>
                                        <th scope="col" role="columnheader" class="text-end">Cobrado</th>
                                        <th scope="col" role="columnheader" class="text-end">Saldo</th>
                                        <th scope="col" role="columnheader">Avance</th>
                                        <th scope="col" role="columnheader" class="text-center">Estado</th>
                                        <th scope="col" role="columnheader" class="text-center"><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <tr role="row" v-if="loading.list" class="fg-table__state"><td role="cell" colspan="9"><FgListLoading label="Buscando cuentas por cobrar…"/></td></tr>
                                    <template v-else-if="accounts.data?.length">
                                    <tr role="row" v-for="account in accounts.data" :key="account.id">
                                        <td role="cell" class="fg-record-table__identity">
                                            <strong class="fg-receivables-table__document">{{ account.document }}</strong>
                                            <small>{{ formatDate(account.issue_date) }}<span v-if="account.branch"> · {{ account.branch }}</span></small>
                                        </td>
                                        <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Cliente</span>
                                            <div class="fg-record-table__value">
                                                <strong>{{ account.customer.name }}</strong>
                                                <small>{{ account.customer.document_number || 'Sin documento' }}</small>
                                            </div>
                                        </td>
                                        <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Vencimiento</span>
                                            <div class="fg-record-table__value">
                                                <template v-if="account.next_installment">
                                                    <strong>Cuota {{ account.next_installment.number }}</strong>
                                                    <small :class="{'text-danger': account.next_installment.status === 'overdue'}">
                                                        {{ formatDate(account.next_installment.due_date) }}
                                                    </small>
                                                </template>
                                                <span v-else class="fg-receivables__muted">Sin saldo pendiente</span>
                                            </div>
                                        </td>
                                        <td role="cell" class="text-end fg-money__value">
                                    <span class="fg-record-table__label" aria-hidden="true">Total por cobrar</span>{{ money(account, account.total_amount) }}</td>
                                        <td role="cell" class="text-end fg-money__value">
                                    <span class="fg-record-table__label" aria-hidden="true">Cobrado</span>{{ money(account, account.paid_amount) }}</td>
                                        <td role="cell" class="text-end fg-money__value fg-receivables-table__pending">
                                    <span class="fg-record-table__label" aria-hidden="true">Saldo</span>{{ money(account, account.pending_amount) }}</td>
                                        <td role="cell">
                                    <span class="fg-record-table__label" aria-hidden="true">Avance</span>
                                            <div class="fg-record-table__value">
                                                <div class="fg-receivables-progress" :title="`${paymentProgress(account)} % cobrado`">
                                                    <span><i :style="{width: `${paymentProgress(account)}%`}"></i></span>
                                                    <small>{{ paymentProgress(account) }} %</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td role="cell" class="text-center fg-record-table__state">
                                            <span class="fg-badge" :class="`fg-receivables-status--${account.status}`">
                                                {{ statusLabel(account.status) }}
                                            </span>
                                        </td>
                                        <td role="cell" class="text-center fg-record-table__actions">
                                            <button
                                                type="button"
                                                class="fg-button fg-button--icon"
                                                data-bs-toggle="tooltip"
                                                title="Ver cronograma"
                                                :aria-label="`Ver cronograma de ${account.document}`"
                                                @click="openDetail(account)">
                                                <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    </template>
                                    <tr role="row" v-else class="fg-table__state"><td role="cell" colspan="9"><FgEmptyState title="No encontramos cuentas por cobrar" description="Revisa tus filtros e intenta otra búsqueda."/></td></tr>
                                </tbody>
                            </table>
                        </ResponsiveTable>

                    <Paginator :links="accounts.links || []" :pagination="accounts" :loading="loading.list" @clickPage="loadAccounts($event.url)"/>
                </section>
            </section>

        </div>
    </InitialLoadTransition>
    <div class="modal fade fg-modal fg-modal--receivable" :id="detailModalId" :aria-labelledby="detailModalId + '-title'" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content fg-modal__content fg-receivable-detail">
                <div class="modal-header fg-modal__header">
                    <h2 :id="detailModalId + '-title'" class="modal-title fg-modal__title">{{ selectedAccount?.document || 'Cuenta por cobrar' }}</h2>
                    <button type="button" class="fg-button fg-button--icon" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
                    <FgListLoading label="Cargando el cronograma…" v-if="loading.detail"/>
                    <template v-else-if="selectedAccount">
                        <div class="fg-receivable-detail__identity">
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
                            <span class="fg-badge" :class="`fg-receivables-status--${selectedAccount.status}`">
                                {{ statusLabel(selectedAccount.status) }}
                            </span>
                        </div>

                        <div class="fg-receivable-detail__amounts">
                            <div><small>Capital financiado</small><strong class="fg-money__value">{{ money(selectedAccount, selectedAccount.original_amount) }}</strong></div>
                            <div><small>Recargo ({{ separatorNumber(selectedAccount.extra_percentage) }} %)</small><strong class="fg-money__value">{{ money(selectedAccount, selectedAccount.extra_amount) }}</strong></div>
                            <div><small>Total por cobrar</small><strong class="fg-money__value">{{ money(selectedAccount, selectedAccount.total_amount) }}</strong></div>
                            <div class="is-pending"><small>Saldo pendiente</small><strong class="fg-money__value">{{ money(selectedAccount, selectedAccount.pending_amount) }}</strong></div>
                        </div>

                        <section class="fg-receivable-detail__section">
                            <div class="fg-receivable-detail__section-title">
                                <h3 class="fg-section-title mb-0">Cronograma de cuotas</h3>
                                <span class="fg-help">{{ selectedAccount.installments.length }} {{ selectedAccount.installments.length === 1 ? "cuota" : "cuotas" }}</span>
                            </div>
                            <ResponsiveTable fg class="fg-sales-table-scroll">
                                <table role="table" class="fg-record-table fg-record-table--installments mb-0 fg-table">
                                    <thead role="rowgroup">
                                        <tr role="row">
                                            <th scope="col" role="columnheader">Cuota</th>
                                            <th scope="col" role="columnheader">Vencimiento</th>
                                            <th scope="col" role="columnheader" class="text-end">Importe</th>
                                            <th scope="col" role="columnheader" class="text-end">Cobrado</th>
                                            <th scope="col" role="columnheader" class="text-end">Pendiente</th>
                                            <th scope="col" role="columnheader" class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody role="rowgroup">
                                        <tr role="row" v-for="installment in selectedAccount.installments" :key="installment.id">
                                            <td role="cell" class="fg-record-table__identity"><strong>Cuota {{ installment.number }}</strong></td>
                                            <td role="cell"><span class="fg-record-table__label" aria-hidden="true">Vencimiento</span><div class="fg-record-table__value">{{ formatDate(installment.due_date) }}</div></td>
                                            <td role="cell" class="text-end fg-money__value"><span class="fg-record-table__label" aria-hidden="true">Importe</span><div class="fg-record-table__value">{{ money(selectedAccount, installment.amount) }}</div></td>
                                            <td role="cell" class="text-end fg-money__value"><span class="fg-record-table__label" aria-hidden="true">Cobrado</span><div class="fg-record-table__value">{{ money(selectedAccount, installment.paid_amount) }}</div></td>
                                            <td role="cell" class="text-end fg-money__value"><span class="fg-record-table__label" aria-hidden="true">Pendiente</span><div class="fg-record-table__value">{{ money(selectedAccount, installment.pending_amount) }}</div></td>
                                            <td role="cell" class="text-center fg-record-table__state">
                                                <span class="fg-badge" :class="`fg-receivables-status--${installment.status}`">
                                                    {{ statusLabel(installment.status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </ResponsiveTable>
                        </section>

                        <section v-if="selectedAccount.payments?.length" class="fg-receivable-detail__section">
                            <div class="fg-receivable-detail__section-title"><h3 class="fg-section-title mb-0">Pagos aplicados</h3></div>
                            <div class="fg-receivable-detail__payments">
                                <div v-for="payment in selectedAccount.payments" :key="payment.id">
                                    <span><strong>{{ payment.method }}</strong><small>{{ formatDateTime(payment.paid_at) }}</small></span>
                                    <strong class="fg-money__value">{{ money(selectedAccount, payment.amount) }}</strong>
                                </div>
                            </div>
                        </section>
                    </template>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    mixins: [InitialPageMixin],
    components: {SearchInput},
    name: "AccountsReceivableMain",
    data() {
        return {
            config: Requests.config({entity: "accounts_receivable"}),
            accounts: {data: [], links: []},
            summary: {accounts: 0, overdue_accounts: 0, amounts: []},
            selectedAccount: null,
            detailModalId: Utils.uuid(),
            filters: {search: "", status: "", date_from: "", date_to: ""},
            appliedFilters: null,
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
    async mounted() {
        navbarItem("menu-parent-sales", {addClass: "open"});
        navbarItem("menu-sales-accounts-receivable", {addClass: "active"});
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
            this.loading.list = true;

            try {
                this.accounts = Requests.emptyPagination();
                this.summary = {accounts: 0, overdue_accounts: 0, amounts: []};
                const result = await Requests.get({
                    route: pageUrl || this.config.routes.list,
                    data: this.appliedFilters,
                    showAlert: true
                });

                if(!Requests.valid({result})) return;

                this.accounts = result.data.data || {data: [], links: []};
                this.summary = result.data.summary || {accounts: 0, overdue_accounts: 0, amounts: []};
                this.$nextTick(() => Alerts.tooltips({}));
            } finally {
                this.loading.list = false;
            }
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
            return Alerts.showFiltersCleared();
        },
        summaryAmount(key) {
            return Utils.formatCurrencyTotals(this.summary.amounts, key);
        },
        paymentProgress(account) {
            const total = account.total_amount;

            if(Utils.compareNumbers(total, 0) <= 0) return 0;

            return Math.max(0, Math.min(100, Utils.proportion(account.paid_amount, 100, total, 0)));
        },
        money(account, value) {
            return Utils.formatCurrencyAmount(value, account.currency);
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
