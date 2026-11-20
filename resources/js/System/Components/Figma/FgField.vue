<template>
    <div class="fg-field">
        <div class="fg-field__label-row">
            <label :for="id" class="fg-field__label">{{ label }}<span v-if="required" class="fg-required" aria-hidden="true">*</span></label>
            <div v-if="$slots.action" class="fg-field__actions"><slot name="action"/></div>
        </div>
        <slot :id="id" :invalid="Boolean(errorText)" :error-id="errorText ? `${id}-error` : undefined"/>
        <small v-if="errorText" :id="`${id}-error`" class="fg-error" role="alert">{{ errorText }}</small>
        <small v-else-if="help" class="fg-help">{{ help }}</small>
    </div>
</template>
<script setup>
import {computed, useId, provide, reactive} from 'vue';
const props = defineProps({label: String, required: Boolean, error: [String, Array], help: String, inputId: String});
const uid = useId();
const id = computed(() => props.inputId || `fg-field-${uid}`);
const errorText = computed(() => Array.isArray(props.error) ? props.error[0] : props.error);
provide('fgField', reactive({label: computed(() => props.label), id, required: computed(() => props.required), invalid: computed(() => Boolean(errorText.value))}));
</script>
