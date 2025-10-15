import axios from "axios";
import retry from "async-retry";
import { StorageService } from "./Storage";

const ApiService = {
	init(baseURL) {
		axios.defaults.baseURL = baseURL;
	},
	setHeader() {
		axios.defaults.headers.common[
			"Authorization"
		] = `Bearer ${StorageService.getItem("access_token")}`;
	},
	removeHeader() {
		axios.defaults.headers.common = {};
	},
	get(resource = "") {
		return axios.get(resource);
	},
	post(data, resource = "", retryConfig) {
		if (!retryConfig) {
			return axios.post(resource, data);
		}

		return retry(async (bail, number) => {
			const response = await axios.post(resource, data, {
				toast_error: number > retryConfig.retries
			});
			if (!response) {
				throw new Error("error");
			}

			if (response.data.status == "error") {
				console.warn(response.data);
				throw new Error(response.data.message);
			}

			return response;
		}, retryConfig);
	},
	put(data, resource = "") {
		return axios.put(resource, data);
	},
	delete(resource = "") {
		return axios.delete(resource);
	},
	customRequest(data) {
		return axios(data);
	}
};

export default ApiService;
