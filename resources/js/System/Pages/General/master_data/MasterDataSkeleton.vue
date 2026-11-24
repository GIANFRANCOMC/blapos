<template>
    <section class="fg-master-data fg-master-skeleton fg-skeleton" role="status" aria-live="polite" aria-label="Cargando datos maestros">
        <span class="visually-hidden">Cargando datos maestros</span>

        <div class="fg-tabs fg-tabs--descriptive" aria-hidden="true">
            <span class="fg-tabs__step fg-skeleton-line"></span>
            <div class="fg-tabs__list">
                <span v-for="index in 6" :key="index" class="fg-tabs__item" :aria-selected="index === 2">
                    <span class="fg-tabs__icon fg-skeleton-line"></span>
                    <span class="fg-tabs__text">
                        <span class="fg-skeleton-line fg-skeleton-tab__label"></span>
                        <span class="fg-skeleton-line fg-skeleton-tab__description"></span>
                    </span>
                </span>
            </div>
            <span class="fg-tabs__step fg-skeleton-line"></span>
        </div>

        <main class="fg-master-data__workspace" aria-hidden="true">
            <header class="fg-master-data__header">
                <span class="fg-master-skeleton__header-copy">
                    <span class="fg-skeleton-line fg-master-skeleton__title"></span>
                    <span class="fg-skeleton-line fg-master-skeleton__subtitle"></span>
                </span>
                <span class="fg-skeleton-line fg-master-skeleton__header-button"></span>
            </header>

            <div class="fg-master-data__grid">
                <section class="fg-entity-list fg-master-data__records">
                    <div class="fg-master-data__records-head">
                        <span class="fg-skeleton-control fg-master-skeleton__search"><span class="fg-skeleton-line fg-master-skeleton__search-value"></span></span>
                    </div>
                    <Loader/>
                </section>

                <section class="fg-master-data-form">
                    <header><span class="fg-skeleton-line fg-master-skeleton__form-title"></span></header>
                    <div class="fg-master-skeleton__notice"><span class="fg-skeleton-line fg-master-skeleton__notice-line"></span></div>
                    <div class="fg-master-skeleton__fields">
                        <div v-for="index in 6" :key="index" :class="['fg-master-skeleton__field', {'fg-master-skeleton__field--full': index === 5}]">
                            <span class="fg-skeleton-line fg-master-skeleton__label"></span>
                            <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-master-skeleton__field-value"></span></span>
                        </div>
                    </div>
                    <footer>
                        <span class="fg-skeleton-line fg-master-skeleton__footer-button"></span>
                        <span class="fg-skeleton-line fg-master-skeleton__footer-button fg-master-skeleton__footer-button--save"></span>
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
