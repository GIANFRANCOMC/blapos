<template>
    <VueSelect
        ref="select"
        :id="id"
        :input-id="inputId"
        :model-value="modelValue"
        :options="options"
        :get-option-key="option => option.code"
        :class="[selectClass, 'fg-warehouse-select']"
        :clearable="clearable"
        :searchable="searchable"
        :disabled="disabled"
        :placeholder="placeholder"
        :append-to-body="appendToBody"
        @update:modelValue="$emit('update:modelValue', $event)"
        @close="$emit('close')">
        <template #selected-option="warehouse">
            <span class="fg-location__value" :title="warehouse.displayLabel || warehouse.label">
                <strong v-if="warehouse.branchName" class="fg-location__branch">
                    {{ warehouse.branchName }}
                </strong>
                <span v-if="warehouse.branchName" class="fg-location__separator" aria-hidden="true">-</span>
                <span class="fg-location__name">
                    {{ warehouse.warehouseName || warehouse.label }}
                </span>
            </span>
        </template>
        <template #option="warehouse">
            <span class="fg-location__value" :title="warehouse.displayLabel || warehouse.label">
                <strong v-if="warehouse.branchName" class="fg-location__branch">
                    {{ warehouse.branchName }}
                </strong>
                <span v-if="warehouse.branchName" class="fg-location__separator" aria-hidden="true">-</span>
                <span class="fg-location__name">
                    {{ warehouse.warehouseName || warehouse.label }}
                </span>
            </span>
        </template>
        <template #no-options><SelectNoOptions/></template>
    </VueSelect>
</template>

<script>
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import SelectNoOptions from "./SelectNoOptions.vue";

export default {
    name: "WarehouseSelect",
    components: {VueSelect, SelectNoOptions},
    emits: ["update:modelValue", "close"],
    props: {
        id: {type: String, required: true},
        inputId: {type: String, default: undefined},
        modelValue: {type: Object, default: null},
        options: {type: Array, default: () => []},
        selectClass: {type: [String, Array, Object], default: ""},
        searchable: {type: Boolean, default: true},
        disabled: {type: Boolean, default: false},
        clearable: {type: Boolean, default: false},
        placeholder: {type: String, default: ""},
        appendToBody: {type: Boolean, default: false}
    },
    methods: {
        focus() {
            this.$refs.select?.searchEl?.focus();
        }
    }
};
</script>
