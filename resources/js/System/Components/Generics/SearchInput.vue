<template>
    <InputText
        ref="input"
        v-bind="$attrs"
        :model-value="modelValue"
        :disabled="disabled"
        @update:model-value="$emit('update:modelValue', $event)"
        @enterKeyPressed="$emit('search')">
        <template #inputGroupAppend>
            <button
                v-if="String(modelValue ?? '').length > 0"
                type="button"
                class="br-input-action"
                title="Limpiar búsqueda"
                aria-label="Limpiar búsqueda"
                :disabled="disabled"
                @click="clear">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </template>
    </InputText>
</template>

<script>
import InputText from "@System/Components/InputText.vue";

export default {
    name: "SearchInput",
    components: {InputText},
    inheritAttrs: false,
    emits: ["update:modelValue", "search", "clear"],
    props: {
        modelValue: {type: [String, Number], default: ""},
        disabled: {type: Boolean, default: false}
    },
    methods: {
        clear() {

            this.$emit("update:modelValue", "");
            this.$emit("clear");
            this.focus();

        },
        focus() {

            this.$refs.input?.focus();

        }
    }
};
</script>
