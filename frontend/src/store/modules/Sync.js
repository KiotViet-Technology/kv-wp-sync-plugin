const state = {
	doneSync: false
};

const getters = {};

const actions = {};

const mutations = {
	setDoneSync(state, data) {
		state.doneSync = data;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
