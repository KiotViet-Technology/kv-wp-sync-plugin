import { WebhookService } from "../../services/Webhook";

const state = {
	webhooks: []
};

const getters = {
	webhooks: state => state.webhooks
};

const actions = {
	async getAllProductKiotviet({ commit }) {
		const response = await WebhookService.getWebhookKiotviet();
		if (response.data && response.data.data && response.data.data.data) {
			commit("setWebhooks", response.data.data.data);
		}
	}
};

const mutations = {
	setWebhooks(state, data) {
		state.webhooks = data;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
