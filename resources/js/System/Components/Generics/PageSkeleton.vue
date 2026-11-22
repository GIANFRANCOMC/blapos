<template>
    <section class="fg-page-skeleton fg-skeleton" role="status" aria-live="polite" aria-busy="true" aria-label="Cargando configuración">
        <span class="fg-sr-only">Cargando configuración</span>
        <FgSkeletonFilters/>
        <div v-if="table" class="fg-table-scroll fg-page-skeleton__surface" aria-hidden="true">
            <table class="fg-table">
                <thead>
                    <tr>
                        <th><span class="fg-catalog-skeleton__heading"><span class="fg-skeleton-line"/></span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="fg-table__state">
                        <td><FgListLoading label="Cargando configuración…"/></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="fg-page-skeleton__surface" aria-hidden="true"/>
    </section>
</template>

<script>
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgSkeletonFilters from "@System/Components/Figma/FgSkeletonFilters.vue";

export default {
    components: {FgListLoading, FgSkeletonFilters},
    props: {table: Boolean}
};
</script>
