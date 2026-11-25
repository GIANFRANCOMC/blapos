<template>
    <main class="fg-account-page" aria-labelledby="account-title">
        <div class="fg-account-page__breadcrumb">
            <Breadcrumb :list="[{title: 'Configuración'}, {title: 'Preferencias'}, {title: 'Mi cuenta', active: true}]"/>
        </div>

        <header class="fg-page-header fg-account-page__hero">
            <div>
                <h1 id="account-title" class="fg-title">Mi cuenta</h1>
                <p class="fg-help">Actualiza tus datos de contacto y consulta tu perfil de acceso.</p>
            </div>
            <div v-if="!loadError" class="fg-page-header__actions">
                <FgButton type="submit" form="fg-account-data-form" :disabled="loading || confirming" :loading="saving">
                    {{ saving ? 'Guardando cambios…' : 'Guardar cambios' }}
                </FgButton>
            </div>
        </header>

        <InitialLoadTransition>
            <AccountSkeleton v-if="loading"/>
            <div v-else-if="loadError" class="fg-account-page__load-error" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span>{{ loadError }}</span>
                <button type="button" class="fg-button fg-button--secondary" @click="loadAccount">Reintentar</button>
            </div>

            <div v-else class="fg-account-page__layout">
                <form id="fg-account-data-form" class="fg-account-page__panel" aria-labelledby="account-personal-title" novalidate @submit.prevent="saveAccount">
                    <h2 id="account-personal-title" class="fg-section-title">Datos personales</h2>

                    <div class="fg-account-page__fields">
                        <div class="fg-account-page__field">
                            <InputBase v-model="form.email" input-type="email" title="Correo electrónico" :is-required="true" :maxlength="100" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.email || []"/>
                        </div>
                        <div class="fg-account-page__field">
                            <InputText v-model="form.phone_number" title="Celular" :maxlength="15" placeholder="Sin información" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.phone_number || []"/>
                        </div>
                        <div class="fg-account-page__field">
                            <InputSelect v-model="form.gender" title="Género" :options="genderOptions" default-option-label="Sin especificar" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.gender || []"/>
                        </div>
                        <div class="fg-account-page__field">
                            <InputDate v-model="form.birthdate" title="Fecha de nacimiento" :max="today" :disabled="saving" :has-text-bottom="true" :text-bottom-info="errors.birthdate || []"/>
                        </div>
                    </div>

                    <div class="fg-account-page__scope">
                        <p class="fg-section-title">Alcance de la edición</p>
                        <p>El nombre, documento y perfil de acceso se gestionan desde Colaboradores.</p>
                    </div>
                </form>

                <aside class="fg-account-page__aside" aria-label="Identidad de la empresa y del usuario">
                    <AccountUserCard :account="account"/>
                    <AccountCompanyCard :company="company"/>
                </aside>
            </div>
        </InitialLoadTransition>
    </main>
</template>

<script>
import FgButton from "@System/Components/Figma/FgButton.vue";
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import * as Requests from "@System/Helpers/Requests.js";
import * as Alerts from "@System/Helpers/Alerts.js";
import AccountSkeleton from "./AccountSkeleton.vue";
import AccountCompanyCard from "./AccountCompanyCard.vue";
import AccountUserCard from "./AccountUserCard.vue";

const emptyForm = () => ({email: "", phone_number: "", gender: "", birthdate: ""});

export default {
    components: {FgButton, InitialLoadTransition, AccountSkeleton, AccountCompanyCard, AccountUserCard},
    data() {
        return {
            account: null,
            company: {},
            form: emptyForm(),
            errors: {},
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

            try {
                const result = await Requests.get({route: document.getElementById("app").dataset.endpoint});

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
