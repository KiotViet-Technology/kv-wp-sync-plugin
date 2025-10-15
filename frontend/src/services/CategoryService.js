import qs from "qs";
import ApiService from "./Api";

const CategoryService = {
	getCategoryKiotviet: async function(request) {
		const params = request || [];
		const data = {
			action: process.env.ACTION_WP_KV,
			method: "get",
			url: process.env.KIOTVIET_ENPOINT + "/categories",
			params: params
		};

		if (request.retailer) {
			data.retailer = request.retailer;
		}

		const response = await ApiService.post(qs.stringify(data), "", {
			retries: 5,
			minTimeout: 20000,
			maxTimeout: 180000
		});
		return response;
	},
	deleteCategoryMap: async function() {
		const data = qs.stringify({
			action: process.env.ACTION_WP_DELETE_CATEGORY
		});

		const response = await ApiService.post(data);
		return response;
	},
	addCategory: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_ADD_CATEGORY,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	deleteSync: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_DELETE_SYNC_CATEGORY,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	},
	updateCategoryMap: async function(request) {
		const data = qs.stringify({
			action: process.env.ACTION_WP_UPDATE_CATEGORY,
			data: request
		});

		const response = await ApiService.post(data);
		return response;
	}
};

export default CategoryService;

export { CategoryService };
