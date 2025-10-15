import { ProductService } from "../../services/ProductService";
import { indexDbService } from "../../services/indexDbService";
import Helper from "../../utils/Helper";
import HelperProduct from "../../utils/HelperProduct";
import SyncSetting from "../../components/sync/SyncSetting";
import ConfigSetting from "../../components/config/ConfigSetting";

const state = {
	productAdd: [],
	productUpdate: [],
	configSyncProduct: [],
	doneSyncProduct: false,
	categoryIdSearch: 0,
	productSynced: []
};

const getters = {};

const actions = {
	async getAllProductKiotviet({ dispatch, commit }, request) {
		await utils.clearIndexDb();
		const response = await ProductService.getProductKiotviet(request);
		const productKiotviet = response.data.data.data;
		await dispatch("setImagesPro", productKiotviet);
		// total product
		const total = response.data.data.total;
		if (total > 0) {
			await dispatch("insertProductIndexDB", productKiotviet);
		}
		const productRemovedId = response.data.data.removedIds;

		const numberPage = Math.ceil(total / response.data.data.pageSize);

		if (numberPage > 1) {
			for (let page = 2; page <= numberPage; page++) {
				const currentItem = 1000 * page - 1000;
				const data = request;
				data.currentItem = currentItem;
				const responseForPage = await ProductService.getProductKiotviet(
					data
				);

				if (responseForPage.data.status == "success") {
					const dataResponse = responseForPage.data.data.data;
					await dispatch("setImagesPro", dataResponse);
					if (dataResponse.length) {
						// insert product to indexDB
						await dispatch("insertProductIndexDB", dataResponse);
					}

					const unitProcess = Helper.unitProcess(
						ConfigSetting.PROCESS_BAR.products,
						numberPage,
						1
					);

					commit("setProcessBarGetData", unitProcess, {
						root: true
					});
				}
			}

			const unitProcess = Helper.unitProcess(
				ConfigSetting.PROCESS_BAR.products,
				numberPage,
				1
			);

			commit("setProcessBarGetData", unitProcess, {
				root: true
			});
		} else {
			commit("setProcessBarGetData", ConfigSetting.PROCESS_BAR.products, {
				root: true
			});
		}

		// remove product in indexDB
		if (productRemovedId.length) {
			await dispatch("removeProductIndexDB", productRemovedId);
		}
	},
	async getProductSynced({ commit }) {
		const response = await ProductService.getProductSynced();
		commit("setProductSynced", response.data.data);
	},
	// eslint-disable-next-line no-unused-vars
	async setImagesPro({ commit }, data) {
		for (let i = 0; i < data.length; i++) {
			if (data[i].masterUnitId !== undefined) {
				const parentItem = await data.find(
					pro =>
						pro.id === data[i].masterProductId &&
						pro.masterUnitId === undefined
				);
				if (parentItem) {
					// eslint-disable-next-line no-param-reassign
					data[i].images = parentItem.images;
				}
			}
		}
	},
	// eslint-disable-next-line no-unused-vars
	async removeProductIndexDB({ commit }, productRemovedId) {
		await indexDbService.delete("products", productRemovedId);
	},
	// eslint-disable-next-line no-unused-vars
	async insertProductIndexDB({ commit }, productKiotviet) {
		await indexDbService.add("products", productKiotviet);
	},
	async addProduct({ commit, dispatch }) {
		const productAdd = await utils.getProductAdd();
		if (productAdd.length) {
			await dispatch("productVariant", {
				product: productAdd,
				action: "add",
				process: SyncSetting.product.add
			});

			await dispatch("productSimple", {
				product: productAdd,
				action: "add",
				process: SyncSetting.product.add
			});
		} else {
			commit("setProcessBar", SyncSetting.product.add, {
				root: true
			});
		}
	},
	async updateProduct({ commit, dispatch }) {
		const productUpdate = await utils.getProductUpdate();
		// Add product not exits website
		if (productUpdate.length) {
			await dispatch("productVariant", {
				product: productUpdate,
				action: "update",
				process: SyncSetting.product.update
			});

			await dispatch("productSimple", {
				product: productUpdate,
				action: "update",
				process: SyncSetting.product.update
			});
		} else {
			commit("setProcessBar", SyncSetting.product.update, {
				root: true
			});
		}
	},
	// eslint-disable-next-line no-unused-vars
	async syncProduct({ dispatch }) {
		await dispatch("addProduct");
		await dispatch("updateProduct");
	},
	async productVariant({ rootState, commit }, payload) {
		const config = utils.getConfig(rootState);
		const productAll = await indexDbService.getAll("products");

		const itemExec = 100;
		// get all product variant child
		const productVariantChild = HelperProduct.getProductChildVariant(
			productAll
		);

		const product = HelperProduct.mergeProductVariantChild(
			payload.product,
			productVariantChild
		);

		const productVariant = HelperProduct.getProductVariant(
			product,
			config.regularPrice,
			config.salePrice,
			config.branchStock
		);

		// Exec add product variant
		if (productVariant.length) {
			await Helper.recursiveService(
				productVariant.length,
				itemExec,
				productVariant,
				0,
				async function(productData) {
					const chunkSize = 20;
					// chunk data
					for (let i = 0; i < productData.length; i += chunkSize) {
						const chunk = productData.slice(i, i + chunkSize);

						if (payload.action == "add") {
							await ProductService.addProduct(JSON.stringify(chunk));
						}

						if (payload.action == "update") {
							await ProductService.updateProduct(JSON.stringify(chunk));
						}
					}

					const unitProcess = Helper.unitProcess(
						payload.process / 2,
						productVariant.length,
						itemExec
					);

					commit("setProcessBar", unitProcess, {
						root: true
					});
				}
			);
		} else {
			commit("setProcessBar", payload.process / 2, {
				root: true
			});
		}
	},
	async productSimple({ rootState, commit }, payload) {
		const itemExec = 100;
		const config = utils.getConfig(rootState);
		const productSimple = HelperProduct.getProductSimple(
			payload.product,
			config.regularPrice,
			config.salePrice,
			config.branchStock
		);

		if (productSimple.length) {
			await Helper.recursiveService(
				productSimple.length,
				itemExec,
				productSimple,
				0,
				async function(productData) {
					const chunkSize = 20;
					// chunk data
					for (let i = 0; i < productData.length; i += chunkSize) {
						const chunk = productData.slice(i, i + chunkSize);

						if (payload.action == "add") {
							await ProductService.addProduct(JSON.stringify(chunk));
						}

						if (payload.action == "update") {
							await ProductService.updateProduct(JSON.stringify(chunk));
						}
					}

					const unitProcess = Helper.unitProcess(
						payload.process / 2,
						productSimple.length,
						itemExec
					);

					commit("setProcessBar", unitProcess, {
						root: true
					});
				}
			);
		} else {
			commit("setProcessBar", payload.process / 2, {
				root: true
			});
		}
	}
};

const utils = {
	async clearIndexDb() {
		await indexDbService.clear("products");
	},
	getConfig(rootState) {
		const regularPrice = rootState.PriceBook.regularPrice
			? rootState.PriceBook.regularPrice.id
			: 0;
		const salePrice = rootState.PriceBook.salePrice
			? rootState.PriceBook.salePrice.id
			: 0;

		const branchStock = rootState.Branch.configBranchStock
			? rootState.Branch.configBranchStock.id
			: 0;

		return {
			regularPrice: regularPrice,
			salePrice: salePrice,
			branchStock: branchStock
		};
	},
	async getProductAdd() {
		// Get product add
		const productAll = await indexDbService.getAll("products");
		// splice productIgnoreAdd
		const productAdd = productAll.filter(value => {
			return state.productAdd.indexOf(value.id) !== -1;
		});
		return productAdd;
	},
	async getProductUpdate() {
		// Get product add
		const productAll = await indexDbService.getAll("products");
		// splice productIgnoreAdd
		const productUpdate = productAll.filter(value => {
			return state.productUpdate.indexOf(value.id) !== -1;
		});
		return productUpdate;
	}
};

const mutations = {
	setDoneSyncProduct: (state, data) => {
		state.doneSyncProduct = data;
	},
	setCategoryIdSearch: (state, data) => {
		state.categoryIdSearch = data;
	},
	setProductAdd: (state, data) => {
		state.productAdd = data;
	},
	setProductUpdate: (state, data) => {
		state.productUpdate = data;
	},
	setConfigSyncProduct: (state, data) => {
		state.configSyncProduct = data;
	},
	setProductSynced: (state, data) => {
		state.productSynced = data;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
