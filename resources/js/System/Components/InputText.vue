<template>
    <InputBase
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
            <span v-if="shouldShowCharCounter && charCounterPosition === 'left'" class="input-group-text character-counter br-character-counter" :class="[isNearLimit ? 'character-counter-warning' : 'character-counter-normal', 'character-counter-left']">
                <span class="br-character-counter__content">
                    <span class="counter-number" :class="{ 'counter-update': isUpdating }" :style="{ minWidth: `${maxlengthDigits}ch` }">{{ currentLength }}</span>/{{ maxlengthValue }}
                </span>
            </span>
        </template>
        <template #inputGroupAppend>
            <span v-if="shouldShowCharCounter && charCounterPosition === 'right'" class="input-group-text character-counter br-character-counter" :class="[isNearLimit ? 'character-counter-warning' : 'character-counter-normal', 'character-counter-right']">
                <span class="br-character-counter__content">
                    <span class="counter-number" :class="{ 'counter-update': isUpdating }" :style="{ minWidth: `${maxlengthDigits}ch` }">{{ currentLength }}</span>/{{ maxlengthValue }}
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
            default: false
        },
        charCounterPosition: {
            type: String,
            default: "right",
            validator(value) {
                return ["left", "right"].includes(value);
            }
        }
    },
    data() {
        return {
            isUpdating: false
        };
    },
    watch: {
        currentLength(newValue, oldValue) {

            if(newValue !== oldValue && this.shouldShowCharCounter) {

                this.isUpdating = true;

                setTimeout(() => {

                    this.isUpdating = false;

                }, 150);

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
        isNearLimit() {

            if(!this.hasMaxlength) return false;

            return (this.currentLength / this.maxlengthValue) * 100 >= 90;

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
        handleUpdate(value) {

            if(this.hasMaxlength && value.length > this.maxlengthValue) {

                value = value.substring(0, this.maxlengthValue);

            }

            this.$emit("update:modelValue", value);

        }
    }
};
</script>

<style scoped>
.character-counter {
    display: inline-flex;
    align-self: stretch;
    align-items: center;
    justify-content: center;
    min-width: 3.2rem;
    padding: 0 0.32rem;
    white-space: nowrap;
    cursor: default;
    user-select: none;
    background-color: var(--br-surface-elevated, #ffffff);
    transition: border-color 0.2s ease, color 0.2s ease;
    margin: 0;
    box-sizing: border-box;
}
.character-counter-left {
    border-right: 1px solid #dee2e6;
}
.character-counter-right {
    border-left: 1px solid #dee2e6;
}

.character-counter-normal {
    border-color: var(--bs-border-color, var(--br-border, #dee2e6));
    background-color: var(--br-surface-elevated, #ffffff);
}

.character-counter-warning {
    border-color: color-mix(in srgb, var(--br-warning, #f59e0b) 32%, var(--br-border, #dee2e6));
    background-color: var(--br-surface-elevated, #ffffff);
    animation: pulse-subtle 2s ease-in-out infinite;
}

.br-character-counter__content {
    display: inline-flex;
    align-items: baseline;
    justify-content: center;
    color: inherit;
    font-size: inherit;
    font-weight: inherit;
    line-height: 1;
    letter-spacing: 0;
}

.character-counter-warning .br-character-counter__content {
    color: var(--br-warning-hover, #d97706);
    font-weight: 650;
}

.counter-number {
    display: inline-block;
    font-weight: inherit;
    transition: opacity 0.15s ease;
    text-align: right;
}

.counter-update {
    animation: counter-highlight 0.15s ease;
}

@keyframes counter-highlight {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
    100% {
        opacity: 1;
    }
}

@keyframes pulse-subtle {
    0%, 100% {
        opacity: 0.95;
    }
    50% {
        opacity: 1;
    }
}
</style>
