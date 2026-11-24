import FgPageHeader from "@System/Components/Figma/FgPageHeader.vue";
import FgSelect from "@System/Components/Figma/FgSelect.vue";
import FgListLoading from "@System/Components/Figma/FgListLoading.vue";
import ResponsiveTable from "@/Shared/ResponsiveTable.vue";
import { createApp } from "vue"

// Components imports
import App from "./main.vue"

import InputDate     from "@System/Components/InputDate.vue";
import InputDatetime from "@System/Components/InputDatetime.vue";
import InputNumber   from "@System/Components/InputNumber.vue";
import InputSelect   from "@System/Components/InputSelect.vue";
import InputSlot     from "@System/Components/InputSlot.vue";
import InputSelect2  from "@System/Components/InputSelect2.vue";
import InputText     from "@System/Components/InputText.vue";
import InputTextArea from "@System/Components/InputTextArea.vue";
import Paginator     from "@System/Components/Paginator.vue";
import PrintSale     from "@System/Components/Sales/PrintSale.vue";
import AddCustomer   from "@System/Components/Customers/AddCustomer.vue";
import StatusBadge   from "@System/Components/Generics/StatusBadge.vue";

// App creation and mounted
createApp(App)
    .component("InputDate", InputDate)
    .component("InputDatetime", InputDatetime)
    .component("InputNumber", InputNumber)
    .component("InputSelect", InputSelect)
    .component("InputSlot", InputSlot)
    .component("InputSelect2", InputSelect2)
    .component("InputText", InputText)
    .component("InputTextArea", InputTextArea)
    .component("Paginator", Paginator)
    .component("PrintSale", PrintSale)
    .component("AddCustomer", AddCustomer)
    .component("StatusBadge", StatusBadge)
    .component("FgPageHeader", FgPageHeader)
    .component("FgSelect", FgSelect)
    .component("FgListLoading", FgListLoading)
    .component("ResponsiveTable", ResponsiveTable)
    .mount("#app");
