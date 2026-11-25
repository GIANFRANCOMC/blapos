import { createApp } from "vue";

import App from "./main.vue";
import VueSelect from "vue-select";
import "../../../../../css/System/Pages/Catalogs/recipes/recipes.css";

import InputNumber from "@System/Components/InputNumber.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";

createApp(App)
    .component("v-select", VueSelect)
    .component("InputNumber", InputNumber)
    .component("InputSlot", InputSlot)
    .component("InputText", InputText)
    .component("InputTextArea", InputTextArea)
    .component("StatusBadge", StatusBadge)
    .mount("#app");
