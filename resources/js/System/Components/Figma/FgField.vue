<template>
    <div class="fg-field">
        <div class="fg-field__label-row">
            <label :for="id" class="fg-field__label">{{ label }}<span v-if="required" class="fg-required" aria-hidden="true">*</span></label>
            <div v-if="$slots.action" class="fg-field__actions"><slot name="action"/></div>
        </div>
        <slot :id="id" :invalid="Boolean(errorText)" :error-id="errorText ? `${id}-error` : undefined" :described-by="describedBy"/>
        <small v-if="errorText" :id="`${id}-error`" class="fg-field__message fg-error" role="alert">{{ errorText }}</small>
        <small v-if="help" :id="`${id}-help`" class="fg-field__message fg-help">{{ help }}</small>
    </div>
</template>
<script setup>
import {computed, useId, provide, reactive} from 'vue';
const props = defineProps({label: String, required: Boolean, error: [String, Array], help: String, inputId: String});
const uid = useId();
const id = computed(() => props.inputId || `fg-field-${uid}`);
const errorText = computed(() => Array.isArray(props.error) ? props.error[0] : props.error);
const describedBy = computed(() => [errorText.value && `${id.value}-error`, props.help && `${id.value}-help`].filter(Boolean).join(' ') || undefined);
provide('fgField', reactive({label: computed(() => props.label), id, required: computed(() => props.required), invalid: computed(() => Boolean(errorText.value)), describedBy}));
</script>
