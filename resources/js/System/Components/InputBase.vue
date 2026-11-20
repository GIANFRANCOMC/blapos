<template>
    <FgField  :label="title" :required="isRequired" :input-id="resolvedControlId" :error="hasTextBottom ? textBottomInfo : null">
        <template #action><slot name="default"/><slot name="defaultAppend"/></template>
        <div :class="['fg-field__control', {'fg-field__control--invalid': hasError}]">
            <slot name="inputGroupPrepend"/>
            <input ref="inputElement" v-bind="$attrs" :id="resolvedControlId" :type="inputType" :value="modelValue" class="fg-input"
                :placeholder="placeholder" :disabled="disabled" :maxlength="maxlength" :min="min" :max="max"
                :aria-label="ariaLabel || undefined" :aria-required="isRequired || undefined" :aria-invalid="hasError || undefined"
                :aria-describedby="hasError ? resolvedControlId + '-error' : undefined" @input="updateValue($event.target)" @keyup.enter="handleEnterKey"/>
            <slot name="inputGroupAppend"/>
        </div>
    </FgField>


</template>

<script>
import FgField from "./Figma/FgField.vue";
import {useId} from "vue";
import { generalConfig } from "../Helpers/Constants.js";

export default {
    components: {FgField},
    name: "InputBase",
    inheritAttrs: false,
    setup() {
        return {controlId: useId()};
    },
    emits: ["enterKeyPressed", "update:modelValue", "input", "change"],
    props: {
        noWhitespace: Boolean,
        transformValue: Function,
        modelValue: {
            type: [String, Number],
            default: ""
        },
        inputType: {
            type: String,
            default: "text"
        },
        hasDiv: {
            type: Boolean,
            default: false
        },
        divClass: {
            type: Array,
            default: () => []
        },
        title: {
            type: String,
            default: ""
        },
        titleClass: {
            type: Array,
            default: () => ["fg-field__label"]
        },
        isRequired: {
            type: Boolean,
            default: false
        },
        requiredLabel: {
            type: String,
            default: generalConfig.forms.inputs.required
        },
        requiredClass: {
            type: Array,
            default: () => ["fg-required"]
        },
        inputClass: {
            type: Array,
            default: () => ["fg-input"]
        },
        placeholder: {
            type: String,
            default: ""
        },
        ariaLabel: {
            type: String,
            default: ""
        },
        disabled: {
            type: Boolean,
            default: false
        },
        maxlength: {
            type: [String, Number],
            default: generalConfig.forms.inputs.maxlength
        },
        min: {
            type: String,
            default: null
        },
        max: {
            type: String,
            default: null
        },
        hasTextBottom: {
            type: Boolean,
            default: false
        },
        textBottomType: {
            type: String,
            default: "first"
        },
        textBottomClass: {
            type: Array,
            default: () => [generalConfig.forms.errors.styles.default]
        },
        textBottomInfo: {
            type: Array,
            default: () => []
        },
        xl: {
            type: [String, Number],
            default: "12"
        },
        lg: {
            type: [String, Number],
            default: "12"
        },
        md: {
            type: [String, Number],
            default: "12"
        },
        sm: {
            type: [String, Number],
            default: "12"
        }
    },
    computed: {
        resolvedControlId() {
            return this.$attrs.id || this.controlId;
        },
        hasError() {
            return Boolean(this.textBottom);
        },
        textBottom() {
            try {
                return this.textBottomInfo.length > 0 ? this.textBottomInfo[0] : "";
            } catch (e) {
                return e;
            }
        },
        divSizeClass() {
            return `form-group col-xl-${this.xl} col-lg-${this.lg} col-md-${this.md} col-sm-${this.sm}`;
        }
    },
    methods: {
        focus() {

            this.$refs.inputElement?.focus();

        },
        updateValue(input) {
            const text = this.noWhitespace ? input.value.replace(/[\s\p{Z}]/gu, "") : input.value;
            const value = this.transformValue ? this.transformValue(text) : text;
            if(input.value !== value) input.value = value;

            this.$emit("update:modelValue", value);
            this.$emit("input", value);
            this.$emit("change", value);
        },
        handleEnterKey() {
            this.$emit("enterKeyPressed");
        }
    }
};
</script>

