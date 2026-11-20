<template>
    <VueSelect ref="select" v-bind="$attrs" :class="['fg-select', {'fg-select--invalid': field?.invalid}]" append-to-body :calculate-position="positionSelectDropdown" :input-id="inputId" :model-value="modelValue" @update:modelValue="$emit('update:modelValue', $event)">
        <template #search="{attributes, events}">
            <input class="vs__search" v-bind="attributes" :aria-label="label" :aria-labelledby="undefined" :aria-required="field?.required || undefined" :aria-invalid="field?.invalid || undefined" :aria-describedby="field?.invalid ? inputId + '-error' : undefined" v-on="events">
        </template>
        <template v-for="(_, name) in $slots" #[name]="scope"><slot :name="name" v-bind="scope || {}"/></template>
        <template v-if="!$slots['no-options']" #no-options><span>No hay opciones disponibles.</span></template>
    </VueSelect>
</template>
<script setup>
import {computed, inject, ref, watchPostEffect} from 'vue';
import VueSelect from 'vue-select';
import {positionSelectDropdown} from '../../Helpers/SelectOverlay.js';
defineOptions({inheritAttrs: false});
defineEmits(['update:modelValue']);
defineProps({modelValue: {default: null}});
const field = inject('fgField', null);
const select = ref(null);
const label = computed(() => field?.label || 'Selecciona una opción');
const inputId = computed(() => field?.id);
// vue-select hardcodes the English combobox name; keep this accessibility adapter in one place.
watchPostEffect(() => {
    const root = select.value?.$el;
    root?.querySelector('[role="combobox"]')?.setAttribute('aria-label', label.value);
    root?.querySelector('.vs__clear')?.setAttribute('aria-label', 'Limpiar selección');
});
</script>
