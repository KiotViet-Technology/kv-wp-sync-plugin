import { BranchService } from "../../services/BranchService";
import Helper from "../../utils/Helper";
import SyncSetting from "../../components/sync/SyncSetting";
import { indexDbService } from "../../services/indexDbService";
import ConfigSetting from "../../components/config/ConfigSetting";
import HelperProduct from "../../utils/HelperProduct";
import { ProductService } from "../../services/ProductService";

const state = {
	configBranchStock: [],
	configBranchOrder: []
};

const getters = {};

const actions = {
	async getAllBranch({ dispatch }, request) {
		// clear indexDb priceBook
		await utils.clearIndexDb();
		const response = await BranchService.getBranchKiotviet(request);

		let branchArr = [];

		const branch = response.data.data.data;
		branchArr.push(...branch);

		// total branch
		const total = response.data.data.total;
		if (total > 0) {
			await dispatch("insertBranchIndexDB", branch);
		}

		const numberPage = Math.ceil(total / request.pageSize);

		if (numberPage > 1) {
			for (let page = 2; page <= numberPage; page++) {
				const currentItem = 30 * page - 30;
				const data = request;
				data.currentItem = currentItem;
				const responseForPage = await BranchService.getBranchKiotviet(data);

				if (responseForPage.data.status == "success") {
					const dataResponse = responseForPage.data.data.data;
					branchArr.push(...dataResponse);
					if (dataResponse.length) {
						// insert product to indexDB
						await dispatch("insertBranchIndexDB", dataResponse);
					}
				}
			}
			// Helper.getAllDataByPaginate(branch, total, request, async data => {
			// 	const response = await BranchService.getBranchKiotviet(data);
			// 	if (response.data.status == "success") {
			// 		const branch = response.data.data.data;
			// 		if (branch.length) {
			// 			await dispatch("insertBranchIndexDB", branch);
			// 		}
			// 	}
			// });
		}
		return branchArr;
	},
	async getAllBranchKiotviet({ dispatch, commit }, request) {
		// clear indexDb priceBook
		await utils.clearIndexDb();
		const response = await BranchService.getBranchKiotviet(request);
		const branch = response.data.data.data;
		// total branch
		const total = response.data.data.total;
		if (total > 0) {
			await dispatch("insertBranchIndexDB", branch);
		}

		const numberPage = Math.ceil(total / request.pageSize);

		if (numberPage > 1) {
			Helper.getAllDataByPaginate(branch, total, request, async data => {
				const response = await BranchService.getBranchKiotviet(data);
				if (response.data.status == "success") {
					const branch = response.data.data.data;
					if (branch.length) {
						await dispatch("insertBranchIndexDB", branch);
					}

					const unitProcess = Helper.unitProcess(
						ConfigSetting.PROCESS_BAR.branchs,
						numberPage,
						1
					);

					commit("setProcessBarGetData", unitProcess, {
						root: true
					});
				}
			});

			const unitProcess = Helper.unitProcess(
				ConfigSetting.PROCESS_BAR.branchs,
				numberPage,
				1
			);

			commit("setProcessBarGetData", unitProcess, {
				root: true
			});
		} else {
			commit("setProcessBarGetData", ConfigSetting.PROCESS_BAR.branchs, {
				root: true
			});
		}
	},
	async syncBranch({ dispatch }) {
		await dispatch("saveConfig");
		// await dispatch("updateStockProduct");
	},
	async syncStock({ dispatch }) {
		await dispatch("updateStockProduct");
	},
	// eslint-disable-next-line no-unused-vars
	async removeBranchIndexDB({ commit }, branchRemovedIds) {
		await indexDbService.delete("branchs", branchRemovedIds);
	},
	// eslint-disable-next-line no-unused-vars
	async insertBranchIndexDB({ commit }, branchsKiotviet) {
		await indexDbService.add("branchs", branchsKiotviet);
	},
	async saveConfig({ commit }) {
		if (state.configBranchStock || state.configBranchOrder) {
			const request = {
				configBranchStock: JSON.stringify(state.configBranchStock),
				configBranchOrder: JSON.stringify(state.configBranchOrder)
			};
			await BranchService.saveConfig(request);
		}

		commit("setProcessBar", SyncSetting.branch.config, {
			root: true
		});
	},
	async updateStockProduct({ commit, rootState }) {
		// get all product from indexDB
		const productUpdateStock = await utils.getProductUpdateStock(rootState);
		const itemExec = 300;

		if (productUpdateStock.length) {
			await Helper.recursiveService(
				productUpdateStock.length,
				itemExec,
				productUpdateStock,
				0,
				async function(data) {
					await ProductService.updateStock(data);
					const unitProcess = Helper.unitProcess(
						SyncSetting.branch.map,
						productUpdateStock.length,
						itemExec
					);

					commit("setProcessBar", unitProcess, {
						root: true
					});
				}
			);
		} else {
			commit("setProcessBar", SyncSetting.branch.map, {
				root: true
			});
		}
	}
};

const mutations = {
	setConfigBranchStock: (state, data) => {
		state.configBranchStock = data;
	},
	setConfigBranchOrder: (state, data) => {
		state.configBranchOrder = data;
	}
};

const utils = {
	async clearIndexDb() {
		await indexDbService.clear("branchs");
	},
	async getProductUpdateStock(rootState) {
		const productIndexDb = await indexDbService.getAll("products");
		const productAdd = rootState.Product.productAdd;
		const productUpdate = rootState.Product.productUpdate;
		const products = productIndexDb
			.filter(product => {
				return product.isActive && !product.masterUnitId;
			})
			.filter(product => {
				return (
					productAdd.indexOf(product.id) == -1 &&
					productUpdate.indexOf(product.id) == -1
				);
			});

		const productUpdateStock = products.map(value => {
			const stock = HelperProduct.getStock(
				value,
				state.configBranchStock.id
			);

			return {
				productKvId: value.id,
				stock: stock
			};
		});

		return productUpdateStock;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
