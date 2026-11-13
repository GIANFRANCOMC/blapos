<template>
    <QuickCreateCatalogEntity
        entity="brands"
        resource-key="brand"
        singular-label="Marca"
        internal-code-pattern="^[A-Za-z0-9._-]+$"
        internal-code-pattern-message="El código interno solo puede contener letras, números, puntos, guiones y guiones bajos."
        :name-maxlength="100"
        :description-maxlength="250"
        v-bind="$attrs"
        @created="$emit('created', $event)"
        @postAction="$emit('postAction', $event)"/>
</template>

<script>
import QuickCreateCatalogEntity from "@System/Components/Catalogs/QuickCreateCatalogEntity.vue";

export default {
    name: "AddBrand",
    components: {
        QuickCreateCatalogEntity
    },
    inheritAttrs: false,
    emits: ["created", "postAction"]
};
</script>
