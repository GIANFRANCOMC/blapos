<template>
    <slot name="before-filters"/>
    <div v-if="minimumOptions" class="col-12 col-sm-6 col-lg-2">
        <label for="stockMinimumStatus" class="form-label">Stock mínimo</label>
        <v-select id="stockMinimumStatus" v-model="minimumStatus" :options="minimumOptions" :class="selectClass" :disabled="disabled" :clearable="false" :searchable="false"/>
    </div>
    <div v-if="inactivityOptions" class="col-12 col-sm-6 col-lg-2">
        <label for="stockInactiveDays" class="form-label">Último movimiento</label>
        <v-select id="stockInactiveDays" v-model="inactiveDays" :options="inactivityOptions" :class="selectClass" :disabled="disabled" :clearable="false" :searchable="false"/>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <label for="stockCategory" class="form-label">Categoría</label>
        <v-select id="stockCategory" v-model="category" :options="categories" :class="selectClass" :disabled="disabled" :clearable="false" placeholder="Todas las categorías"/>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <label for="stockBrand" class="form-label">Marca</label>
        <v-select id="stockBrand" v-model="brand" :options="brands" :class="selectClass" :disabled="disabled" :clearable="false" placeholder="Todas las marcas"/>
    </div>
    <slot/>
</template>

<script setup>
defineProps({
    categories: {type: Array, required: true},
    brands: {type: Array, required: true},
    minimumOptions: {type: Array, default: null},
    inactivityOptions: {type: Array, default: null},
    selectClass: {type: String, required: true},
    disabled: Boolean
});

const category = defineModel("category");
const brand = defineModel("brand");
const minimumStatus = defineModel("minimumStatus");
const inactiveDays = defineModel("inactiveDays");
</script>
