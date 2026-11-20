<template>
    <FormGroup title="Visibilidad para clientes" icon="fa-solid fa-eye">
        <div class="br-entity-publication-settings">
            <label class="br-entity-switch" :for="`${idPrefix}-publish`">
                <input
                    :id="`${idPrefix}-publish`"
                    :checked="published"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :aria-labelledby="`${idPrefix}-publish-label`"
                    :aria-describedby="`${idPrefix}-publish-help`"
                    @change="changePublication($event.target.checked)">
                <span>
                    <strong :id="`${idPrefix}-publish-label`">Publicar {{ itemLabel }}</strong>
                    <small :id="`${idPrefix}-publish-help`">Aparece en el catálogo público cuando está activo.</small>
                </span>
            </label>

            <label class="br-entity-switch" :class="{'is-disabled': !published}" :for="`${idPrefix}-show-price`">
                <input
                    :id="`${idPrefix}-show-price`"
                    :checked="published && showPrice"
                    :disabled="!published"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :aria-labelledby="`${idPrefix}-show-price-label`"
                    :aria-describedby="`${idPrefix}-show-price-help`"
                    @change="$emit('update:showPrice', $event.target.checked)">
                <span>
                    <strong :id="`${idPrefix}-show-price-label`">Mostrar precio</strong>
                    <small :id="`${idPrefix}-show-price-help`">
                        {{ published ? 'Si lo ocultas, se muestra «Precio a consultar».' : 'Activa la publicación para mostrar el precio.' }}
                    </small>
                </span>
            </label>
        </div>
    </FormGroup>
</template>

<script>
import FormGroup from "@/Shared/FormGroup.vue";

export default {
    name: "CatalogVisibilityFields",
    components: {FormGroup},
    emits: ["update:published", "update:showPrice"],
    props: {
        idPrefix: {type: String, required: true},
        itemLabel: {type: String, required: true},
        published: {type: Boolean, default: false},
        showPrice: {type: Boolean, default: false}
    },
    methods: {
        changePublication(published) {
            this.$emit("update:published", published);
            if(!published) this.$emit("update:showPrice", false);
        }
    }
};
</script>
