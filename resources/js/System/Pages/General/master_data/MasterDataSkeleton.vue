<template>
    <section class="br-master-data br-master-skeleton br-skeleton" role="status" aria-live="polite" aria-label="Cargando datos maestros">
        <span class="visually-hidden">Cargando datos maestros</span>

        <div class="br-tabs br-tabs--descriptive" aria-hidden="true">
            <span class="br-tabs__step br-skeleton-line"></span>
            <div class="br-tabs__list">
                <span v-for="index in 6" :key="index" class="br-tabs__item" :aria-selected="index === 2">
                    <span class="br-tabs__icon br-skeleton-line"></span>
                    <span class="br-tabs__text">
                        <span class="br-skeleton-line br-skeleton-tab__label"></span>
                        <span class="br-skeleton-line br-skeleton-tab__description"></span>
                    </span>
                </span>
            </div>
            <span class="br-tabs__step br-skeleton-line"></span>
        </div>

        <main class="br-master-data__workspace" aria-hidden="true">
            <header class="br-master-data__header">
                <span class="br-master-skeleton__header-copy">
                    <span class="br-skeleton-line br-master-skeleton__title"></span>
                    <span class="br-skeleton-line br-master-skeleton__subtitle"></span>
                </span>
                <span class="br-skeleton-line br-master-skeleton__header-button"></span>
            </header>

            <div class="br-master-data__grid">
                <section class="br-entity-list br-master-data__records">
                    <div class="br-master-data__records-head">
                        <span class="br-skeleton-control br-master-skeleton__search"><span class="br-skeleton-line br-master-skeleton__search-value"></span></span>
                    </div>
                    <Loader/>
                </section>

                <section class="br-master-data-form">
                    <header><span class="br-skeleton-line br-master-skeleton__form-title"></span></header>
                    <div class="br-master-skeleton__notice"><span class="br-skeleton-line br-master-skeleton__notice-line"></span></div>
                    <div class="br-master-skeleton__fields">
                        <div v-for="index in 6" :key="index" :class="['br-master-skeleton__field', {'br-master-skeleton__field--full': index === 5}]">
                            <span class="br-skeleton-line br-master-skeleton__label"></span>
                            <span class="br-skeleton-control"><span class="br-skeleton-line br-master-skeleton__field-value"></span></span>
                        </div>
                    </div>
                    <footer>
                        <span class="br-skeleton-line br-master-skeleton__footer-button"></span>
                        <span class="br-skeleton-line br-master-skeleton__footer-button br-master-skeleton__footer-button--save"></span>
                    </footer>
                </section>
            </div>
        </main>
    </section>
</template>

<script>
import Loader from "@System/Components/Loader.vue";

export default {components: {Loader}};
</script>
