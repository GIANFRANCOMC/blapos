<template>
    <main class="fg-entity fg-roles fg-roles-skeleton fg-skeleton" role="status" aria-live="polite" aria-label="Cargando perfiles de acceso">
        <span class="visually-hidden">Cargando perfiles de acceso</span>

        <section class="fg-filter-bar fg-roles-skeleton__filters" aria-hidden="true">
            <div class="fg-roles-skeleton__search">
                <span class="fg-skeleton-line fg-roles-skeleton__label"></span>
                <span class="fg-skeleton-control"><span class="fg-skeleton-line fg-roles-skeleton__search-value"></span></span>
            </div>
            <div class="fg-filter-bar__actions">
                <span class="fg-skeleton-line fg-roles-skeleton__button"></span>
                <span class="fg-skeleton-line fg-roles-skeleton__button fg-roles-skeleton__button--add"></span>
            </div>
        </section>

        <section class="fg-entity-list" aria-hidden="true">
            <div class="table-responsive">
                <table class="table fg-entity-table mb-0">
                    <colgroup>
                        <col style="width: 34%">
                        <col style="width: 29%">
                        <col style="width: 16%">
                        <col style="width: 14%">
                        <col style="width: 7%">
                    </colgroup>
                    <thead class="fg-table-header-surface">
                        <tr>
                            <th>Perfil de acceso</th>
                            <th>Módulos permitidos</th>
                            <th>Colaboradores</th>
                            <th>Estado</th>
                            <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="fg-table__state">
                            <td colspan="5" class="py-4"><Loader/></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<script>
import Loader from "@System/Components/Loader.vue";

export default {components: {Loader}};
</script>
