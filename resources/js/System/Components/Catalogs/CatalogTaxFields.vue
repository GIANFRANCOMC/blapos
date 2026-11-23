<template>
    <div class="fg-grid--checks">
        <label class="fg-check">
            <input v-model="priceIncludesTax" type="checkbox" :disabled="disabled || igvExempt"/>
            <span>Precio incluye IGV</span>
        </label>
        <label class="fg-check">
            <input v-model="igvExempt" type="checkbox" :disabled="disabled" @change="clearIncludedTax"/>
            <span>Exonerado de IGV</span>
        </label>
    </div>
</template>

<script setup>
defineProps({disabled: Boolean});
const priceIncludesTax = defineModel("priceIncludesTax", {type: Boolean});
const igvExempt = defineModel("igvExempt", {type: Boolean});

function clearIncludedTax(event) {
    if(event.target.checked) priceIncludesTax.value = false;
}
</script>
