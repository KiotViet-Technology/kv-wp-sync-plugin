import qs from "qs";
import ApiService from "./Api";

const ProductService = {
	getProductKiotviet: async function(request) {
		const params = request || [];
		const data = qs.stringify({
			action: process.env.ACTION_WP_KV,
			method: "get",
			url: process.env.KIOTVIET_ENPOINT + "/products",
			params: params
		});

		const response = await ApiService.post(data, "", {
			retries: 5,
			minTimeout: 20000,
			maxTimeout: 180000
		});
		return response;
	},
	getProductSynced: async function() {
		const data = qs.stringify({
			action: "kiotviet_sync_get_product_synced"
		});

		const response = await ApiService.post(data, "", {
			retries: 5,
			minTimeout: 20000,
			maxTimeout: 180000
		});
		return response;
	},
	deleteProductMap: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_DELETE_PRODUCT
		});

		const response = await ApiService.post(data);
		return response;
	},
	addProduct: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_ADD_PRODUCT,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	updateProduct: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_UPDATE_PRODUCT,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	getProductMap: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_GET_PRODUCT_MAP,
			product_id: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	updatePrice: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_UPDATE_PRODUCT_PRICE,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	updateStock: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_UPDATE_PRODUCT_STOCK,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	updateStatus: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_UPDATE_PRODUCT_STATUS,
			product_id: request.product_id,
			product_kv_id: request.product_kv_id,
			status: request.status
		});
		const response = await ApiService.post(data);
		return response;
	}
};

export default ProductService;

export { ProductService };
