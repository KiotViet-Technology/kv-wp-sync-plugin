import { CategoryService } from "../../services/CategoryService";
import { indexDbService } from "../../services/indexDbService";
import Helper from "../../utils/Helper";
import HelperCategory from "../../utils/HelperCategory";
import SyncSetting from "../../components/sync/SyncSetting";
import ConfigSetting from "../../components/config/ConfigSetting";

const state = {
	historyCategorySearch: [],
	categoryIgnoreAdd: []
};

const getters = {};

const actions = {
	async getAllCategoryKiotviet({ dispatch, commit }, request) {
		await utils.clearIndexDb();
		const response = await CategoryService.getCategoryKiotviet(request);
		const categoryKiotviet = response.data.data.data;
		// total item
		const total = response.data.data.total;
		if (total > 0) {
			await dispatch("insertCategoryIndexDB", categoryKiotviet);
		}
		const categoryRemovedId = response.data.data.removedIds;

		commit("setProcessBarGetData", ConfigSetting.PROCESS_BAR.categories, {
			root: true
		});

		// remove category in indexDB
		if (categoryRemovedId.length) {
			await CategoryService.deleteSync(categoryRemovedId);
			await dispatch("removeCategoryIndexDB", categoryRemovedId);
		}
	},
	// eslint-disable-next-line no-unused-vars
	async removeCategoryIndexDB({ commit }, categoryRemovedId) {
		await indexDbService.delete("categories", categoryRemovedId);
	},
	// eslint-disable-next-line no-unused-vars
	async insertCategoryIndexDB({ commit }, categoryKiotviet) {
		// add Product to IndexDB
		await indexDbService.add("categories", categoryKiotviet);
	},
	async syncCategory({ rootState, dispatch, commit }) {
		if (rootState.Product.configSyncProduct.includes(2)) {
			commit(
				"setProcessBar",
				parseInt(SyncSetting.category.add) +
					parseInt(SyncSetting.category.update),
				{
					root: true
				}
			);
		} else {
			// Add category not exits website
			const category = await utils.getCategory(rootState);
			if (category.length) {
				await dispatch("add", category);
			} else {
				commit("setProcessBar", SyncSetting.category.add, {
					root: true
				});
			}

			await dispatch("update");
		}
	},
	async add({ commit }, categoryAdd) {
		// splice categoryAdd
		const itemExec = 50;
		if (categoryAdd.length) {
			await Helper.recursiveService(
				categoryAdd.length,
				itemExec,
				categoryAdd,
				0,
				async function(categoryData) {
					const data = categoryData.map(value => {
						return {
							name: value.categoryName,
							parentId: value.parentId,
							categoryKvId: value.categoryId,
							dataRaw: JSON.stringify(value)
						};
					});

					await CategoryService.addCategory(data);
					const unitProcess = Helper.unitProcess(
						SyncSetting.category.add,
						categoryAdd.length,
						itemExec
					);

					commit("setProcessBar", unitProcess, {
						root: true
					});
				}
			);
		} else {
			commit("setProcessBar", SyncSetting.category.add, {
				root: true
			});
		}
	},
	async update({ commit }) {
		const categoryKiotvietAll = await HelperCategory.getCategoryByIndexDb();
		if (categoryKiotvietAll) {
			const request = categoryKiotvietAll.map(value => {
				return {
					args: {
						name: value.categoryName
					},
					categoryKv: JSON.stringify(value)
				};
			});
			await CategoryService.updateCategoryMap(request);
			commit("setProcessBar", SyncSetting.category.update, {
				root: true
			});
		} else {
			commit("setProcessBar", SyncSetting.category.update, {
				root: true
			});
		}
	}
};

const utils = {
	async clearIndexDb() {
		await indexDbService.clear("categories");
	},
	async getCategory(rootState) {
		const categoryProduct = [];
		const productKiotvietAll = await indexDbService.getAll("products");
		const productAdd = productKiotvietAll.filter(product => {
			return rootState.Product.productAdd.indexOf(product.id) !== -1;
		});

		const productUpdate = productKiotvietAll.filter(product => {
			return rootState.Product.productUpdate.indexOf(product.id) !== -1;
		});

		productAdd.forEach(product => {
			if (categoryProduct.indexOf(product.categoryId) == -1) {
				categoryProduct.push(product.categoryId);
			}
		});

		productUpdate.forEach(product => {
			if (categoryProduct.indexOf(product.categoryId) == -1) {
				categoryProduct.push(product.categoryId);
			}
		});

		const categoryKiotvietAll = await HelperCategory.getCategoryByIndexDb();
		const categories = HelperCategory.getAllParentCategory(
			categoryKiotvietAll,
			categoryProduct
		);
		return categories;
	}
};

const mutations = {
	setHistoryCategorySearch: (state, data) => {
		state.historyCategorySearch = data;
	},
	setCategoryIgnoreAdd: (state, data) => {
		state.categoryIgnoreAdd = data;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
