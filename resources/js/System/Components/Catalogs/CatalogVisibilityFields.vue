<template>
    <h3 class="fg-section-title">Visibilidad y condiciones</h3>
    <p>Publicación en el catálogo</p>
    <label class="fg-check" :for="`${idPrefix}-publish`">
        <input
            :id="`${idPrefix}-publish`"
            :checked="published"
            :disabled="disabled"
            type="checkbox"
            role="switch"
            :aria-labelledby="`${idPrefix}-publish-label`"
            :aria-describedby="`${idPrefix}-publish-help`"
            @change="$emit('update:published', $event.target.checked)">
        <span class="fg-check__text">
            <strong :id="`${idPrefix}-publish-label`">Publicar {{ itemLabel }}</strong>
            <small :id="`${idPrefix}-publish-help`">Se muestra a tus clientes cuando está activo.</small>
        </span>
    </label>

    <label class="fg-check" :for="`${idPrefix}-show-price`">
        <input
            :id="`${idPrefix}-show-price`"
            :checked="published && showPrice"
            :disabled="disabled || !published"
            type="checkbox"
            role="switch"
            :aria-labelledby="`${idPrefix}-show-price-label`"
            :aria-describedby="`${idPrefix}-show-price-help`"
            @change="$emit('update:showPrice', $event.target.checked)">
        <span class="fg-check__text">
            <strong :id="`${idPrefix}-show-price-label`">Mostrar precio</strong>
            <small :id="`${idPrefix}-show-price-help`">Al ocultarlo, aparece «Precio a consultar».</small>
        </span>
    </label>
</template>

<script>
export default {
    name: "CatalogVisibilityFields",
    emits: ["update:published", "update:showPrice"],
    props: {
        idPrefix: {type: String, required: true},
        itemLabel: {type: String, required: true},
        published: {type: Boolean, default: false},
        showPrice: {type: Boolean, default: false},
        disabled: Boolean
    }
};
</script>
