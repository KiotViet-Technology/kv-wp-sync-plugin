<template>
	<div>
		<div v-show="waitingMap" style="text-align: center;">
			<b-spinner variant="success" label="Spinning" />
		</div>
		<b-table
			hover
			show-empty
			empty-text="Không tìm thấy dữ liệu"
			responsive="sm"
			:items="categoryKiotviet"
			:fields="fields"
			v-show="!waitingMap"
		>
			<template slot="category_product_wp.name" slot-scope="data">
				<div
					v-show="
						oldCategoryMap[
							(request.page - 1) * request.pageSize + data.index
						]
					"
					class="disabled-input"
				></div>
				<v-select
					placeholder="Chọn nhóm hàng"
					@search:focus="
						searchFocus(
							(request.page - 1) * request.pageSize + data.index
						)
					"
					@input="
						value =>
							onChange(
								value,
								(request.page - 1) * request.pageSize + data.index
							)
					"
					label="name"
					size="sm"
					:filterable="false"
					v-model="
						categoryMap[
							(request.page - 1) * request.pageSize + data.index
						]
					"
					:options="
						categorySearch[
							(request.page - 1) * request.pageSize + data.index
						]
					"
					@search="
						(search, loading) =>
							onSearch(
								search,
								loading,
								(request.page - 1) * request.pageSize + data.index
							)
					"
				>
					<template slot="no-options">
						{{ messageSearch }}
					</template>
					<template slot="option" slot-scope="option">
						<div class="d-center">{{ option.name }}</div>
					</template>
					<template slot="clear-selection">
						<img
							v-show="
								!oldCategoryMap[
									(request.page - 1) * request.pageSize + data.index
								]
							"
							src="./../../../assets/img/icon_trash.png"
							@click="
								clearCategoryMap(
									(request.page - 1) * request.pageSize + data.index
								)
							"
						/>
					</template>
					<template slot="search">
						<p
							v-if="
								oldCategoryMap[
									(request.page - 1) * request.pageSize + data.index
								]
							"
						></p>
					</template>
				</v-select>
			</template>
			<template slot="categoryName" slot-scope="data">
				<span>{{ data.item.char }} {{ data.item.categoryName }}</span>
			</template>
		</b-table>
		<nav>
			<b-row>
				<b-col lg="12">
					<b-pagination
						:total-rows="totalItem"
						:per-page="request.pageSize"
						v-model="request.page"
						prev-text="Prev"
						next-text="Next"
						@input="getDataByPaginate"
					>
						<span class="text-success" slot="next-text">Next</span>
					</b-pagination>

					<span style="padding-left: 30px" v-show="totalItem">
						Hiển thị <b style="color: #28a745">{{ from }}</b
						>-{{ to }} của {{ totalItem }}
					</span>
				</b-col>
			</b-row>
		</nav>
	</div>
</template>
<script>
import Vue from "vue";
import { CategoryService } from "../../../services/CategoryService";
import Helper from "../../../utils/Helper";

export default {
	name: "CategoryTable",
	data() {
		return {
			fields: [
				{
					key: "categoryName",
					label: "Nhóm hàng trên KiotViet",
					thClass: "thead-table",
					tdClass: "td-table"
				},
				{
					key: "category_product_wp.name",
					label: "Chọn nhóm hàng tương ứng trên Website",
					thClass: "thead-table",
					tdClass: "td-table"
				}
			],
			categorySearch: [],
			categoryMap: [],
			oldCategoryMap: [],
			request: {
				pageSize: 20,
				page: 1
			},
			waitingMap: true,
			messageSearch: "Không có nhóm hàng để chọn ...",
			totalItem: 0,
			categoryKiotviet: [],
			defaultCategorySearch: [],
			categoryIgnoreAdd: [] // ignore category add with index categoryKiotvietAll
		};
	},
	props: ["categoryKiotvietAll"],
	computed: {
		from() {
			return Helper.from(this.request.page, this.request.pageSize);
		},
		to() {
			return Helper.to(
				this.request.page,
				this.request.pageSize,
				this.totalItem
			);
		}
	},
	watch: {
		categoryKiotvietAll(value) {
			if (value.length) {
				this.totalItem = value.length;
				this.getDataByPaginate();
			}
		}
	},
	methods: {
		clearCategoryMap(index) {
			// add categoryIgnoreAdd
			if (this.categoryIgnoreAdd.indexOf(index) == -1) {
				this.categoryIgnoreAdd.push(index);
			}
		},
		async getDataByPaginate() {
			this.categoryKiotviet = Helper.getDataByPaginate(
				this.categoryKiotvietAll,
				this.request.page,
				this.request.pageSize
			);
			await this.getCategoryMap(this.categoryKiotviet);
			window.scrollTo(0, 0);
		},
		searchFocus(index) {
			const dataSearch = [];
			this.defaultCategorySearch.map(itemSearch => {
				let checkExits = false;
				this.categoryMap.map(categoryMap => {
					if (categoryMap && itemSearch.term_id == categoryMap.term_id) {
						checkExits = true;
					}
				});

				if (!checkExits) {
					dataSearch.push(itemSearch);
				}
			});

			Vue.set(this.categorySearch, index, dataSearch);
			this.messageSearch = "Không có nhóm hàng để chọn ...";
		},
		onChange(value, index) {
			if (value === null) {
				if (this.categoryMap[index] !== null) {
					Vue.set(this.categoryMap, index, null);
				} else {
					return;
				}
			}
		},
		onSearch(search, loading, index) {
			if (search) {
				loading(true);
				this.search(search, loading, index);
			}
		},
		search: Helper.debounce(async function(search, loading, index) {
			const response = await CategoryService.getCategory(search);
			if (response.data.status == "success") {
				const resultSearch = response.data.data ? response.data.data : [];
				if (!resultSearch.length) {
					this.messageSearch = "Không có kết quả phù hợp";
				} else {
					// check exits data map
					const dataSearch = [];
					resultSearch.map(itemSearch => {
						let checkExits = false;
						this.categoryMap.map(categoryMap => {
							if (
								categoryMap &&
								itemSearch.term_id == categoryMap.term_id
							) {
								checkExits = true;
							}
						});

						if (!checkExits) {
							dataSearch.push(itemSearch);
						}
					});

					if (!dataSearch.length) {
						this.messageSearch = "Không có kết quả phù hợp";
					}

					Vue.set(this.categorySearch, index, dataSearch);
				}
			}
			loading(false);
		}, 1000),
		getDataMap() {
			// get categoryMap when change
			const categoryMap = this.categoryMap
				.reduce((filtered, option, index) => {
					if (
						(!option &&
							typeof this.oldCategoryMap[index] !== "undefined") ||
						(typeof this.oldCategoryMap[index] === "undefined" && option)
					) {
						filtered.push({
							CategoryKv: this.categoryKiotvietAll[index],
							Category: option
						});
					}
					return filtered;
				}, [])
				.filter(value => {
					if (value.Category && value.Category.term_id) {
						return value;
					}

					if (!value.Category) {
						return value;
					}
				});

			return categoryMap;
		},
		setDefaultNewData(add = true) {
			if (add) {
				this.categoryKiotvietAll.map((value, key) => {
					if (
						typeof this.categoryMap[key] === "undefined" ||
						this.categoryMap[key] === null
					) {
						const data = {
							name: "Thêm mới",
							term_id: 0
						};
						Vue.set(this.categoryMap, key, data);
					}
				});
			} else {
				this.categoryIgnoreAdd = [];
				this.categoryMap.map((value, key) => {
					if (value) {
						if (!value.term_id) {
							Vue.set(this.categoryMap, key, null);
						}
					}
				});
			}
		},
		async getCategoryMap(value) {
			const categoryIds = value.map(value => {
				return value.categoryId;
			});
			this.waitingMap = true;
			const categoryMaps = await CategoryService.getCategoryMap(categoryIds);
			this.waitingMap = false;
			categoryMaps.data.data.map(categoryMap => {
				this.categoryKiotviet.map(
					(categoryKiotviet, keyCategoryKiotviet) => {
						if (
							categoryMap.category_kv_id == categoryKiotviet.categoryId
						) {
							const index =
								(this.request.page - 1) * this.request.pageSize +
								keyCategoryKiotviet;
							const value = {
								name: categoryMap.name.replace("amp;", ""),
								term_id: categoryMap.term_id
							};

							Vue.set(this.categoryMap, index, value);
							this.oldCategoryMap[index] = value;
						}
					}
				);
			});
		}
	},
	async mounted() {
		const q = "";
		const response = await CategoryService.getCategory(q);
		this.defaultCategorySearch = response.data.data;
	}
};
</script>
