const state = {
	currentStep: 1,
	lastStep: 0
};

const getters = {};

const actions = {};

const mutations = {
	setCurrentStep(state, data) {
		state.currentStep = data;
	},
	nextStep(state) {
		state.currentStep++;
	},
	backStep(state) {
		state.currentStep--;
	},
	setLastStep(state, data) {
		state.lastStep = data;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
