<template>
    <main class="fg-workspace" aria-label="Inicio">
        <InitialLoadTransition>
            <WorkspaceSkeleton v-if="loading"/>
            <div v-else class="fg-workspace__content">
                <header class="fg-workspace__welcome">
                    <h1>{{ firstName ? "Hola, " + firstName : "Inicio" }}</h1>
                    <p>¿Qué necesitas resolver hoy?</p>
                </header>

                <div v-if="loadError" class="fg-workspace__empty" role="alert">
                    {{ loadError }}
                    <button type="button" class="fg-button fg-button--secondary" @click="loadWorkspace">Reintentar</button>
                </div>
                <template v-else>
                    <div class="fg-workspace__layout">
                        <div class="fg-workspace__main">
                            <section class="fg-workspace__section" aria-labelledby="workspace-popular-title">
                                <div class="fg-workspace__section-heading">
                                    <h2 id="workspace-popular-title">Accesos frecuentes</h2>
                                </div>

                                <div class="fg-workspace__links">
                                    <a v-for="item in actions" :key="item.id" :href="item.url" class="fg-workspace__link">
                                        <span class="fg-workspace__link-icon" aria-hidden="true"><i :class="item.icon"></i></span>
                                        <span class="fg-workspace__link-copy">
                                            <strong>{{ item.label }}</strong>
                                            <small v-if="item.description">{{ item.description }}</small>
                                        </span>
                                        <span class="fg-workspace__link-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                                    </a>
                                    <div v-if="!actions.length" class="fg-workspace__empty">No hay módulos disponibles para tu perfil.</div>
                                </div>
                            </section>
                        </div>

                        <aside class="fg-workspace__aside" aria-labelledby="workspace-company-title">
                            <CompanyCard :company="company"/>
                        </aside>
                    </div>

                    <div class="fg-workspace__support" aria-label="Ayuda de Blapos">
                        <span class="fg-workspace__support-icon" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                        <div class="fg-workspace__support-intro">
                            <strong>¿Necesitas ayuda?</strong>
                            <span>Estamos para ayudarte. Contáctanos.</span>
                        </div>
                        <a class="fg-workspace__support-channel fg-workspace__support-channel--email" :href="`mailto:${support.email}`">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            <span class="fg-workspace__support-channel-copy">
                                <strong>{{ support.email }}</strong>
                                <small>Envíanos un correo</small>
                            </span>
                        </a>
                        <a class="fg-workspace__support-channel fg-workspace__support-channel--phone" :href="`tel:${support.phone_href}`">
                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            <span class="fg-workspace__support-channel-copy">
                                <strong>{{ support.phone }}</strong>
                                <small>Llámanos</small>
                            </span>
                        </a>
                        <a class="fg-workspace__support-cta" :href="`mailto:${support.email}`">
                            Contáctanos <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </template>
            </div>
        </InitialLoadTransition>
    </main>
</template>

<script>
import InitialLoadTransition from "@System/Components/Generics/InitialLoadTransition.vue";
import * as Requests from "@System/Helpers/Requests.js";


export default {
    components: {InitialLoadTransition},
    data() {
        return {
            firstName: "",
            actions: [],
            company: {},
            support: {email: "", phone: "", phone_href: ""},
            loading: true,
            loadError: ""
        };
    },
    mounted() {
        this.loadWorkspace();
    },
    methods: {
        async loadWorkspace() {
            this.loading = true;
            this.loadError = "";

            try {
                const result = await Requests.get({route: document.getElementById("app").dataset.endpoint});

                if(!result.bool || !result.data?.company) {
                    this.loadError = result.data?.msg || "No fue posible cargar tu espacio.";
                    return;
                }

                this.firstName = result.data.first_name || "";
                this.actions = result.data.actions || [];
                this.company = result.data.company;
                this.support = result.data.support || this.support;
            } catch(error) {
                this.loadError = "No fue posible cargar tu espacio.";
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
