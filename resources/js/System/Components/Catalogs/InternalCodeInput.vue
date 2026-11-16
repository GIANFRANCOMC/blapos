<template>
    <InputText v-bind="$attrs" no-whitespace maxlength="50" :transform-value="transformValue">
        <template #default>
            <QuickCreateTrigger
                v-if="prefix"
                mode="icon"
                custom-class="br-quick-create-trigger--field-label"
                :icon="usePrefix ? 'fa-solid fa-circle-minus' : 'fa-solid fa-circle-plus'"
                :title="usePrefix ? 'Quitar prefijo' : 'Agregar prefijo'"
                @click="$emit('togglePrefix')"/>
        </template>
        <template #inputGroupPrepend>
            <span v-if="usePrefix" class="input-group-text br-internal-code-prefix" v-text="prefix"></span>
        </template>
        <template #defaultAppend>
            <slot name="defaultAppend"></slot>
        </template>
        <template #inputGroupAppend>
            <slot name="inputGroupAppend"></slot>
        </template>
    </InputText>
</template>

<script>
import InputText from "@System/Components/InputText.vue";
import QuickCreateTrigger from "@System/Components/Generics/QuickCreateTrigger.vue";

export default {
    name: "InternalCodeInput",
    components: {InputText, QuickCreateTrigger},
    inheritAttrs: false,
    emits: ["togglePrefix"],
    props: {
        prefix: {type: String, default: ""},
        usePrefix: Boolean,
        transformValue: {type: Function, required: true}
    }
};
</script>
