import qs from "qs";
import ApiService from "./Api";

const WebhookService = {
	getWebhookKiotviet: async function(request) {
		const params = request || [];
		const data = qs.stringify({
			action: process.env.ACTION_WP_KV,
			method: "get",
			url: process.env.KIOTVIET_ENPOINT + "/webhooks",
			params: params
		});

		const response = await ApiService.post(data);
		return response;
	}
};

export default WebhookService;

export { WebhookService };
