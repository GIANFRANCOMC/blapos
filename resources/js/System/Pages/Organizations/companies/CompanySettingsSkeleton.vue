<template>
    <main class="br-company-settings br-entity-modal--tabbed br-company-skeleton fg-skeleton" role="status" aria-live="polite" aria-label="Cargando empresa">
        <span class="visually-hidden">Cargando empresa</span>
        <section class="br-company-settings__panel" aria-hidden="true">
            <div class="br-tabs br-tabs--descriptive" aria-hidden="true">
                <span class="br-tabs__step fg-skeleton-line"></span>
                <div class="br-tabs__list">
                    <span v-for="index in 3" :key="index" class="br-tabs__item" :aria-selected="index === 1">
                        <span class="br-tabs__icon fg-skeleton-line"></span>
                        <span class="br-tabs__text">
                            <span class="fg-skeleton-line fg-skeleton-tab__label"></span>
                            <span class="fg-skeleton-line fg-skeleton-tab__description"></span>
                        </span>
                    </span>
                </div>
                <span class="br-tabs__step fg-skeleton-line"></span>
            </div>

            <div class="br-company-settings__content br-company-skeleton__fields">
                <div v-for="index in 6" :key="index" :class="['br-company-skeleton__field', {'br-company-skeleton__field--full': index > 4}]">
                    <span class="fg-skeleton-line br-company-skeleton__label"></span>
                    <span class="fg-skeleton-control"><span class="fg-skeleton-line br-company-skeleton__value"></span></span>
                </div>
            </div>

            <footer class="br-company-settings__footer">
                <span class="fg-skeleton-line br-company-skeleton__button"></span>
            </footer>
        </section>
    </main>
</template>
