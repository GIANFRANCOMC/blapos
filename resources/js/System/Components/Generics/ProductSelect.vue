<template>
    <VueSelect
        :model-value="modelValue"
        :options="options"
        :class="selectClass"
        :clearable="false"
        :searchable="true"
        :disabled="disabled"
        append-to-body
        @update:modelValue="$emit('update:modelValue', $event)">
        <template #no-options><SelectNoOptions/></template>
    </VueSelect>
</template>

<script>
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import SelectNoOptions from "./SelectNoOptions.vue";

export default {
    name: "ProductSelect",
    components: {VueSelect, SelectNoOptions},
    emits: ["update:modelValue"],
    props: {
        modelValue: {type: Object, default: null},
        options: {type: Array, default: () => []},
        selectClass: {type: [String, Array, Object], default: ""},
        disabled: {type: Boolean, default: false}
    }
};
</script>
