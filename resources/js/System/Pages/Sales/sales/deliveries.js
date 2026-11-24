import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgResponsiveFilters from "@System/Components/Figma/FgResponsiveFilters.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import { createApp } from "vue";
import App from "./deliveries.vue";

import InputDate from "@System/Components/InputDate.vue";
import InputNumber from "@System/Components/InputNumber.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import Paginator from "@System/Components/Paginator.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";

createApp(App)
    .component("InputDate", InputDate)
    .component("InputNumber", InputNumber)
    .component("InputSlot", InputSlot)
    .component("InputText", InputText)
    .component("InputTextArea", InputTextArea)
    .component("Paginator", Paginator)
    .component("StatusBadge", StatusBadge)
    .component("FgPageHeader", FgPageHeader)
    .component("FgSelect", FgSelect)
    .component("FgResponsiveFilters", FgResponsiveFilters)
    .component("FgListLoading", FgListLoading)
    .component("FgEmptyState", FgEmptyState)
    .component("ResponsiveTable", ResponsiveTable)
    .mount("#app");
