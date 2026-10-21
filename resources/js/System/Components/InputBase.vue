<template>
    <template v-if="hasDiv">
        <div :class="[...divClass, divSizeClass]">
            <slot name="default"></slot>
            <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
            <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
            <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
                <slot name="inputGroupPrepend"></slot>
                <input
                    ref="inputElement"
                    :type="inputType"
                    :value="modelValue"
                    @input="updateValue($event.target.value)"
                    :class="[...inputClass]"
                    :aria-label="ariaLabel || undefined"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    :maxlength="maxlength"
                    :min="min"
                    :max="max"
                    @keyup.enter="handleEnterKey"/>
                <slot name="inputGroupAppend"></slot>
            </div>
            <div v-if="hasTextBottom">
                <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-html="textBottom"></small>
            </div>
        </div>
    </template>
    <template v-else>
        <slot name="default"></slot>
        <label v-if="!!title" v-text="title" :class="[...titleClass]"></label>
        <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
        <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
            <slot name="inputGroupPrepend"></slot>
            <input
                ref="inputElement"
                :type="inputType"
                :value="modelValue"
                @input="updateValue($event.target.value)"
                :class="[...inputClass]"
                :aria-label="ariaLabel || undefined"
                :placeholder="placeholder"
                :disabled="disabled"
                :maxlength="maxlength"
                :min="min"
                :max="max"
                @keyup.enter="handleEnterKey"/>
            <slot name="inputGroupAppend"></slot>
        </div>
        <div v-if="hasTextBottom">
            <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-text="textBottom"></small>
        </div>
    </template>
</template>

<script>
import { generalConfig } from "../Helpers/Constants.js";

export default {
    name: "InputBase",
    emits: ["enterKeyPressed", "update:modelValue", "input", "change"],
    props: {
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
            default: () => ["form-label", "colon-at-end"]
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
            default: () => ["text-danger", "ms-1", "fw-bold"]
        },
        inputClass: {
            type: Array,
            default: () => ["form-control"]
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
        updateValue(value) {
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

