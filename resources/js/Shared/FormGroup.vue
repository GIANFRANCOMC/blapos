<template>
    <div class="fg-form-group" :class="{'fg-form-group--primary': primary, 'fg-form-group--stacked': stacked}" role="group" :aria-labelledby="headingId">
        <div class="fg-form-group__header">
            <span class="fg-form-group__icon" aria-hidden="true"><i :class="icon"></i></span>
            <h3 :id="headingId" class="fg-form-group-heading">{{ title }}</h3>
            <div v-if="$slots.actions" class="fg-form-group__actions"><slot name="actions"/></div>
        </div>
        <div class="fg-form-group__body">
            <slot/>
        </div>
    </div>
</template>

<script>
import {useId} from "vue";

export default {
    name: "FormGroup",
    props: {
        title: {type: String, required: true},
        icon: {type: String, required: true},
        primary: {type: Boolean, default: false},
        stacked: {type: Boolean, default: false}
    },
    setup() {
        return {headingId: `form-group-${useId()}`};
    }
};
</script>
