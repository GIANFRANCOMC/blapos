<template>
    <div>
        <div>
            <component :is="showSummary ? 'span' : 'label'" :for="showSummary ? null : id" class="form-label colon-at-end">
                {{ label }}
            </component>
            <span v-if="required" class="text-danger ms-1 fw-bold" aria-hidden="true">*</span>
            <button
                v-if="showSummary"
                ref="change"
                type="button"
                class="br-link br-field-label-action p-0 ms-2 border-0 bg-transparent waves-effect"
                :disabled="disabled"
                :aria-label="`Cambiar ${label.toLowerCase()}`"
                @click="changeWarehouse">
                Cambiar
            </button>
        </div>
        <div v-if="showSummary" class="br-field-summary br-field-summary--control">
            <span class="br-field-summary__value" :title="warehouseLabel">
                <span v-if="modelValue.branchName && modelValue.warehouseName" class="br-warehouse-summary__parts">
                    <span class="br-warehouse-summary__part">{{ modelValue.branchName }}</span>
                    <span class="br-warehouse-select__separator" aria-hidden="true">-</span>
                    <span class="br-warehouse-summary__part">{{ modelValue.warehouseName }}</span>
                </span>
                <template v-else>{{ warehouseLabel }}</template>
            </span>
        </div>
        <WarehouseSelect
            v-else
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
        <small v-if="error" class="text-danger">{{ error }}</small>
    </div>
</template>

<script>
import WarehouseSelect from "./WarehouseSelect.vue";

export default {
    name: "WarehouseSelectionField",
    components: {WarehouseSelect},
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
