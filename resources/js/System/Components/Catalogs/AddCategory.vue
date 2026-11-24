<template>
    <QuickCreateCatalogEntity
        entity="categories"
        resource-key="category"
        singular-label="Categoría"
        trigger-mode="icon"
        trigger-class="fg-quick-create-trigger--field-label"
        :name-maxlength="50"
        :description-maxlength="100"
        v-bind="$attrs"
        @created="$emit('created', $event)"
        @postAction="$emit('postAction', $event)"/>
</template>

<script>
import QuickCreateCatalogEntity from "@System/Components/Catalogs/QuickCreateCatalogEntity.vue";

export default {
    name: "AddCategory",
    components: {
        QuickCreateCatalogEntity
    },
    inheritAttrs: false,
    emits: ["created", "postAction"]
};
</script>
