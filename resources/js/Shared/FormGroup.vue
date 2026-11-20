<template>
    <div class="br-form-group" :class="{'br-form-group--primary': primary, 'br-form-group--stacked': stacked}" role="group" :aria-labelledby="headingId">
        <div class="br-form-group__header">
            <span class="br-form-group__icon" aria-hidden="true"><i :class="icon"></i></span>
            <h3 :id="headingId" class="br-form-group-heading">{{ title }}</h3>
            <div v-if="$slots.actions" class="br-form-group__actions"><slot name="actions"/></div>
        </div>
        <div class="br-form-group__body">
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
