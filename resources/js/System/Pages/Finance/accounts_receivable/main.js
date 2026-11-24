import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgField from "@System/Components/Figma/FgField.vue";
import FgResponsiveFilters from "@System/Components/Figma/FgResponsiveFilters.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import FgEmptyState from "@System/Components/Figma/FgEmptyState.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import InputDate from "@System/Components/InputDate.vue";
import { createApp } from "vue";

import App from "./main.vue";
import Paginator from "@System/Components/Paginator.vue";

createApp(App)
    .component("Paginator", Paginator)
    .component("FgPageHeader", FgPageHeader)
    .component("FgField", FgField)
    .component("FgResponsiveFilters", FgResponsiveFilters)
    .component("FgListLoading", FgListLoading)
    .component("FgEmptyState", FgEmptyState)
    .component("ResponsiveTable", ResponsiveTable)
    .component("InputDate", InputDate)
    .mount("#app");
