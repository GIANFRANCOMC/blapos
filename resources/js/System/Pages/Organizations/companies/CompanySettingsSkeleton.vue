<template>
    <main class="br-company-settings br-entity-modal--tabbed br-company-skeleton" role="status" aria-live="polite" aria-label="Cargando empresa">
        <span class="visually-hidden">Cargando empresa</span>
        <section class="br-company-settings__panel" aria-hidden="true">
            <div class="br-catalog-form-tabs br-catalog-form-tabs--descriptive br-company-skeleton__tabs">
                <span v-for="index in 3" :key="index" class="br-company-skeleton__tab">
                    <span class="br-company-skeleton__line br-company-skeleton__tab-icon"></span>
                    <span class="br-company-skeleton__tab-copy">
                        <span class="br-company-skeleton__line br-company-skeleton__tab-title"></span>
                        <span class="br-company-skeleton__line br-company-skeleton__tab-description"></span>
                    </span>
                </span>
            </div>

            <div class="br-company-settings__content br-company-skeleton__fields">
                <div v-for="index in 6" :key="index" :class="['br-company-skeleton__field', {'br-company-skeleton__field--full': index > 4}]">
                    <span class="br-company-skeleton__line br-company-skeleton__label"></span>
                    <span class="br-company-skeleton__control"><span class="br-company-skeleton__line br-company-skeleton__value"></span></span>
                </div>
            </div>

            <footer class="br-company-settings__footer">
                <span class="br-company-skeleton__line br-company-skeleton__button"></span>
            </footer>
        </section>
    </main>
</template>
