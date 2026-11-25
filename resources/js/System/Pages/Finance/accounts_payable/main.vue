<template>
    <FgPageHeader title="Cuentas por pagar" description="Consulta los saldos y vencimientos de las compras a crédito.">
        <template #actions>
            <span v-if="!initialLoading && !loading.list && !listError" class="fg-help">
                {{ summary.accounts || 0 }} {{ summary.accounts === 1 ? "cuenta registrada" : "cuentas registradas" }}
            </span>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <section class="fg-receivables">
                <div class="fg-receivables__metrics">
                    <article v-for="metric in metrics" :key="metric.key" class="fg-receivables-metric" :class="`fg-receivables-metric--${metric.tone}`">
                        <span class="fg-receivables-metric__icon"><i :class="metric.icon" aria-hidden="true"></i></span>
                        <div>
                            <small>{{ metric.label }}</small>
                            <strong class="fg-money__value">{{ loading.list || listError ? "—" : summaryAmount(metric.key) }}</strong>
                            <span v-if="metric.key === 'overdue' && !loading.list && !listError">{{ summary.overdue_accounts || 0 }} cuentas vencidas</span>
                            <span v-else>{{ metric.description }}</span>
                        </div>
                    </article>
                </div>

                <FgResponsiveFilters
                    id="purchase-payable-filters"
                    class="fg-responsive-filters--receivables"
                    label="Filtros de cuentas por pagar"
                    :loading="loading.list"
                    @search="searchAccounts"
                    @clear="clearFilters">
                    <div class="fg-field-cell col-12 col-sm-6 col-lg-4">
                        <SearchInput title="Búsqueda" v-model.trim="filters.search" placeholder="Proveedor o documento de compra"/>
                    </div>
                    <FgField class="col-12 col-sm-6 col-lg-2" label="Estado" input-id="payable-status">
                        <select id="payable-status" v-model="filters.status" class="fg-native-select">
                            <option value="">Todos</option>
                            <option value="pending">Pendientes</option>
                            <option value="partial">Pago parcial</option>
                            <option value="overdue">Vencidas</option>
                            <option value="paid">Pagadas</option>
                            <option value="canceled">Anuladas</option>
                        </select>
                    </FgField>
                    <FgField class="col-12 col-sm-6 col-lg-2" label="Desde" input-id="payable-from">
                        <input id="payable-from" v-model="filters.date_from" type="date" class="fg-input">
                    </FgField>
                    <FgField class="col-12 col-sm-6 col-lg-2" label="Hasta" input-id="payable-to">
                        <input id="payable-to" v-model="filters.date_to" type="date" class="fg-input">
                    </FgField>
                </FgResponsiveFilters>

                <section class="fg-receivables__panel">
                    <FgListLoading v-if="loading.list" label="Buscando cuentas por pagar…"/>
                    <FgEmptyState v-else-if="listError" title="No se pudieron consultar las cuentas" description="Vuelve a intentar con los filtros aplicados.">
                        <button type="button" class="fg-button fg-button--secondary" @click="loadAccounts()">Reintentar</button>
                    </FgEmptyState>
                    <template v-else-if="accounts.data?.length">
                        <ResponsiveTable class="fg-sales-table-scroll">
                            <table role="table" class="fg-table fg-record-table fg-receivables-table mb-0">
                                <thead role="rowgroup" class="fg-table-header-surface">
                                    <tr role="row">
                                        <th scope="col" role="columnheader">Compra</th>
                                        <th scope="col" role="columnheader">Proveedor</th>
                                        <th scope="col" role="columnheader">Vencimiento</th>
                                        <th scope="col" role="columnheader" class="text-end">Total</th>
                                        <th scope="col" role="columnheader" class="text-end">Pagado</th>
                                        <th scope="col" role="columnheader" class="text-end">Saldo</th>
                                        <th scope="col" role="columnheader">Estado</th>
                                        <th scope="col" role="columnheader" class="text-center"><span class="visually-hidden">Acciones</span></th>
                                    </tr>
                                </thead>
                                <tbody role="rowgroup">
                                    <tr role="row" v-for="account in accounts.data" :key="account.id">
                                        <td role="cell" class="fg-record-table__identity">
                                            <strong class="fg-receivables-table__document">{{ account.document }}</strong>
                                            <small>{{ account.branch }} · {{ account.warehouse }}</small>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Proveedor</span>
                                            <div class="fg-record-table__value"><strong>{{ account.supplier.name }}</strong><small>{{ account.supplier.document_number }}</small></div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Vencimiento</span>
                                            <div class="fg-record-table__value">{{ formatDate(account.due_date) }}</div>
                                        </td>
                                        <td role="cell" class="text-end fg-amount-inline__amount">
                                            <span class="fg-record-table__label" aria-hidden="true">Total</span>
                                            <div class="fg-record-table__value">{{ money(account, account.total_amount) }}</div>
                                        </td>
                                        <td role="cell" class="text-end fg-amount-inline__amount">
                                            <span class="fg-record-table__label" aria-hidden="true">Pagado</span>
                                            <div class="fg-record-table__value">{{ money(account, account.paid_amount) }}</div>
                                        </td>
                                        <td role="cell" class="text-end fg-amount-inline__amount fg-receivables-table__pending">
                                            <span class="fg-record-table__label" aria-hidden="true">Saldo</span>
                                            <div class="fg-record-table__value">{{ money(account, account.pending_amount) }}</div>
                                        </td>
                                        <td role="cell" class="fg-record-table__state">
                                            <span class="fg-badge" :class="`fg-receivables-status--${account.status}`">{{ statusLabel(account.status) }}</span>
                                        </td>
                                        <td role="cell" class="text-center fg-record-table__actions">
                                            <button type="button" class="fg-icon-action fg-icon-action-edit" title="Ver cronograma" :aria-label="'Ver cronograma de ' + account.document" :aria-expanded="selectedAccount?.id === account.id" @click="openDetail(account, $event)">
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </ResponsiveTable>
                    </template>
                    <FgEmptyState v-else :title="hasAppliedFilters ? 'No encontramos cuentas por pagar' : 'Aún no hay cuentas por pagar'" :description="hasAppliedFilters ? 'Revisa los filtros e intenta otra búsqueda.' : 'Las compras a crédito aparecerán aquí.'"/>
                    <Paginator v-if="!listError" :links="accounts.links" :pagination="accounts" :loading="loading.list" @clickPage="loadAccounts"/>
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
                    <FgListLoading v-if="loading.detail" label="Cargando el cronograma…"/>
                    <FgEmptyState v-else-if="detailError" title="No se pudo cargar el cronograma" description="Vuelve a intentar para consultar las cuotas de esta compra.">
                        <button type="button" class="fg-button fg-button--secondary" @click="openDetail(selectedAccount)">Reintentar</button>
                    </FgEmptyState>
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
import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgResponsiveFilters from "@System/Components/Figma/FgResponsiveFilters.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import SearchInput from "@System/Components/Generics/SearchInput.vue";

export default {
    mixins: [InitialPageMixin],
    components: {FgPageHeader, FgEmptyState, FgListLoading, FgResponsiveFilters, FgField, ResponsiveTable, SearchInput},
    name: "AccountsPayableMain",
    data() {
        return {
            config: Requests.config({entity: "accounts_payable"}),
            accounts: {data: [], links: []},
            summary: {accounts: 0, overdue_accounts: 0, amounts: []},
            selectedAccount: null,
            detailRequest: 0,
            detailTrigger: null,
            detailError: false,
            listError: false,
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
        hasAppliedFilters() {
            return Object.values(this.appliedFilters ?? {}).some(value => String(value || "").trim() !== "");
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
            this.listError = false;

            try {
                this.accounts = Requests.emptyPagination();
                this.summary = {accounts: 0, overdue_accounts: 0, amounts: []};
                const result = await Requests.get({route: pageUrl || this.config.routes.list, data: this.appliedFilters});

                this.listError = !Requests.valid({result});
                if(this.listError) return;

                this.accounts = result.data.data || {data: [], links: []};
                this.summary = result.data.summary || {accounts: 0, overdue_accounts: 0, amounts: []};
            } finally {
                this.loading.list = false;
            }
        },
        async openDetail(account, event) {
            const request = ++this.detailRequest;
            if(event) this.detailTrigger = event.currentTarget;
            this.detailError = false;
            this.selectedAccount = account;
            this.loading.detail = true;
            await this.$nextTick();
            this.$refs.detailHeading?.focus();

            const result = await Requests.get({route: `${this.config.routes.consult}/${account.id}`});
            if(request !== this.detailRequest) return;
            this.loading.detail = false;

            this.detailError = !Requests.valid({result});
            if(!this.detailError) this.selectedAccount = result.data.data;
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
