import qs from "qs";
import ApiService from "./Api";

const AuthService = {
	getAccessToken: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_GET_TOKEN,
			client_id: request.client_id,
			client_secret: request.client_secret,
			retailer: request.retailer,
			json: true
		});

		try {
			const response = await ApiService.post(data);
			return response.data;
		} catch (error) {
			return error;
		}
	},
	getConfig: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_GET_CONFIG
		});
		try {
			const response = await ApiService.post(data);
			return response.data;
		} catch (error) {
			return error;
		}
	},
	saveConfig: async function(request) {
		const data = qs.stringify({
			data: request,
			action: process.env.ACTION_WP_SAVE_CONFIG
		});

		try {
			const response = await ApiService.post(data);
			return response.data;
		} catch (error) {
			return error;
		}
	},
	removeConfig: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_REMOVE_CONFIG
		});
		try {
			const response = await ApiService.post(data);
			return response.data;
		} catch (error) {
			return error;
		}
	},
	registerWebhook: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_REGISTER_WEBHOOK
		});
		const response = await ApiService.post(data);
		return response.data;
	},
	removeWebhook: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_REMOVE_WEBHOOK
		});
		const response = await ApiService.post(data);
		return response.data;
	},
	saveConfigRetailer: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_SAVE_CONFIG_RETAILER,
			retailer: request.retailer
		});
		const response = await ApiService.post(data);
		return response.data;
	},
	removeLog: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_REMOVE_LOG
		});
		const response = await ApiService.post(data);
		return response.data;
	}
};

export default AuthService;

export { AuthService };
