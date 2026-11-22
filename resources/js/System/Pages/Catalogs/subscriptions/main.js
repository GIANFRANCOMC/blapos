import { createApp } from "vue"

// Components imports
import App from "./main.vue"
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import Breadcrumb     from "@System/Components/Breadcrumb.vue";
import InputDate      from "@System/Components/InputDate.vue";
import InputNumber    from "@System/Components/InputNumber.vue";
import InputSelect    from "@System/Components/InputSelect.vue";
import InputSlot      from "@System/Components/InputSlot.vue";
import InputSelect2   from "@System/Components/InputSelect2.vue";
import InputText      from "@System/Components/InputText.vue";
import InputTextArea  from "@System/Components/InputTextArea.vue";
import Paginator      from "@System/Components/Paginator.vue";
import Loader         from "@System/Components/Loader.vue";
import WithoutData    from "@System/Components/WithoutData.vue";
import FiltersSection from "@System/Components/Generics/FiltersSection.vue";
import StatusBadge    from "@System/Components/Generics/StatusBadge.vue";

// App creation and mounted
createApp(App)
.component("v-select", VueSelect)
.component("Breadcrumb", Breadcrumb)
.component("InputDate", InputDate)
.component("InputNumber", InputNumber)
.component("InputSelect", InputSelect)
.component("InputSlot", InputSlot)
.component("InputSelect2", InputSelect2)
.component("InputText", InputText)
.component("InputTextArea", InputTextArea)
.component("Paginator", Paginator)
.component("Loader", Loader)
.component("WithoutData", WithoutData)
.component("FiltersSection", FiltersSection)
.component("StatusBadge", StatusBadge)
.mount("#app");
