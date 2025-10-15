import Vue from "vue";
import axios from "axios";
import { router } from "../router";

axios.interceptors.response.use(
	function(response) {
		if (response.data.status == "error") {
			if (response.config.toast_error !== false) {
				Vue.toaster.error(response.data.message);
			}

			if (response.data.errorCode == 100) {
				router.push({
					name: "Login"
				});
			}

			// throw response.data.message;
		}

		return response;
	},
	// eslint-disable-next-line no-unused-vars
	function(error) {
		// handle error
		if (error.config.toast_error === false) {
			return;
		}
		Vue.toaster.error("Kết nối dữ liệu thất bại !");
	}
);
