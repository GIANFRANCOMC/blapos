<template>
    <InputBase
        ref="base"
        input-type="text"
        v-bind="$attrs"
        :model-value="modelValue"
        :maxlength="effectiveMaxlength"
        @update:modelValue="handleUpdate"
        @enterKeyPressed="$emit('enterKeyPressed')"
        @input="$emit('input', $event)"
        @change="$emit('change', $event)">
        <template v-for="(_, slot) in filteredSlots" #[slot]="scope">
            <slot :name="slot" v-bind="scope || {}"></slot>
        </template>
        <template #inputGroupPrepend>
            <slot name="inputGroupPrepend"></slot>
            <span v-if="shouldShowCharCounter && charCounterPosition === 'left'" :class="'fg-field__counter'">
                <span :class="'fg-field__counter-content'">
                    <span :class="'fg-field__counter-number'" :style="{ minWidth: `${maxlengthDigits}ch` }">{{ currentLength + counterOffset }}</span> / {{ maxlengthValue + counterOffset }}
                </span>
            </span>
        </template>
        <template #inputGroupAppend>
            <span v-if="shouldShowCharCounter && charCounterPosition === 'right'" :class="'fg-field__counter'">
                <span :class="'fg-field__counter-content'">
                    <span :class="'fg-field__counter-number'" :style="{ minWidth: `${maxlengthDigits}ch` }">{{ currentLength + counterOffset }}</span> / {{ maxlengthValue + counterOffset }}
                </span>
            </span>
            <slot name="inputGroupAppend"></slot>
        </template>
    </InputBase>
</template>

<script>
import InputBase from "./InputBase.vue";

export default {
    name: "InputText",
    components: { InputBase },
    inheritAttrs: false,
    emits: ["enterKeyPressed", "update:modelValue", "input", "change"],
    props: {
        counterOffset: {type: Number, default: 0},
        modelValue: {
            type: [String, Number],
            default: ""
        },
        maxlength: {
            type: [String, Number],
            default: null
        },
        showCharCounter: {
            type: Boolean,
            default: true
        },
        charCounterPosition: {
            type: String,
            default: "right",
            validator(value) {
                return ["left", "right"].includes(value);
            }
        }
    },
    computed: {
        currentLength() {

            return (this.modelValue?.toString() || "").length;

        },
        maxlengthValue() {

            const maxlengthAttr = this.maxlength || this.$attrs.maxlength;

            return maxlengthAttr ? parseInt(maxlengthAttr) : null;

        },
        effectiveMaxlength() {

            return this.maxlengthValue || this.$attrs.maxlength || undefined;

        },
        hasMaxlength() {

            return this.maxlengthValue !== null && this.maxlengthValue > 0;

        },
        shouldShowCharCounter() {

            return this.showCharCounter && this.hasMaxlength;

        },
        maxlengthDigits() {

            return this.maxlengthValue?.toString().length || 0;

        },
        filteredSlots() {

            const slots = {};

            if(this.$slots) {

                Object.keys(this.$slots).forEach(slotName => {

                    if(slotName !== "inputGroupAppend" && slotName !== "inputGroupPrepend") {

                        slots[slotName] = this.$slots[slotName];

                    }

                });

            }
            return slots;

        }
    },
    methods: {
        focus() {

            this.$refs.base?.focus();

        },
        handleUpdate(value) {

            if(this.hasMaxlength && value.length > this.maxlengthValue) {

                value = value.substring(0, this.maxlengthValue);

            }

            this.$emit("update:modelValue", value);

        }
    }
};
</script>
