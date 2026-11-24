import { createApp } from "vue";
import VueSelect from "vue-select";

import Breadcrumb     from "@System/Components/Breadcrumb.vue";
import InputDate      from "@System/Components/InputDate.vue";
import InputNumber    from "@System/Components/InputNumber.vue";
import InputSelect    from "@System/Components/InputSelect.vue";
import InputSelect2   from "@System/Components/InputSelect2.vue";
import InputSlot      from "@System/Components/InputSlot.vue";
import InputText      from "@System/Components/InputText.vue";
import InputTextArea  from "@System/Components/InputTextArea.vue";
import Loader         from "@System/Components/Loader.vue";
import Paginator      from "@System/Components/Paginator.vue";
import WithoutData    from "@System/Components/WithoutData.vue";
import CopyButton     from "@System/Components/CopyButton.vue";
import FiltersSection from "@System/Components/Generics/FiltersSection.vue";
import StatusBadge    from "@System/Components/Generics/StatusBadge.vue";

const SHARED_COMPONENTS = {
    "v-select": VueSelect,
    Breadcrumb,
    InputDate,
    InputNumber,
    InputSelect,
    InputSelect2,
    InputSlot,
    InputText,
    InputTextArea,
    Loader,
    Paginator,
    WithoutData,
    CopyButton,
    FiltersSection,
    StatusBadge
};

export function mountEntityApp(App, {components = {}, mountTarget = "#app"} = {}) {

    const app = createApp(App);

    Object.entries({...SHARED_COMPONENTS, ...components}).forEach(([name, component]) => {

        app.component(name, component);

    });

    app.mount(mountTarget);

    return app;

}
