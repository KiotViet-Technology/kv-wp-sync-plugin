import qs from "qs";
import ApiService from "./Api";

const BranchService = {
	getBranchKiotviet: async function(request) {
		const params = request || [];
		const data = qs.stringify({
			action: process.env.ACTION_WP_KV,
			method: "get",
			url: process.env.KIOTVIET_ENPOINT + "/branches",
			params: params
		});

		const response = await ApiService.post(data, "", {
			retries: 5,
			minTimeout: 20000,
			maxTimeout: 180000
		});
		return response;
	},
	saveConfig: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_SAVE_BRANCH,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	getConfig: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_GET_BRANCH,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	}
};

export default BranchService;

export { BranchService };
