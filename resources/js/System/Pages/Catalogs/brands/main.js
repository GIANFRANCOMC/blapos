import {createApp} from "vue";
import VueSelect from "vue-select";
import "vue-select/dist/vue-select.css";

import App from "./main.vue";
import Breadcrumb from "@System/Components/Breadcrumb.vue";
import CopyButton from "@System/Components/CopyButton.vue";
import FiltersSection from "@System/Components/Generics/FiltersSection.vue";
import InputSlot from "@System/Components/InputSlot.vue";
import InputText from "@System/Components/InputText.vue";
import Loader from "@System/Components/Loader.vue";
import Paginator from "@System/Components/Paginator.vue";
import StatusBadge from "@System/Components/Generics/StatusBadge.vue";
import WithoutData from "@System/Components/WithoutData.vue";
import "../../../../../css/System/Components/Catalogs/catalog-list-skeleton.css";

createApp(App)
    .component("v-select", VueSelect)
    .component("Breadcrumb", Breadcrumb)
    .component("CopyButton", CopyButton)
    .component("FiltersSection", FiltersSection)
    .component("InputSlot", InputSlot)
    .component("InputText", InputText)
    .component("Loader", Loader)
    .component("Paginator", Paginator)
    .component("StatusBadge", StatusBadge)
    .component("WithoutData", WithoutData)
    .mount("#app");
