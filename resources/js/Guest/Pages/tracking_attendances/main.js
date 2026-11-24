import { createApp } from "vue"

// Components imports
import App from "./main.vue"
import VueSelect from "vue-select";

import Breadcrumb    from "../../Components/Breadcrumb.vue";
import InputDate     from "../../Components/InputDate.vue";
import InputDatetime from "../../Components/InputDatetime.vue";
import InputNumber   from "../../Components/InputNumber.vue";
import InputSelect   from "../../Components/InputSelect.vue";
import InputSlot     from "../../Components/InputSlot.vue";
import InputText     from "../../Components/InputText.vue";
import Paginator     from "../../Components/Paginator.vue";
import Loader        from "../../Components/Loader.vue";
import CodeScanner from "../../Components/CodeScanner.vue";
import WithoutData   from "../../Components/WithoutData.vue";

// App creation and mounted
createApp(App)
.component("v-select", VueSelect)
.component("Breadcrumb", Breadcrumb)
.component("InputDate", InputDate)
.component("InputDatetime", InputDatetime)
.component("InputNumber", InputNumber)
.component("InputSelect", InputSelect)
.component("InputSlot", InputSlot)
.component("InputText", InputText)
.component("Paginator", Paginator)
.component("Loader", Loader)
.component("CodeScanner", CodeScanner)
.component("WithoutData", WithoutData)
.mount("#app");
