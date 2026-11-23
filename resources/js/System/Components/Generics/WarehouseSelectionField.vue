<template>
    <div v-if="showSummary" class="fg-field">
        <div class="fg-field__label-row">
            <component :is="showSummary ? 'span' : 'label'" :for="showSummary ? null : id" class="fg-field__label">
                {{ label }}
                <span v-if="required" class="fg-required" aria-hidden="true">*</span>
            </component>
            <button
                v-if="showSummary"
                ref="change"
                type="button"
                class="fg-link"
                :disabled="disabled"
                :aria-label="`Cambiar ${label.toLowerCase()}`"
                @click="changeWarehouse">
                Cambiar
            </button>
        </div>
        <div v-if="showSummary" class="fg-field__control">
            <span class="fg-selection-summary__value" :title="warehouseLabel">
                <span v-if="modelValue.branchName && modelValue.warehouseName" class="fg-location">
                    <span class="fg-location__name">{{ modelValue.branchName }}</span>
                    <span class="fg-location__separator" aria-hidden="true">-</span>
                    <span class="fg-location__name">{{ modelValue.warehouseName }}</span>
                </span>
                <template v-else>{{ warehouseLabel }}</template>
            </span>
        </div>
    </div>
    <FgField v-else :label="label" :input-id="id" :required="required" :error="error">
        <WarehouseSelect
            ref="select"
            :id="`${id}Select`"
            :input-id="id"
            :model-value="modelValue"
            :options="options"
            :select-class="selectClass"
            :placeholder="placeholder"
            :disabled="disabled"
            :append-to-body="appendToBody"
            @update:model-value="selectWarehouse"
            @close="editing = false"
            @keydown.esc.stop="cancelChange"/>
    </FgField>
</template>

<script>
import WarehouseSelect from "./WarehouseSelect.vue";
import FgField from "@System/Components/Figma/FgField.vue";

export default {
    name: "WarehouseSelectionField",
    components: {WarehouseSelect, FgField},
    emits: ["update:modelValue"],
    props: {
        id: {type: String, required: true},
        label: {type: String, required: true},
        modelValue: {type: Object, default: null},
        options: {type: Array, default: () => []},
        selectClass: {type: [String, Array, Object], default: ""},
        placeholder: {type: String, default: ""},
        disabled: {type: Boolean, default: false},
        appendToBody: {type: Boolean, default: false},
        summarizeSelection: {type: Boolean, default: true},
        required: {type: Boolean, default: false},
        error: {type: String, default: ""}
    },
    data() {
        return {editing: false};
    },
    computed: {
        showSummary() {
            return this.summarizeSelection && Boolean(this.modelValue?.code) && !this.editing;
        },
        warehouseLabel() {
            const {displayLabel, label, branchName, warehouseName} = this.modelValue ?? {};
            return displayLabel || label || [branchName, warehouseName].filter(Boolean).join(" - ");
        }
    },
    watch: {
        modelValue() {
            this.editing = false;
        }
    },
    methods: {
        changeWarehouse() {
            this.editing = true;
            this.$nextTick(() => this.$refs.select?.focus());
        },
        selectWarehouse(warehouse) {
            this.editing = false;
            this.$emit("update:modelValue", warehouse);
            this.$nextTick(() => this.$refs.change?.focus());
        },
        cancelChange() {
            if(!this.editing) return;

            this.editing = false;
            this.$nextTick(() => this.$refs.change?.focus());
        }
    }
};
</script>
