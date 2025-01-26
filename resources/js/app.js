import "./bootstrap";
import "../css/app.css";
import "../scss/app.scss";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/dist/vue.m";

import PrimeVue from "primevue/config";
import ToastService from "primevue/toastservice";
import Button from "primevue/button";
import ToggleButton from "primevue/togglebutton";
import SplitButton from 'primevue/splitbutton';
import RadioButton from "primevue/radiobutton";
import CheckBox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Textarea from "primevue/textarea";
import Dropdown from "primevue/dropdown";
import ProgressBar from "primevue/progressbar";
import MultiSelect from "primevue/multiselect";
import FileUpload from "primevue/fileupload";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Toast from "primevue/toast";
import Message from 'primevue/message';
import Sidebar from "primevue/sidebar";
import InputSwitch from "primevue/inputswitch";
import Dialog from "primevue/dialog";
import Tooltip from "primevue/tooltip";
import Chips from 'primevue/chips';
import Chip from 'primevue/chip';

import "primevue/resources/primevue.min.css";
// import "primevue/resources/themes/bootstrap4-light-blue/theme.css"
// const current = route().current().split('.')[0];
// if(current ==  'user') {
//     // import('primevue/resources/themes/lara-light-green/theme.css');
//     import('primevue/resources/themes/saga-green/theme.css');
// } else {
// }
import("primevue/resources/themes/bootstrap4-light-blue/theme.css");
import "primeicons/primeicons.css";

// config fontawesome
import { library } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { fas } from "@fortawesome/free-solid-svg-icons";
import { far } from "@fortawesome/free-regular-svg-icons";
import { fab } from "@fortawesome/free-brands-svg-icons";
library.add(fas, far, fab);

import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

// import custom components
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AdminLayout from "@/Layouts/Admin/AdminLayout.vue";
import MainContent from "@/Layouts/Admin/MainContent.vue";
import ContentBox from "@/Layouts/Admin/ContentBox.vue";
import MasterContentBox from "@/Layouts/Admin/MasterContentBox.vue";
import MasterCreateForm from "@/Layouts/Admin/MasterCreateForm.vue";
import MasterDetailBox from "@/Layouts/Admin/MasterDetailBox.vue";
import MasterEditBox from "@/Layouts/Admin/MasterEditBox.vue";
import InputLabel from "./Components/InputLabel.vue";
import InputError from "./Components/InputError.vue";
import SmallLabel from "./Components/SmallLabel.vue";
import Spinner from "./Components/Spinner.vue";
import CustomToast from "./Components/CustomToast.vue";
import Loader from "./Components/Loader.vue";
import LinkPagination from "./Components/LinkPagination.vue";
import Pagination from "./Components/Pagination.vue";
import ContextMenu from 'primevue/contextmenu';

const appName =
    window.document.getElementsByTagName("title")[0]?.innerText || "TimePals";

createInertiaApp({
    title: (title) => `${appName} | ${title}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(PrimeVue, { ripple: true })
            .use(ToastService)
            .directive("tooltip", Tooltip)
            .component("FontAwesomeIcon", FontAwesomeIcon)
            .component("Button", Button)
            .component("ToggleButton", ToggleButton)
            .component("SplitButton", SplitButton)
            .component("InputText", InputText)
            .component("InputNumber", InputNumber)
            .component("Textarea", Textarea)
            .component("Dropdown", Dropdown)
            .component("MultiSelect", MultiSelect)
            .component("FileUpload", FileUpload)
            .component("DataTable", DataTable)
            .component("Column", Column)
            .component("Chips", Chips)
            .component("Chip", Chip)
            .component("Toast", Toast)
            .component("Message", Message)
            .component("Sidebar", Sidebar)
            .component("InputSwitch", InputSwitch)
            .component("VueDatePicker", VueDatePicker)
            .component("RadioButton", RadioButton)
            .component("CheckBox", CheckBox)
            .component("Dialog", Dialog)
            .component("ProgressBar", ProgressBar)
            .component("AuthenticatedLayout", AuthenticatedLayout)
            .component("AdminLayout", AdminLayout)
            .component("MainContent", MainContent)
            .component("ContentBox", ContentBox)
            .component("MasterContentBox", MasterContentBox)
            .component("MasterCreateForm", MasterCreateForm)
            .component("MasterDetailBox", MasterDetailBox)
            .component("MasterEditBox", MasterEditBox)
            .component("InputLabel", InputLabel)
            .component("InputError", InputError)
            .component("SmallLabel", SmallLabel)
            .component("Loader", Loader)
            .component("CustomToast", CustomToast)
            .component("Spinner", Spinner)
            .component("LinkPagination", LinkPagination)
            .component("Pagination", Pagination)
            .component("ContextMenu", ContextMenu)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
