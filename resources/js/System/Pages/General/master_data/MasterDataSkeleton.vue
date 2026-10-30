<template>
    <section class="br-master-data br-master-skeleton" role="status" aria-live="polite" aria-label="Cargando datos maestros">
        <span class="visually-hidden">Cargando datos maestros</span>

        <div class="br-catalog-form-tabs br-catalog-form-tabs--descriptive br-master-skeleton__tabs" aria-hidden="true">
            <span v-for="index in 6" :key="index" :class="['br-master-skeleton__tab', {'is-active': index === 2}]">
                <span class="br-master-skeleton__line br-master-skeleton__tab-icon"></span>
                <span class="br-master-skeleton__tab-copy">
                    <span class="br-master-skeleton__line br-master-skeleton__tab-title"></span>
                    <span class="br-master-skeleton__line br-master-skeleton__tab-description"></span>
                </span>
            </span>
        </div>

        <main class="br-master-data__workspace" aria-hidden="true">
            <header class="br-master-data__header">
                <span class="br-master-skeleton__header-copy">
                    <span class="br-master-skeleton__line br-master-skeleton__title"></span>
                    <span class="br-master-skeleton__line br-master-skeleton__subtitle"></span>
                </span>
                <span class="br-master-skeleton__line br-master-skeleton__header-button"></span>
            </header>

            <div class="br-master-data__grid">
                <section class="br-entity-list br-master-data__records">
                    <div class="br-master-data__records-head">
                        <span class="br-master-skeleton__search"><span class="br-master-skeleton__line br-master-skeleton__search-value"></span></span>
                        <span class="br-master-skeleton__line br-master-skeleton__count"></span>
                    </div>
                    <div class="br-master-data-list">
                        <article v-for="index in 5" :key="index" class="br-master-data-row">
                            <span class="br-master-skeleton__record-copy">
                                <span class="br-master-skeleton__line br-master-skeleton__record-title"></span>
                                <span class="br-master-skeleton__line br-master-skeleton__record-description"></span>
                                <span class="br-master-skeleton__line br-master-skeleton__record-meta"></span>
                            </span>
                            <span class="br-master-skeleton__line br-master-skeleton__badge"></span>
                        </article>
                    </div>
                </section>

                <section class="br-master-data-form">
                    <header><span class="br-master-skeleton__line br-master-skeleton__form-title"></span></header>
                    <div class="br-master-skeleton__notice"><span class="br-master-skeleton__line br-master-skeleton__notice-line"></span></div>
                    <div class="br-master-skeleton__fields">
                        <div v-for="index in 6" :key="index" :class="['br-master-skeleton__field', {'br-master-skeleton__field--full': index === 5}]">
                            <span class="br-master-skeleton__line br-master-skeleton__label"></span>
                            <span class="br-master-skeleton__control"><span class="br-master-skeleton__line br-master-skeleton__field-value"></span></span>
                        </div>
                    </div>
                    <footer>
                        <span class="br-master-skeleton__line br-master-skeleton__footer-button"></span>
                        <span class="br-master-skeleton__line br-master-skeleton__footer-button br-master-skeleton__footer-button--save"></span>
                    </footer>
                </section>
            </div>
        </main>
    </section>
</template>
