<template>
    <FgField :label="title" :required="isRequired" :input-id="id" :error="hasTextBottom ? textBottomInfo : null" :help="help">
        <template #action><slot name="default"/></template>
        <template #default="{describedBy, invalid}">
            <div class="fg-select2">
                <slot name="inputGroupPrepend"/>
                <select ref="select" :id="id" class="fg-input" :disabled="disabled" :multiple="multiple" :aria-required="isRequired || undefined" :aria-invalid="invalid || undefined" :aria-describedby="describedBy">
                    <option v-if="hasDefaultOption" :value="defaultOptionValue">{{ defaultOptionLabel }}</option>
                    <option v-for="(option,index) in options" :key="option.code ?? index" :value="option.code" :extras="option?.extras">{{ option.label }}</option>
                </select>
                <slot name="inputGroupAppend"/>
            </div>
        </template>
    </FgField>
</template>
<script>
import InputSelect2 from "@/Shared/InputSelect2.vue";
import FgField from "./Figma/FgField.vue";
// El adaptador conserva modelos, consultas remotas, cancelación y ciclo de vida de Select2.
export default {extends: InputSelect2, name: "InputSelect2", components: {FgField}, props: {help: {type: String, default: ""}}};
</script>
