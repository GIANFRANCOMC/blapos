import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import { createApp } from "vue";

import App from "./main.vue";

import InputNumber from "@System/Components/InputNumber.vue";
import InputText from "@System/Components/InputText.vue";

createApp(App)
    .component("InputNumber", InputNumber)
    .component("InputText", InputText)
    .component("FgPageHeader", FgPageHeader)
    .component("FgSelect", FgSelect)
    .component("FgListLoading", FgListLoading)
    .component("FgEmptyState", FgEmptyState)
    .mount("#app");
