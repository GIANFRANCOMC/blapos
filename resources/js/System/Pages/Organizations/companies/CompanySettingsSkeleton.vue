<template>
    <main class="fg-company-settings fg-entity-modal--tabbed fg-company-skeleton fg-skeleton" role="status" aria-live="polite" aria-label="Cargando empresa">
        <span class="visually-hidden">Cargando empresa</span>
        <section class="fg-company-settings__panel" aria-hidden="true">
            <div class="fg-tabs fg-tabs--descriptive" aria-hidden="true">
                <span class="fg-tabs__step fg-skeleton-line"></span>
                <div class="fg-tabs__list">
                    <span v-for="index in 3" :key="index" class="fg-tabs__item" :aria-selected="index === 1">
                        <span class="fg-tabs__icon fg-skeleton-line"></span>
                        <span class="fg-tabs__text">
                            <span class="fg-skeleton-line fg-skeleton-tab__label"></span>
                            <span class="fg-skeleton-line fg-skeleton-tab__description"></span>
                        </span>
                    </span>
                </div>
                <span class="fg-tabs__step fg-skeleton-line"></span>
            </div>

            <div class="fg-company-settings__content fg-company-skeleton__fields">
                <div v-for="index in 6" :key="index" :class="['fg-company-skeleton__field', {'fg-company-skeleton__field--full': index > 4}]">
                    <span class="fg-skeleton-line fg-company-skeleton__label"></span>
                    <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-company-skeleton__value"></span></span>
                </div>
            </div>

            <footer class="fg-company-settings__footer">
                <span class="fg-skeleton-line fg-company-skeleton__button"></span>
            </footer>
        </section>
    </main>
</template>
