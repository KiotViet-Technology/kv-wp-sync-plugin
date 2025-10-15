// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from "vue";
import Vuex from "vuex";
// Toaster
import Toaster from "v-toaster";
import "v-toaster/dist/v-toaster.css";

// Bootstrap
import BootstrapVue from "bootstrap-vue";
import "bootstrap/dist/css/bootstrap.css";
import "bootstrap-vue/dist/bootstrap-vue.css";
import "vue-select/dist/vue-select.css";

// Vue select
import vSelect from "vue-select";

// fontawesome
import {
	faSpinner,
	faSearch,
	faTrash,
	faMinus,
	faTimes,
	faPlus,
	faLongArrowAltRight,
	faExclamationTriangle,
	faCheck,
	faAngleDown,
	faBan,
	faFileAlt,
	faCheckCircle,
	faCog,
	faTag,
	faSlidersH,
	faBox,
	faMapMarkerAlt,
	faClipboardList
} from "@fortawesome/free-solid-svg-icons";
import { library } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

import App from "./App";
import { router } from "./router";
import ApiService from "./services/Api";
import store from "./store";
import "./utils/directive";

require("./services/errorHandler");

Vue.use(BootstrapVue);
Vue.component("v-select", vSelect);
Vue.use(Toaster, {
	timeout: 4000
});

Vue.use(Vuex);

library.add(
	faSpinner,
	faSearch,
	faTrash,
	faPlus,
	faMinus,
	faLongArrowAltRight,
	faExclamationTriangle,
	faCheck,
	faAngleDown,
	faBan,
	faFileAlt,
	faCheckCircle,
	faTimes,
	faCog,
	faTag,
	faMapMarkerAlt,
	faSlidersH,
	faBox,
	faClipboardList
);
Vue.component("font-awesome-icon", FontAwesomeIcon);
Vue.config.productionTip = false;

// Set the base URL of the API
// eslint-disable-next-line no-undef
ApiService.init(wp_obj.ajaxurl);

new Vue({
	el: "#kvsync",
	store,
	router,
	components: {
		App
	},
	template: "<App/>"
});
