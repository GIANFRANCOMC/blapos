<template>
    <main class="br-workspace" aria-labelledby="workspace-popular-title">
        <WorkspaceSkeleton v-if="loading && skeletonEnabled"/>
        <nav v-else-if="breadcrumb.section && breadcrumb.page" class="br-breadcrumb br-workspace__breadcrumb" aria-label="Ubicación actual">
            <ol class="br-breadcrumb__list">
                <li class="br-breadcrumb__item"><span>{{ breadcrumb.section }}</span></li>
                <li class="br-breadcrumb__item is-current" aria-current="page">
                    <i class="fa-solid fa-chevron-right br-breadcrumb__separator" aria-hidden="true"></i>
                    <span>{{ breadcrumb.page }}</span>
                </li>
            </ol>
        </nav>

        <div v-if="loading && !skeletonEnabled" class="br-workspace__loading" aria-hidden="true"></div>
        <div v-else-if="!loading && loadError" class="br-workspace__empty" role="alert">
            {{ loadError }}
            <button type="button" class="br-btn br-btn-outline-secondary" @click="loadWorkspace">Reintentar</button>
        </div>
        <template v-else-if="!loading">
            <div class="br-workspace__layout">
                <div class="br-workspace__main">
                    <section class="br-workspace__section" aria-labelledby="workspace-popular-title">
                        <div class="br-workspace__section-heading">
                            <span class="br-workspace__section-eyebrow">Accesos rápidos</span>
                            <h1 id="workspace-popular-title">¿Qué deseas hacer hoy, {{ firstName }}?</h1>
                            <p>Encuentra aquí las acciones principales para gestionar tu empresa de forma rápida y sencilla.</p>
                        </div>

                        <div class="br-workspace__links">
                            <a v-for="item in actions" :key="item.id" :href="item.url" class="br-workspace__link">
                                <span class="br-workspace__link-icon" aria-hidden="true"><i :class="item.icon"></i></span>
                                <span class="br-workspace__link-copy">
                                    <strong>{{ item.label }}</strong>
                                    <small v-if="item.description">{{ item.description }}</small>
                                </span>
                                <span class="br-workspace__link-arrow" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                            </a>
                            <div v-if="!actions.length" class="br-workspace__empty">No hay módulos disponibles para tu perfil.</div>
                        </div>
                    </section>
                </div>

                <aside class="br-workspace__aside" aria-labelledby="workspace-company-title">
                    <CompanyCard :company="company"/>
                </aside>
            </div>

            <div class="br-workspace__support" role="note" aria-label="Ayuda de Blapos">
                <span class="br-workspace__support-icon" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                <div class="br-workspace__support-intro">
                    <strong>¿Necesitas ayuda?</strong>
                    <span>Estamos para ayudarte. Contáctanos.</span>
                </div>
                <a class="br-workspace__support-channel br-workspace__support-channel--email" :href="`mailto:${support.email}`">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    <span class="br-workspace__support-channel-copy">
                        <strong>{{ support.email }}</strong>
                        <small>Envíanos un correo</small>
                    </span>
                </a>
                <a class="br-workspace__support-channel br-workspace__support-channel--phone" :href="`tel:${support.phone_href}`">
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    <span class="br-workspace__support-channel-copy">
                        <strong>{{ support.phone }}</strong>
                        <small>Llámanos</small>
                    </span>
                </a>
                <a class="br-workspace__support-cta" :href="`mailto:${support.email}`">
                    Contáctanos <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </template>
    </main>
</template>

<script>
import * as Requests from "@System/Helpers/Requests.js";
import {createInitialLoad} from "@System/Helpers/InitialLoad.js";

const initialLoad = createInitialLoad({skeleton: true});

export default {
    data() {
        return {
            firstName: "",
            actions: [],
            breadcrumb: {section: "", page: ""},
            company: {},
            support: {email: "", phone: "", phone_href: ""},
            skeletonEnabled: initialLoad.skeleton,
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
            initialLoad.start();

            try {
                const result = await Requests.get({route: window.location.pathname});

                if(!result.bool || !result.data?.company) {
                    this.loadError = result.data?.msg || "No fue posible cargar tu espacio.";
                    return;
                }

                this.firstName = result.data.first_name || "";
                this.actions = result.data.actions || [];
                this.breadcrumb = result.data.breadcrumb || this.breadcrumb;
                this.company = result.data.company;
                this.support = result.data.support || this.support;
            } catch(error) {
                this.loadError = "No fue posible cargar tu espacio.";
            } finally {
                initialLoad.finish();
                this.loading = false;
            }
        }
    }
};
</script>
