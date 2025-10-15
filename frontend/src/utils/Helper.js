/* eslint-disable no-param-reassign */
export default {
	debounce: (func, wait, immediate) => {
		let timeout;
		return function() {
			const context = this;
			const args = arguments;
			const later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			const callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	},
	from: function(page, pageSize) {
		return (page - 1) * pageSize + 1;
	},
	to: function(page, pageSize, total_item) {
		return (page - 1) * pageSize + pageSize > total_item
			? total_item
			: (page - 1) * pageSize + pageSize;
	},
	uniqueMultidimArray: (array, key) => {
		const result = [];
		const map = new Map();
		for (const item of array) {
			if (!map.has(item[key])) {
				map.set(item[key], true); // set any value to Map
				result.push(item);
			}
		}
		return result;
	},
	uniqueArray: array => {
		return array.filter((value, index, self) => {
			return self.indexOf(value) === index;
		});
	},
	getDataByPaginate: (array, page, pageSize) => {
		return array.filter((value, key) => {
			const from = (page - 1) * pageSize;
			const to = from + pageSize - 1;
			if (from <= key && to >= key) {
				return value;
			}
		});
	},
	cloneObject: data => {
		return Object.assign({}, data);
	},
	retryRequest: async function(callback, data, number) {
		if (number == 0) {
			return false;
		}

		try {
			const response = await callback(data);
			return response;
		} catch (error) {
			number--;
			await this.retryRequest(callback, data, number);
		}
	},
	recursiveService: async function(
		totalItem,
		numberPage,
		data,
		wait,
		callback
	) {
		const numberRquest = Math.ceil(totalItem / numberPage);

		for (let i = 1; i <= numberRquest; i++) {
			const from = this.from(i, numberPage) - 1;
			const to = this.to(i, numberPage, totalItem);
			const dataSlice = data.slice(from, to);
			await callback(dataSlice);
			await this.wait(wait);
		}
	},
	wait: async function wait(ms) {
		return new Promise(resolve => {
			setTimeout(resolve, ms);
		});
	},
	getDataExits: (dataParent, dataChild, key) => {
		const dataExits = [];
		dataParent.map(itemParent => {
			dataChild.map((itemChild, keyChild) => {
				if (itemParent !== null && itemParent[key] == itemChild[key]) {
					return dataExits.push(keyChild);
				}
			});
		});
		return dataExits;
	},
	unitProcess: (dataProcess, total, itemExec) => {
		return dataProcess / (total < itemExec ? 1 : Math.ceil(total / itemExec));
	},
	getAllDataByPaginate: (data, total, request, callback) => {
		const numberPage = Math.ceil(total / request.pageSize);
		if (numberPage > 1) {
			for (let i = 2; i <= numberPage; i++) {
				request.currentItem = (i - 1) * request.pageSize;
				callback(request);
			}
		}
	},
	getCurrentTimeISO: () => {
		const d = new Date();
		d.setHours(d.getHours() + 7);
		const date = d.toISOString().replace("Z", "");
		return date;
	},
	asyncForEach: async (array, callback) => {
		for (let index = 0; index < array.length; index++) {
			await callback(array[index], index, array);
		}
	},
	changeAlias: str => {
		str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
		str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
		str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
		str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
		str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
		str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
		str = str.replace(/đ/g, "d");
		str = str.replace(/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/g, "A");
		str = str.replace(/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/g, "E");
		str = str.replace(/Ì|Í|Ị|Ỉ|Ĩ/g, "I");
		str = str.replace(/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/g, "O");
		str = str.replace(/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/g, "U");
		str = str.replace(/Ỳ|Ý|Ỵ|Ỷ|Ỹ/g, "Y");
		str = str.replace(/Đ/g, "D");
		return str;
	}
};
