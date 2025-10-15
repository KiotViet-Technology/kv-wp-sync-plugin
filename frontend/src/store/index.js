import Vue from "vue";
import Vuex from "vuex";
import Category from "./modules/Category";
import Product from "./modules/Product";
import Step from "./modules/Step";
import PriceBook from "./modules/PriceBook";
import Branch from "./modules/Branch";
import Sync from "./modules/Sync";
import Webhook from "./modules/Webhook";

Vue.use(Vuex);

const debug = process.env.NODE_ENV !== "production";
const state = {
	processBar: 0,
	processBarGetData: 0,
	fastSync: false,
	configKiotviet: [],
	branch: null
};

const actions = {
	execProcessBar({ commit }, totalItem, value) {
		const unit = value / totalItem;
		commit("setProcessBar", unit);
	}
};

const mutations = {
	setProcessBar(state, data) {
		state.processBar += data;
	},
	resetProcessBar(state, data) {
		state.processBar = data;
	},
	setProcessBarGetData(state, data) {
		state.processBarGetData += data;
	},
	resetProcessBarGetData(state, data) {
		state.processBarGetData = data;
	},
	setFastSync(state, data) {
		state.fastSync = data;
	},
	setConfigKiotviet(state, data) {
		state.configKiotviet = data;
	},
	setBranch(state, data) {
		state.branch = data;
	}
};

export default new Vuex.Store({
	state,
	mutations,
	actions,
	modules: {
		Category,
		Product,
		Step,
		PriceBook,
		Branch,
		Sync,
		Webhook
	},
	strict: debug
});
