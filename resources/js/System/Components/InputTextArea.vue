<template>
    <template v-if="hasDiv">
        <div :class="[...divClass, divSizeClass]">
            <slot name="default"></slot>
            <label v-if="!!title" v-text="title" :class="[...titleClass]" :for="controlId"></label>
            <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
            <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
                <slot name="inputGroupPrepend"></slot>
                <textarea
                    :id="controlId"
                    :value="modelValue"
                    @input="updateValue($event.target.value)"
                    :class="[...inputClass]"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    :maxlength="maxlength"
                    :rows="rows"
                    @keyup.enter="handleEnterKey">
                </textarea>
                <slot name="inputGroupAppend"></slot>
            </div>
            <div v-if="hasTextBottom">
                <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-html="textBottom"></small>
            </div>
        </div>
    </template>
    <template v-else>
        <slot name="default"></slot>
        <label v-if="!!title" v-text="title" :class="[...titleClass]" :for="controlId"></label>
        <label v-if="isRequired" v-text="requiredLabel" :class="[...requiredClass]"></label>
        <div :class="['input-group', 'br-form-control-group', {'is-invalid': hasError}]">
            <slot name="inputGroupPrepend"></slot>
            <textarea
                :id="controlId"
                :value="modelValue"
                @input="updateValue($event.target.value)"
                :class="[...inputClass]"
                :placeholder="placeholder"
                :disabled="disabled"
                :maxlength="maxlength"
                :rows="rows"
                @keyup.enter="handleEnterKey">
            </textarea>
            <slot name="inputGroupAppend"></slot>
        </div>
        <div v-if="hasTextBottom">
            <small v-if="textBottomType === 'first'" :class="[...textBottomClass, 'br-form-error']" v-text="textBottom"></small>
        </div>
    </template>
</template>

<script>
import {useId} from "vue";
import { generalConfig } from "../Helpers/Constants.js";

export default {
    name: "InputTextArea",
    setup() {
        return {controlId: useId()};
    },
    emits: ["enterKeyPressed", "update:modelValue", "input", "change"],
    props: {
        modelValue: {
            type: [String, Number],
            default: ""
        },
        // Div - Show
        hasDiv: {
            type: Boolean,
            required: false,
            default: false
        },
        divClass: {
            type: Array,
            required: false,
            default: []
        },
        // Div - Title
        title: {
            type: String,
            required: false,
            default: ""
        },
        titleClass: {
            type: Array,
            required: false,
            default: ["form-label", "colon-at-end"]
        },
        // Input - Required
        isRequired: {
            type: Boolean,
            required: false,
            default: false
        },
        requiredLabel: {
            type: String,
            required: false,
            default: generalConfig.forms.inputs.required
        },
        requiredClass: {
            type: Array,
            required: false,
            default: ["text-danger", "ms-1", "fw-bold"]
        },
        // Input - Props
        inputClass:{
            type: Array,
            required: false,
            default: ["form-control", "no-resize"]
        },
        placeholder: {
            type: String,
            required: false,
            default: ""
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        maxlength: {
            type: [String, Number],
            required: false,
            default: generalConfig.forms.inputs.maxlength
        },
        rows: {
            type: [String, Number],
            required: false,
            default: 4
        },
        // Text Bottom
        hasTextBottom: {
            type: Boolean,
            required: false,
            default: false
        },
        textBottomType: {
            type: String,
            required: false,
            default: "first"
        },
        textBottomClass: {
            type: Array,
            required: false,
            default: [generalConfig.forms.errors.styles.default]
        },
        textBottomInfo: {
            type: Array,
            required: false,
            default: []
        },
        // Sizes
        xl: {
            type: [String, Number],
            required: false,
            default: "12"
        },
        lg: {
            type: [String, Number],
            required: false,
            default: "12"
        },
        md: {
            type: [String, Number],
            required: false,
            default: "12"
        },
        sm: {
            type: [String, Number],
            required: false,
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

            }catch(e) {

                return e;

            }

        },
        divSizeClass() {

            return `form-group col-xl-${this.xl} col-lg-${this.lg} col-md-${this.md} col-sm-${this.sm}`;

        }
    },
    methods: {
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
