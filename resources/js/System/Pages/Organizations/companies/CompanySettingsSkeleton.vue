<template>
    <main class="br-company-settings br-entity-modal--tabbed br-company-skeleton br-skeleton" role="status" aria-live="polite" aria-label="Cargando empresa">
        <span class="visually-hidden">Cargando empresa</span>
        <section class="br-company-settings__panel" aria-hidden="true">
            <div class="br-tabs br-tabs--descriptive" aria-hidden="true">
                <span class="br-tabs__step br-skeleton-line"></span>
                <div class="br-tabs__list">
                    <span v-for="index in 3" :key="index" class="br-tabs__item" :aria-selected="index === 1">
                        <span class="br-tabs__icon br-skeleton-line"></span>
                        <span class="br-tabs__text">
                            <span class="br-skeleton-line br-skeleton-tab__label"></span>
                            <span class="br-skeleton-line br-skeleton-tab__description"></span>
                        </span>
                    </span>
                </div>
                <span class="br-tabs__step br-skeleton-line"></span>
            </div>

            <div class="br-company-settings__content br-company-skeleton__fields">
                <div v-for="index in 6" :key="index" :class="['br-company-skeleton__field', {'br-company-skeleton__field--full': index > 4}]">
                    <span class="br-skeleton-line br-company-skeleton__label"></span>
                    <span class="br-skeleton-control"><span class="br-skeleton-line br-company-skeleton__value"></span></span>
                </div>
            </div>

            <footer class="br-company-settings__footer">
                <span class="br-skeleton-line br-company-skeleton__button"></span>
            </footer>
        </section>
    </main>
</template>
