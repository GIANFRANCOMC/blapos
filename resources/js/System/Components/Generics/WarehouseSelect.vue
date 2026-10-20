<template>
    <VueSelect
        :id="id"
        :model-value="modelValue"
        :options="options"
        :get-option-key="option => option.code"
        :class="[selectClass, 'br-warehouse-select']"
        :clearable="false"
        :searchable="searchable"
        :disabled="disabled"
        :placeholder="placeholder"
        :append-to-body="appendToBody"
        @update:modelValue="$emit('update:modelValue', $event)">
        <template #selected-option="warehouse">
            <span class="br-warehouse-select__value" :title="warehouse.displayLabel || warehouse.label">
                <strong v-if="warehouse.branchName" class="br-warehouse-select__branch">
                    {{ warehouse.branchName }}
                </strong>
                <span v-if="warehouse.branchName" class="br-warehouse-select__separator" aria-hidden="true">-</span>
                <span class="br-warehouse-select__name">
                    {{ warehouse.warehouseName || warehouse.label }}
                </span>
            </span>
        </template>
        <template #option="warehouse">
            <span class="br-warehouse-select__value" :title="warehouse.displayLabel || warehouse.label">
                <strong v-if="warehouse.branchName" class="br-warehouse-select__branch">
                    {{ warehouse.branchName }}
                </strong>
                <span v-if="warehouse.branchName" class="br-warehouse-select__separator" aria-hidden="true">-</span>
                <span class="br-warehouse-select__name">
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
    emits: ["update:modelValue"],
    props: {
        id: {type: String, required: true},
        modelValue: {type: Object, default: null},
        options: {type: Array, default: () => []},
        selectClass: {type: [String, Array, Object], default: ""},
        searchable: {type: Boolean, default: true},
        disabled: {type: Boolean, default: false},
        placeholder: {type: String, default: ""},
        appendToBody: {type: Boolean, default: false}
    }
};
</script>
