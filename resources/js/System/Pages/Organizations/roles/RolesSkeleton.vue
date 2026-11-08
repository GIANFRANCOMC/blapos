<template>
    <main class="br-entity br-roles br-roles-skeleton" role="status" aria-live="polite" aria-label="Cargando perfiles de acceso">
        <span class="visually-hidden">Cargando perfiles de acceso</span>

        <section class="br-filter-bar br-roles-skeleton__filters" aria-hidden="true">
            <div class="br-roles-skeleton__search">
                <span class="br-roles-skeleton__line br-roles-skeleton__label"></span>
                <span class="br-roles-skeleton__control"><span class="br-roles-skeleton__line br-roles-skeleton__search-value"></span></span>
            </div>
            <div class="br-filter-bar__actions">
                <span class="br-roles-skeleton__line br-roles-skeleton__button"></span>
                <span class="br-roles-skeleton__line br-roles-skeleton__button br-roles-skeleton__button--add"></span>
            </div>
        </section>

        <section class="br-entity-list" aria-hidden="true">
            <div class="table-responsive">
                <table class="table br-entity-table mb-0">
                    <colgroup>
                        <col style="width: 34%">
                        <col style="width: 29%">
                        <col style="width: 16%">
                        <col style="width: 14%">
                        <col style="width: 7%">
                    </colgroup>
                    <thead class="br-table-header-surface">
                        <tr>
                            <th>Perfil de acceso</th>
                            <th>Módulos permitidos</th>
                            <th>Colaboradores</th>
                            <th>Estado</th>
                            <th class="text-center"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="br-table-state">
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
