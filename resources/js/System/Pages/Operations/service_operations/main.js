import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import { createApp } from "vue";

import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputText from "@System/Components/InputText.vue";
import Paginator from "@System/Components/Paginator.vue";

createApp(App)
    .component("Breadcrumb", Breadcrumb)
    .component("InputNumber", InputNumber)
    .component("InputText", InputText)
    .component("Paginator", Paginator)
    .component("FgPageHeader", FgPageHeader)
    .component("FgSelect", FgSelect)
    .component("FgListLoading", FgListLoading)
    .component("FgEmptyState", FgEmptyState)
    .component("ResponsiveTable", ResponsiveTable)
    .mount("#app");
