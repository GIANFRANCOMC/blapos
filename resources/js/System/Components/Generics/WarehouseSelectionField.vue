<template>
    <div class="fg-field">
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
        <small v-if="error" class="fg-error">{{ error }}</small>
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
