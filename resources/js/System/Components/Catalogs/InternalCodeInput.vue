<template>
    <InputText v-bind="$attrs" no-whitespace :maxlength="usePrefix ? 50 - prefix.length : 50" :counter-offset="usePrefix ? prefix.length : 0" :transform-value="transformValue">
        <template #default>
            <button v-if="prefix" type="button" class="fg-link" :aria-pressed="usePrefix" @click="$emit('togglePrefix')">{{ usePrefix ? 'Sin prefijo' : 'Usar prefijo' }}</button>
            <slot name="action"/><slot name="default"/>

        </template>
        <template #inputGroupPrepend>
            <span v-if="usePrefix" :class="'fg-field__prefix'" v-text="prefix"></span>
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

export default {
    name: "InternalCodeInput",
    components: {InputText},
    inheritAttrs: false,
    emits: ["togglePrefix"],
    props: {
        prefix: {type: String, default: ""},
        usePrefix: Boolean,
        transformValue: {type: Function, required: true}
    }
};
</script>
