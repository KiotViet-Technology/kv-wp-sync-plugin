import map from "lodash.map";
import filter from "lodash.filter";
import unionBy from "lodash.unionby";
import { indexDbService } from "../services/indexDbService";

export default {
	output: {},
	recursionMenu: function(data, parentId = 0, char = "", result = []) {
		data.forEach(value => {
			if (value.parentId == parentId) {
				const item = value;
				item.char = char;
				result.push(item);
				this.recursionMenu(data, item.categoryId, char + "--", result);
			}
		});

		return result;
	},
	getNestedChildren: function(arr, parent = 0) {
		const out = [];
		arr.forEach(value => {
			const item = value;
			if (item.parentId == parent) {
				const children = this.getNestedChildren(arr, item.categoryId);
				if (children.length) {
					item.children = children;
				} else {
					item.children = [];
				}
				out.push(item);
			}
		});

		return out;
	},
	getArrayByMenuId: function(arr, id) {
		arr.forEach(value => {
			if (value.categoryId == id) {
				this.output = value;
			} else {
				if (value.children.length) {
					this.getArrayByMenuId(value.children, id);
				}
			}
		});
		return this.output;
	},
	getChildId: function(data, result = []) {
		result.push(data.categoryId);
		data.children.forEach(value => {
			this.getChildId(value, result);
		});
		return result;
	},
	getParentMasterId: function(arr, data) {
		if (!data.parentId) {
			this.output = data;
		} else {
			const dataParent = arr.filter(value => {
				return value.categoryId == data.parentId;
			});

			this.getParentMasterId(arr, dataParent[0]);
		}
		return this.output;
	},
	getAllParentCategory: function(arr, ids = []) {
		const categories = filter(arr, category => {
			return ids.indexOf(category.categoryId) !== -1;
		});

		const parentIds = map(categories, "parentId").filter(id => !!id);

		if (!parentIds.length) {
			return categories;
		}

		return unionBy(
			[...categories, ...this.getAllParentCategory(arr, parentIds)],
			"categoryId"
		);
	},
	getCategoryByIndexDb: async function() {
		const categories = await indexDbService.getAll("categories");
		return categories;
	}
};
