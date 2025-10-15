import { PriceBookService } from "../../services/PriceBookService";
import SyncSetting from "../../components/sync/SyncSetting";
import Helper from "../../utils/Helper";
import ConfigSetting from "../../components/config/ConfigSetting";
import { indexDbService } from "../../services/indexDbService";
import HelperProduct from "../../utils/HelperProduct";
import { ProductService } from "../../services/ProductService";

const state = {
	regularPrice: 0,
	salePrice: 0
};

const getters = {};

const actions = {
	async getAllPriceBookKiotviet({ dispatch, commit }, request) {
		// clear indexDb priceBook
		await utils.clearIndexDb();
		const response = await PriceBookService.getPriceBookKiotviet(request);
		const priceBook = response.data.data.data;
		// total priceBook
		const total = response.data.data.total;
		if (total > 0) {
			await dispatch("insertPriceBookIndexDB", priceBook);
		}

		const numberPage = Math.ceil(total / request.pageSize);
		if (numberPage > 1) {
			Helper.getAllDataByPaginate(priceBook, total, request, async data => {
				const customResponse = await PriceBookService.getPriceBookKiotviet(
					data
				);

				if (customResponse.data.status == "success") {
					const dataResponse = customResponse.data.data.data;
					if (dataResponse.length) {
						// insert product to indexDB
						await dispatch("insertPriceBookIndexDB", dataResponse);
					}

					const unitProcess = Helper.unitProcess(
						ConfigSetting.PROCESS_BAR.priceBooks,
						numberPage,
						1
					);

					commit("setProcessBarGetData", unitProcess, {
						root: true
					});
				}
			});

			const unitProcess = Helper.unitProcess(
				ConfigSetting.PROCESS_BAR.priceBooks,
				numberPage,
				1
			);

			commit("setProcessBarGetData", unitProcess, {
				root: true
			});
		} else {
			commit("setProcessBarGetData", ConfigSetting.PROCESS_BAR.priceBooks, {
				root: true
			});
		}

		// insert Bảng giá chung
		await utils.insertPriceBookGeneral(dispatch);
	},
	async syncPriceBook({ dispatch }) {
		await dispatch("saveConfig");
		await dispatch("updatePriceProduct");
	},
	// eslint-disable-next-line no-unused-vars
	async insertPriceBookIndexDB({ commit }, priceBooksKiotviet) {
		await indexDbService.add("priceBooks", priceBooksKiotviet);
	},
	// eslint-disable-next-line no-unused-vars
	async removeBranchIndexDB({ commit }, priceBookRemoveIds) {
		await indexDbService.delete("priceBooks", priceBookRemoveIds);
	},
	async saveConfig({ commit }) {
		const configPriceBook = utils.getConfigPriceBook();
		const request = {
			regularPrice: JSON.stringify({
				id: configPriceBook.regularPriceBookId,
				name: configPriceBook.regularPriceBookName
			}),
			salePrice: JSON.stringify({
				id: configPriceBook.salePriceBookId,
				name: configPriceBook.salePriceBookName
			})
		};

		await PriceBookService.saveConfig(request);
		commit("setProcessBar", SyncSetting.priceBook.config, {
			root: true
		});
	},
	async updatePriceProduct({ commit, rootState }) {
		// get all product from indexDB
		const productUpdatePrice = await utils.getProductUpdatePrice(rootState);
		const itemExec = 300;
		if (productUpdatePrice.length) {
			await Helper.recursiveService(
				productUpdatePrice.length,
				itemExec,
				productUpdatePrice,
				0,
				async function(data) {
					for (let k = 0; k < data.length; k++) {
						if (data[k].productKvId == 26008132) {
							console.log(data[k]);
						}
					}
					await ProductService.updatePrice(data);
					const unitProcess = Helper.unitProcess(
						SyncSetting.priceBook.map,
						productUpdatePrice.length,
						itemExec
					);

					commit("setProcessBar", unitProcess, {
						root: true
					});
				}
			);
		} else {
			commit("setProcessBar", SyncSetting.priceBook.map, {
				root: true
			});
		}
	}
};

const mutations = {
	setRegularPrice: (state, data) => {
		state.regularPrice = data;
	},
	setSalePrice: (state, data) => {
		state.salePrice = data;
	}
};

const utils = {
	async insertPriceBookGeneral(dispatch) {
		const priceBookGeneral = [
			{
				id: -1,
				name: "Bảng giá chung"
			}
		];

		await dispatch("insertPriceBookIndexDB", priceBookGeneral);
	},
	async clearIndexDb() {
		await indexDbService.clear("priceBooks");
	},
	getConfigPriceBook() {
		return {
			regularPriceBookId: state.regularPrice ? state.regularPrice.id : "",
			regularPriceBookName: state.regularPrice
				? state.regularPrice.name
				: "",
			salePriceBookId: state.salePrice ? state.salePrice.id : "",
			salePriceBookName: state.salePrice ? state.salePrice.name : ""
		};
	},
	async getProductUpdatePrice(rootState) {
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

		const config = this.getConfigPriceBook();
		const productUpdatePrice = products.map(value => {
			const regularPrice = HelperProduct.getRegularPrice(
				value,
				config.regularPriceBookId
			);

			const salePrice = HelperProduct.getSalePrice(
				value,
				config.salePriceBookId
			);

			return {
				productKvId: value.id,
				regularPrice: regularPrice,
				salePrice: salePrice
			};
		});

		return productUpdatePrice;
	}
};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
