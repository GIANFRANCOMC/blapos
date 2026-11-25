<template>
    <FgPageHeader title="Proveedores" description="Consulta los datos de contacto y las condiciones de tus proveedores.">
        <template #actions>
            <button type="button" class="fg-button" :disabled="initialLoading" data-bs-toggle="modal" data-bs-target="#supplierModal" @click="prepareSupplier">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Agregar proveedor</span>
            </button>
        </template>
    </FgPageHeader>

    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading" table/>
        <div v-else class="fg-page-content">
            <main class="fg-entity">
                <FgResponsiveFilters
                    id="purchases-supplier-filters"
                    label="Filtros de proveedores"
                    :loading="loading"
                    @search="listSuppliers({applyFilters: true})"
                    @clear="clearFilters">
                    <div class="fg-field-cell col-12 col-lg-8">
                        <SearchInput v-model.trim="word" title="Búsqueda" placeholder="Nombre, documento o contacto"/>
                    </div>
                </FgResponsiveFilters>

                <section class="fg-entity-list">
                    <ResponsiveTable class="fg-sales-table-scroll">
                        <table role="table" class="fg-table fg-record-table mb-0">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 17%;">
                                <col style="width: 11%;">
                                <col style="width: 16%;">
                                <col style="width: 12%;">
                                <col style="width: 12%;">
                                <col style="width: 7%;">
                                <col style="width: 5%;">
                            </colgroup>
                            <thead role="rowgroup" class="fg-table-header-surface">
                                <tr role="row">
                                    <th scope="col" role="columnheader">Identificación</th>
                                    <th scope="col" role="columnheader">Proveedor</th>
                                    <th scope="col" role="columnheader">Contacto</th>
                                    <th scope="col" role="columnheader">Comunicación</th>
                                    <th scope="col" role="columnheader">Condiciones</th>
                                    <th scope="col" role="columnheader">Desempeño</th>
                                    <th scope="col" role="columnheader">Estado</th>
                                    <th scope="col" role="columnheader" class="text-center"><span class="visually-hidden">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody role="rowgroup">
                                <tr role="row" class="fg-table__state" v-if="loading"><td role="cell" colspan="8" class="py-4"><FgListLoading label="Buscando proveedores…"/></td></tr>
                                <template v-else-if="records.data?.length">
                                    <tr role="row" v-for="supplier in records.data" :key="supplier.id">
                                        <td role="cell" class="fg-entity-table__identification-cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Documento</span>
                                            <div class="fg-entity-identifiers fg-entity-identifiers--plain">
                                                <div class="fg-entity-identifier">
                                                    <span class="fg-entity-identifier__label colon-at-end" v-text="supplier.document_type || 'Documento'"></span>
                                                    <span class="fg-entity-identifier__value">
                                                        <span class="fg-entity-code" v-text="supplier.document_number || '—'"></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td role="cell" class="fg-record-table__identity">
                                            <strong>{{ supplier.name }}</strong>
                                            <span class="fg-purchases__meta">{{ supplier.address }}</span>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Contacto</span>
                                            <div class="fg-record-table__value">{{ supplier.contact_name || "Sin contacto" }}</div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Comunicación</span>
                                            <div class="fg-record-table__value">
                                                <span>{{ supplier.telephone }}</span>
                                                <span class="fg-purchases__meta">{{ supplier.email }}</span>
                                            </div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Condiciones</span>
                                            <div class="fg-record-table__value">
                                                <strong>{{ supplier.payment_term_days || 0 }} días</strong>
                                                <span class="fg-purchases__meta">
                                                Crédito: S/ {{ separatorNumber(supplier.credit_limit || 0) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td role="cell">
                                            <span class="fg-record-table__label" aria-hidden="true">Desempeño</span>
                                            <div class="fg-record-table__value">
                                                <strong>{{ supplier.purchases_count || 0 }} compra{{ Number(supplier.purchases_count || 0) === 1 ? "" : "s" }}</strong>
                                                <span class="fg-purchases__meta">
                                                Total: {{ formatCurrencyTotals(supplier.purchase_amounts) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td role="cell" class="fg-record-table__state">
                                            <span :class="['fg-badge', `fg-status-${supplier.status}`]">
                                                {{ supplier.status === "active" ? "Activo" : "Inactivo" }}
                                            </span>
                                        </td>
                                        <td role="cell" class="text-center fg-record-table__actions">
                                            <button
                                                type="button"
                                                class="fg-icon-action fg-icon-action-edit"
                                                data-bs-toggle="modal"
                                                data-bs-target="#supplierModal"
                                                title="Editar proveedor"
                                                :aria-label="`Editar proveedor ${supplier.name}`"
                                                @click="prepareSupplier(supplier)">
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr role="row" v-else-if="listError" class="fg-table__state">
                                    <td role="cell" colspan="8">
                                        <FgEmptyState title="No se pudieron consultar los proveedores" description="Vuelve a intentar con los filtros aplicados.">
                                            <button type="button" class="fg-button fg-button--secondary" @click="listSuppliers()">Reintentar</button>
                                        </FgEmptyState>
                                    </td>
                                </tr>
                                <tr role="row" v-else class="fg-table__state">
                                    <td role="cell" colspan="8">
                                        <FgEmptyState :title="appliedWord ? 'No encontramos proveedores' : 'Aún no hay proveedores'" :description="appliedWord ? 'Prueba otro nombre, documento o contacto.' : 'Agrega un proveedor para usarlo en tus compras.'"/>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </ResponsiveTable>
                </section>

                <Paginator v-if="!listError" :links="records.links" :pagination="records" :loading="loading" @clickPage="listSuppliers"/>
            </main>

        </div>
    </InitialLoadTransition>
    <div id="supplierModal" class="modal fade fg-modal" aria-labelledby="supplier-modal-title" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header fg-modal__header">
                    <h2 id="supplier-modal-title" class="modal-title fg-modal__title">
                        {{ editingId ? "Editar proveedor" : "Agregar proveedor" }}
                    </h2>
                    <button type="button" class="fg-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body fg-modal__body">
                    <div class="row g-3">
                        <InputText v-model="form.name" hasDiv title="Razón social o nombre" :titleClass="[config.forms.classes.title]" isRequired xl="6" lg="6"/>
                        <InputText v-model="form.contact_name" hasDiv title="Persona de contacto" :titleClass="[config.forms.classes.title]" xl="6" lg="6"/>
                        <InputText v-model="form.document_type" hasDiv title="Tipo de documento" :titleClass="[config.forms.classes.title]" placeholder="RUC, DNI u otro" xl="4" lg="4"/>
                        <InputText v-model="form.document_number" hasDiv title="Número de documento" :titleClass="[config.forms.classes.title]" xl="4" lg="4"/>
                        <InputText v-model="form.telephone" hasDiv title="Teléfono" :titleClass="[config.forms.classes.title]" xl="4" lg="4"/>
                        <InputText v-model="form.email" hasDiv title="Correo" :titleClass="[config.forms.classes.title]" xl="6" lg="6"/>
                        <InputText v-model="form.address" hasDiv title="Dirección" :titleClass="[config.forms.classes.title]" xl="6" lg="6"/>
                        <InputNumber v-model="form.payment_term_days" title="Plazo de pago" :titleClass="[config.forms.classes.title]" :decimals="0" :hasNegative="false" xl="3" lg="3">
                            <template #inputGroupAppend>
                                <span class="input-group-text fg-internal-code-prefix">días</span>
                            </template>
                        </InputNumber>
                        <InputNumber v-model="form.credit_limit" title="Límite de crédito" :titleClass="[config.forms.classes.title]" :hasNegative="false" xl="3" lg="3">
                            <template #inputGroupPrepend>
                                <span class="input-group-text fg-currency-prefix">S/</span>
                            </template>
                        </InputNumber>
                        <FgField class="col-lg-6" label="Estado">
                            <FgSelect
                                v-model="form.statusOption"
                                :options="statusOptions"
                                :clearable="false"
                                :searchable="false"/>
                        </FgField>
                    </div>
                    <div class="fg-supplier-related">
                        <div class="fg-supplier-related__header">
                            <div>
                                <strong>Contactos</strong>
                                <small>Personas operativas para compras, entregas o pagos.</small>
                            </div>
                            <button type="button" class="fg-button fg-button--small fg-button--secondary" @click="addContact">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Agregar contacto</span>
                            </button>
                        </div>
                        <div v-for="(contact, index) in form.contacts" :key="contact.key" class="fg-supplier-related__row">
                            <InputText v-model="contact.name" hasDiv title="Nombre" :titleClass="[config.forms.classes.title]" xl="3" lg="3"/>
                            <InputText v-model="contact.position" hasDiv title="Cargo" :titleClass="[config.forms.classes.title]" xl="3" lg="3"/>
                            <InputText v-model="contact.telephone" hasDiv title="Teléfono" :titleClass="[config.forms.classes.title]" xl="2" lg="2"/>
                            <InputText v-model="contact.email" hasDiv title="Correo" :titleClass="[config.forms.classes.title]" xl="3" lg="3"/>
                            <button type="button" class="fg-icon-action fg-icon-action-danger" :disabled="form.contacts.length === 1" @click="removeContact(index)">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="fg-supplier-related">
                        <div class="fg-supplier-related__header">
                            <div>
                                <strong>Cuentas bancarias</strong>
                                <small>Datos de pago para transferencias y conciliación.</small>
                            </div>
                            <button type="button" class="fg-button fg-button--small fg-button--secondary" @click="addBankAccount">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Agregar cuenta</span>
                            </button>
                        </div>
                        <div v-for="(account, index) in form.bank_accounts" :key="account.key" class="fg-supplier-related__row">
                            <InputText v-model="account.bank_name" hasDiv title="Banco" :titleClass="[config.forms.classes.title]" xl="3" lg="3"/>
                            <InputText v-model="account.currency_code" hasDiv title="Moneda" :titleClass="[config.forms.classes.title]" xl="2" lg="2"/>
                            <InputText v-model="account.account_number" hasDiv title="Cuenta" :titleClass="[config.forms.classes.title]" xl="3" lg="3"/>
                            <InputText v-model="account.interbank_code" hasDiv title="CCI" :titleClass="[config.forms.classes.title]" xl="3" lg="3"/>
                            <button type="button" class="fg-icon-action fg-icon-action-danger" :disabled="form.bank_accounts.length === 1" @click="removeBankAccount(index)">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer fg-modal__footer">
                    <button ref="closeModal" type="button" class="fg-button fg-button--secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button
                        type="button"
                        :class="['fg-button', editingId && 'fg-button--edit']"
                        :disabled="saving"
                        @click="saveSupplier">
                        {{ editingId ? "Actualizar proveedor" : "Agregar proveedor" }}
                    </button>
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
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import {navbarItem} from "@System/Layout/Menu.js";
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import * as Constants from "@System/Helpers/Constants.js";
import * as Requests from "@System/Helpers/Requests.js";
import * as Utils from "@System/Helpers/Utils.js";
import SearchInput from "@System/Components/Generics/SearchInput.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";

export default {
    mixins: [InitialPageMixin],
    components: {FgPageHeader, FgResponsiveFilters, FgListLoading, FgEmptyState, ResponsiveTable, SearchInput, FgField, FgSelect},
    async mounted() {
        navbarItem("menu-parent-purchases", {addClass: "open"});
        navbarItem("menu-purchases-suppliers", {});
        await this.loadInitialParams(() => this.listSuppliers({}));
    },
    data() {
        return {
            word: "",
            appliedWord: "",
            loading: false,
            listError: false,
            saving: false,
            editingId: null,
            records: {total: 0, data: []},
            form: {},
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "suppliers"}),
                    page: {title: "Proveedores", active: true, menu: {id: "menu-purchases-suppliers"}}
                }
            }
        };
    },
    methods: {
        clearFilters() {
            this.word = "";
            return Alerts.showFiltersCleared();
        },
        async listSuppliers({url = null, applyFilters = false} = {}) {
            if(this.loading) return;
            this.listError = false;
            if(applyFilters) this.appliedWord = this.word;
            this.loading = true;

            try {
                this.records = Requests.emptyPagination();
                const result = await Requests.get({
                    route: url || this.config.entity.routes.list,
                    data: {word: this.appliedWord}
                });
                this.listError = !result.bool;
                if(!this.listError) this.records = result.data;
            } finally {
                this.loading = false;
            }
        },
        newContact(contact = {}) {
            return {
                key: `${Date.now()}-${Math.random()}`,
                name: contact.name || "",
                position: contact.position || "",
                telephone: contact.telephone || "",
                email: contact.email || "",
                is_primary: contact.is_primary || false
            };
        },
        newBankAccount(account = {}) {
            return {
                key: `${Date.now()}-${Math.random()}`,
                bank_name: account.bank_name || "",
                currency_code: account.currency_code || "PEN",
                account_number: account.account_number || "",
                interbank_code: account.interbank_code || "",
                is_primary: account.is_primary || false
            };
        },
        addContact() {
            this.form.contacts.push(this.newContact());
        },
        removeContact(index) {
            if(this.form.contacts.length > 1) this.form.contacts.splice(index, 1);
        },
        addBankAccount() {
            this.form.bank_accounts.push(this.newBankAccount());
        },
        removeBankAccount(index) {
            if(this.form.bank_accounts.length > 1) this.form.bank_accounts.splice(index, 1);
        },
        prepareSupplier(supplier = null) {
            this.editingId = supplier?.id || null;
            this.form = {
                name: supplier?.name || "",
                contact_name: supplier?.contact_name || "",
                document_type: supplier?.document_type || "RUC",
                document_number: supplier?.document_number || "",
                telephone: supplier?.telephone || "",
                email: supplier?.email || "",
                address: supplier?.address || "",
                payment_term_days: supplier?.payment_term_days ?? 0,
                credit_limit: supplier?.credit_limit ?? "",
                contacts: (supplier?.contacts?.length ? supplier.contacts : [{}]).map(contact => this.newContact(contact)),
                bank_accounts: (supplier?.bank_accounts?.length ? supplier.bank_accounts : supplier?.bankAccounts?.length ? supplier.bankAccounts : [{}]).map(account => this.newBankAccount(account)),
                statusOption: this.statusOptions.find(option => option.code === supplier?.status)
                    || this.statusOptions[0]
            };
        },
        async saveSupplier() {
            this.saving = true;
            Alerts.swals({
                type: "loading",
                message: this.editingId ? "Actualizando proveedor" : "Agregando proveedor"
            });
            const data = {
                ...this.form,
                contacts: this.form.contacts
                    .filter(contact => contact.name || contact.telephone || contact.email)
                    .map((contact, index) => ({...contact, is_primary: index === 0})),
                bank_accounts: this.form.bank_accounts
                    .filter(account => account.bank_name || account.account_number || account.interbank_code)
                    .map((account, index) => ({...account, is_primary: index === 0})),
                status: this.form.statusOption?.code
            };
            delete data.statusOption;
            const result = this.editingId
                ? await Requests.patch({
                    route: this.config.entity.routes.update,
                    id: this.editingId,
                    data
                })
                : await Requests.post({
                    route: this.config.entity.routes.store,
                    data
                });
            this.saving = false;
            Alerts.swals({show: false});
            if(Requests.valid({result})) {
                this.$refs.closeModal?.click();
                Alerts.generateAlert({type: "success", msgContent: result.data.msg});
                await this.listSuppliers({});
                return;
            }
            const errors = Object.values(result?.errors || {}).flat();
            Alerts.generateAlert({
                type: "error",
                messages: errors.length ? errors : [result?.data?.msg || "No se pudo agregar el proveedor."]
            });
        }
    },
    computed: {
        statusOptions() {
            return [
                {code: "active", label: "Activo"},
                {code: "inactive", label: "Inactivo"}
            ];
        },
        formatCurrencyTotals() {
            return Utils.formatCurrencyTotals;
        },
        separatorNumber() {
            return Utils.separatorNumber;
        }
    }
};
</script>
