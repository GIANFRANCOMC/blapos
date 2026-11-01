<template>
    <main class="br-account-page" aria-labelledby="account-title">
        <div class="br-account-page__breadcrumb">
            <Breadcrumb :list="[{title: 'Inicio'}, {title: 'Mi cuenta', active: true}]"/>
        </div>

        <AccountSkeleton v-if="loading && skeletonEnabled"/>
        <div v-else-if="loading" class="br-account-page__loading" aria-hidden="true"></div>
        <div v-else-if="loadError" class="br-account-page__load-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ loadError }}</span>
            <button type="button" class="br-btn br-btn-outline-secondary" @click="loadAccount">Reintentar</button>
        </div>

        <div v-else class="br-account-page__layout">
            <div class="br-account-page__main">
                <header class="br-account-page__hero">
                    <div class="br-account-page__intro">
                        <h1 id="account-title">Mi cuenta</h1>
                        <p>Actualiza tus datos personales y la información de tu cuenta.</p>
                    </div>
                </header>

                <form class="br-account-page__panel" novalidate @submit.prevent="saveAccount">
                    <div class="br-account-page__panel-head">
                        <div>
                            <h2>Datos personales</h2>
                            <p>Actualiza tu información de contacto y otros datos personales.</p>
                        </div>
                    </div>

                    <div class="br-account-page__fields">
                        <div class="br-account-page__field">
                            <InputBase v-model="form.email" input-type="email" title="Correo electrónico" :is-required="true" :maxlength="100" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.email || []">
                                <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span></template>
                            </InputBase>
                        </div>
                        <div class="br-account-page__field">
                            <InputText v-model="form.phone_number" title="Celular" :maxlength="15" placeholder="Sin información" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.phone_number || []">
                                <template #inputGroupPrepend><span class="input-group-text"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i></span></template>
                            </InputText>
                        </div>
                        <div class="br-account-page__field">
                            <InputSelect v-model="form.gender" title="Género" :options="genderOptions" default-option-label="Sin especificar" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.gender || []">
                                <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-user" aria-hidden="true"></i></span></template>
                            </InputSelect>
                        </div>
                        <div class="br-account-page__field">
                            <InputDate v-model="form.birthdate" title="Fecha de nacimiento" :max="today" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.birthdate || []">
                                <template #inputGroupPrepend><span class="input-group-text"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span></template>
                            </InputDate>
                        </div>
                    </div>

                    <footer class="br-account-page__actions">
                        <button type="submit" class="br-btn br-btn-primary" :disabled="saving || confirming">
                            <span>{{ saving ? 'Guardando...' : 'Guardar cambios' }}</span>
                        </button>
                    </footer>
                </form>
            </div>

            <aside class="br-account-page__aside" aria-label="Identidad de la empresa y del usuario">
                <AccountCompanyCard :company="company"/>
                <AccountUserCard :account="account"/>
            </aside>
        </div>
    </main>
</template>

<script>
import * as Requests from "@System/Helpers/Requests.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import {createInitialLoad} from "@System/Helpers/InitialLoad.js";
import AccountSkeleton from "./AccountSkeleton.vue";
import AccountCompanyCard from "./AccountCompanyCard.vue";
import AccountUserCard from "./AccountUserCard.vue";

const emptyForm = () => ({email: "", phone_number: "", gender: "", birthdate: ""});
const initialLoad = createInitialLoad({skeleton: true});

export default {
    components: {AccountSkeleton, AccountCompanyCard, AccountUserCard},
    data() {
        return {
            account: null,
            company: {},
            form: emptyForm(),
            errors: {},
            skeletonEnabled: initialLoad.skeleton,
            loading: true,
            saving: false,
            confirming: false,
            loadError: "",
            genderOptions: [
                {code: "male", label: "Masculino"},
                {code: "female", label: "Femenino"},
                {code: "other", label: "Otro"}
            ]
        };
    },
    computed: {
        today() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");
            const day = String(now.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        }
    },
    mounted() {
        this.loadAccount();
    },
    methods: {
        async loadAccount() {
            this.loading = true;
            this.loadError = "";
            initialLoad.start();

            try {
                const result = await Requests.get({route: window.location.pathname});

                if(!result.bool || !result.data?.account) {
                    this.loadError = result.data?.msg || "No fue posible cargar los datos de tu cuenta.";
                    return;
                }

                this.account = result.data.account;
                this.company = result.data.company || {};
                this.fillForm(this.account);
            } catch(error) {
                this.loadError = "No fue posible cargar los datos de tu cuenta.";
            } finally {
                initialLoad.finish();
                this.loading = false;
            }
        },
        fillForm(account) {
            this.form = {
                email: account.email || "",
                phone_number: account.phone_number || "",
                gender: account.gender || "",
                birthdate: account.birthdate ? String(account.birthdate).slice(0, 10) : ""
            };
        },
        async saveAccount() {
            if(this.saving || this.confirming) return;

            this.confirming = true;
            let confirmed = false;
            try {
                confirmed = await Alerts.confirmAction({
                    title: "¿Guardar cambios de cuenta?",
                    text: "Se guardarán los cambios de correo, celular, género y fecha de nacimiento. La identificación y el nombre permanecerán sin cambios.",
                    confirmButtonText: "Guardar cambios"
                });
            } finally {
                this.confirming = false;
            }
            if(!confirmed) return;

            this.saving = true;
            this.errors = {};
            const result = await Requests.patch({route: window.location.pathname, data: this.form});
            this.saving = false;

            if(!result.bool) {
                this.errors = result.errors || {};
                if(result.code !== 422) Alerts.toastrs({type: "error", subtitle: result.data?.msg || "No se pudo guardar la cuenta."});
                return;
            }

            this.account = result.data.account;
            this.fillForm(this.account);
            await Alerts.showMessage({
                type: "success",
                title: result.data.msg || "Cambios guardados"
            });
        }
    }
};
</script>
