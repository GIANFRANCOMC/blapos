<template>
    <InitialLoadTransition>
        <PageSkeleton v-if="initialLoading && skeletonEnabled"/>
        <div v-else class="br-page-content">
    <main class="br-guest-home">
        <section class="br-guest-hero">
            <div class="br-guest-container br-guest-hero__grid">
                <div class="br-guest-hero__content">
                    <p class="br-guest-eyebrow">Catálogo público</p>
                    <h1>{{ companyName }}</h1>
                    <p>{{ companyDescription }}</p>
                    <div class="br-guest-hero__actions">
                        <a v-if="whatsappNumber" class="br-guest-btn br-guest-btn-primary" :href="whatsappUrl" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            <span>Consultar por WhatsApp</span>
                        </a>
                        <a class="br-guest-btn br-guest-btn-secondary" href="#catalogo">
                            <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
                            <span>Ver catálogo</span>
                        </a>
                    </div>
                </div>
                <div class="br-guest-hero__brand" aria-hidden="true">
                    <img :src="companyLogo" :alt="companyName">
                </div>
            </div>
        </section>

        <section id="catalogo" class="br-guest-section">
            <div class="br-guest-container">
                <div class="br-guest-section__header">
                    <div>
                        <p class="br-guest-eyebrow">Disponible para clientes</p>
                        <h2>Productos, servicios y membresías</h2>
                    </div>
                    <div class="br-guest-catalog-count">
                        <strong>{{ filteredItems.length }}</strong>
                        <span>{{ filteredItems.length === 1 ? "registro visible" : "registros visibles" }}</span>
                    </div>
                </div>

                <div class="br-guest-categories" v-if="categoryOptions.length > 1">
                    <button
                        v-for="category in categoryOptions"
                        :key="category.code"
                        type="button"
                        :class="['br-guest-category', {'is-active': selectedCategory === category.code}]"
                        @click="selectedCategory = category.code">
                        <span>{{ category.label }}</span>
                        <strong>{{ category.count }}</strong>
                    </button>
                </div>

                <div class="br-guest-catalog" v-if="filteredItems.length > 0">
                    <article class="br-guest-item" v-for="item in filteredItems" :key="item.id">
                        <div class="br-guest-item__top">
                            <span :class="['br-guest-item__type', `is-${item.type}`]">{{ item.formatted_type }}</span>
                            <span v-if="item.formatted_duration" class="br-guest-item__duration">{{ item.formatted_duration }}</span>
                        </div>
                        <h3>{{ item.name }}</h3>
                        <p>{{ item.description || "Consulta más información con nuestro equipo de atención." }}</p>
                        <div class="br-guest-item__footer">
                            <strong v-if="hasVisiblePrice(item)">{{ priceLabel(item) }}</strong>
                            <span v-else class="br-guest-item__hidden-price">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                Precio a consultar
                            </span>
                            <a v-if="whatsappNumber" :href="itemWhatsappUrl(item)" class="br-guest-item__link" target="_blank" rel="noopener noreferrer" :aria-label="`Consultar ${item.name} por WhatsApp`">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                </div>

                <div class="br-guest-empty" v-else>
                    <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                    <strong>No hay registros públicos disponibles</strong>
                    <span>Cuando la empresa publique productos, servicios o membresías, aparecerán aquí.</span>
                </div>
            </div>
        </section>

        <section class="br-guest-section br-guest-section--contact">
            <div class="br-guest-container br-guest-contact">
                <div>
                    <p class="br-guest-eyebrow">Contacto</p>
                    <h2>Conecta con {{ companyName }}</h2>
                    <p>Usa los canales oficiales para resolver dudas antes de comprar o visitar una sede.</p>
                </div>
                <div class="br-guest-contact__grid">
                    <a v-if="company.telephone" :href="`tel:${company.telephone}`">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                        <span>{{ company.telephone }}</span>
                    </a>
                    <a v-if="company.email" :href="`mailto:${company.email}`">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                        <span>{{ company.email }}</span>
                    </a>
                    <span v-if="company.address">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <span>{{ company.address }}</span>
                    </span>
                </div>
            </div>
        </section>
    </main>
        </div>
    </InitialLoadTransition>
</template>

<script>
import InitialPageMixin from "@System/Helpers/InitialPageMixin.js";
import * as Constants from "../../Helpers/Constants.js";
import * as Requests from "../../Helpers/Requests.js";
import * as Utils from "../../Helpers/Utils.js";

export default {
    mixins: [InitialPageMixin],
    data() {
        return {
            selectedCategory: "all",
            options: {
                company: {records: []},
                items: {records: []},
                categories: {records: []}
            },
            config: {
                ...Constants.generalConfig,
                entity: {
                    ...Requests.config({entity: "home"})
                }
            }
        };
    },
    async mounted() {
        await this.loadInitialParams(() => this.initParams());
    },
    methods: {
        async initParams() {
            const initParams = await Requests.get({
                route: this.config.entity.routes.initParams,
                data: {page: "main"},
                showAlert: true
            });

            this.options.company = initParams.data?.config?.company || {records: []};
            this.options.items = initParams.data?.config?.items || {records: []};
            this.options.categories = initParams.data?.config?.categories || {records: []};
        },
        hasVisiblePrice(item) {
            return item?.see_my_web_price && Utils.isDefined({value: item?.price}) && Utils.isDefined({value: item?.currency?.sign});
        },
        priceLabel(item) {
            return `${item.currency.sign} ${Utils.separatorNumber(item.price)}`;
        },
        itemWhatsappUrl(item) {
            const message = `Hola, quiero información sobre ${item.name}.`;
            return `https://wa.me/${this.whatsappNumber}?text=${encodeURIComponent(message)}`;
        }
    },
    computed: {
        company() {
            return this.options.company?.records?.[0] || this.config.essential.company || {};
        },
        companyName() {
            return this.company.commercial_name || this.company.legal_name || "Catálogo";
        },
        companyDescription() {
            return this.company.description || this.company.tagline || "Explora la información pública disponible para clientes.";
        },
        whatsappNumber() {
            return String(this.company.whatsapp || "").replace(/\D/g, "");
        },
        companyLogo() {
            const logo = this.company.combinationmark || this.company.logotype || this.company.logomark;

            return logo
                ? Utils.getAsset(logo, {type: "storage"})
                : Utils.getAsset(this.config.essential.ownerApp?.assets?.img?.combinationmark, {type: "none", back: 1});
        },
        whatsappUrl() {
            return `https://wa.me/${this.whatsappNumber}?text=${encodeURIComponent(`Hola, quiero información de ${this.companyName}.`)}`;
        },
        items() {
            return this.options.items?.records || [];
        },
        categories() {
            return this.options.categories?.records || [];
        },
        categoryOptions() {
            const options = this.categories.map(category => ({
                code: category.id,
                label: category.name,
                count: this.items.filter(item => (item.categories || []).some(itemCategory => itemCategory.id === category.id)).length
            })).filter(category => category.count > 0);

            return [
                {code: "all", label: "Todo", count: this.items.length},
                ...options
            ];
        },
        filteredItems() {
            if(this.selectedCategory === "all") return this.items;

            return this.items.filter(item => (item.categories || []).some(category => category.id === this.selectedCategory));
        }
    }
};
</script>
