<template>
	<div>
		<p
			v-show="productKiotvietData.length"
			class="product-length text-center"
		></p>
		<div class="active-all">
			<b-form-checkbox v-model="selectAll" @change="toggleAll">
				Chọn tất cả sản phẩm
			</b-form-checkbox>

			<b-form-checkbox v-model="selectAllNew" @change="toggleAllNew">
				Chọn tất cả các sản phẩm mới
			</b-form-checkbox>

			<div class="sync-all">
				<b-form-checkbox
					v-model="handleStatus"
					@change="changeAllStatus"
					:disabled="checkDisableChangeStatus"
					switch
					class="button-active"
				>
					Đồng bộ tất cả sản phẩm được chọn
				</b-form-checkbox>
			</div>
		</div>

		<div class="product-list">
			<div class="product-header">
				<div class="select">
					<b-form-checkbox
						@change="checkAllOnePage"
						:value="true"
						:unchecked-value="false"
						v-model="selectAllOnePage"
					></b-form-checkbox>
				</div>
				<div class="name">Hàng hóa trên Kiotviet</div>
				<div class="category">Danh mục hàng hóa</div>
				<div class="status text-center">Trạng thái đồng bộ</div>
			</div>
			<div class="product-search">
				<ProductSearch
					ref="ProductSearch"
					:productKiotvietAll="productKiotvietAll"
				></ProductSearch>
			</div>
			<div v-show="waiting" class="text-center mt-3">
				<b-spinner variant="success" label="Spinning" />
			</div>
			<div v-show="!waiting" class="product-list-item">
				<div
					v-for="(product, index) in productKiotviet"
					v-bind:key="product.id"
					class="product-item"
				>
					<div class="select">
						<b-form-group>
							<b-form-checkbox-group v-model="productSelected">
								<b-form-checkbox
									class="d-flex"
									v-model="productSelected"
									:value="product.id"
								></b-form-checkbox>
							</b-form-checkbox-group>
						</b-form-group>
					</div>
					<div class="image">
						<figure>
							<img v-bind:src="productImage(product)" alt="" />
						</figure>
					</div>
					<div class="name" :title="productFullName(product)">
						{{ productName(product) }}
					</div>
					<div class="category">
						<span :title="categoryFullName(product.categoryId)"
							>{{ categoryName(product.categoryId) }}
						</span>
					</div>
					<div class="status text-center">
						<b-form-checkbox
							:id="
								'test' + ((request.page - 1) * request.pageSize + index)
							"
							name="check-button"
							@change="
								changeStatusItem(
									[product.id],
									checkBoxStatus[
										(request.page - 1) * request.pageSize + index
									]
								)
							"
							v-model="
								checkBoxStatus[
									(request.page - 1) * request.pageSize + index
								]
							"
							switch
						>
						</b-form-checkbox>
					</div>
				</div>
				<div v-show="!productKiotviet.length" class="product-item">
					<p class="text-center" style="width: 100%; padding-top: 15px">
						Không tìm thấy sản phẩm
					</p>
				</div>
			</div>
			<nav class="product-footer">
				<b-pagination
					:total-rows="totalItem"
					:per-page="request.pageSize"
					v-model="request.page"
					@input="getDataByPaginate(productKiotvietData)"
				>
				</b-pagination>
				<span class="total-item" v-show="totalItem">
					Hiển thị <b style="color: #28a745">{{ from }}</b> - {{ to }} của
					{{ totalItem }}
				</span>
			</nav>
		</div>
	</div>
</template>
<script>
import { mapMutations, mapState } from "vuex";
import { ProductService } from "../../../services/ProductService";
import Helper from "../../../utils/Helper";
import HelperCategory from "../../../utils/HelperCategory";
import ProductSearch from "./ProductSearch";

export default {
	name: "ProductTable",
	components: {
		ProductSearch
	},
	data() {
		return {
			waiting: true,
			handleStatus: false,
			request: {
				pageSize: 10,
				page: 1
			},
			productKiotviet: [],
			productSearch: [],
			productKiotvietData: [],
			categorySearch: [],
			productSelected: [],
			selectAllOnePage: false,
			selectAll: false,
			selectAllNew: false,
			totalItem: 0,
			checkBoxStatus: [],
			productSync: []
		};
	},
	props: ["productKiotvietAll", "categoryKiotvietAll"],
	watch: {
		productKiotvietAll(value) {
			if (value) {
				// auto active category all
				this.categoryIdSearch(-1);
				this.setCategoryIdSearch(-1);
			}
		},
		productKiotvietData(value) {
			for (let i = 0; i < this.checkBoxStatus.length; i++) {
				this.checkBoxStatus[i] = false;
			}
			if (value) {
				this.totalItem = value.length;
				this.getDataByPaginate(value);
				this.request.page = 1;
			}
		},
		productKiotviet() {
			this.checkIsAllByPage();
			this.checkIsAll();
			this.checkStatusSync();
		},
		productSelected() {
			this.checkIsAllByPage();
			this.checkIsAll();
			this.checkIsAllNew();
		}
	},
	computed: {
		...mapState("Product", ["productSynced"]),
		from() {
			return Helper.from(this.request.page, this.request.pageSize);
		},
		to() {
			return Helper.to(
				this.request.page,
				this.request.pageSize,
				this.totalItem
			);
		},
		checkDisableChangeStatus() {
			const product = this.productKiotviet.filter(product => {
				return this.productSelected.indexOf(product.id) !== -1;
			});

			if (product.length) {
				return false;
			}
			return true;
		}
	},
	methods: {
		...mapMutations("Category", ["setHistoryCategorySearch"]),
		...mapMutations("Product", ["setCategoryIdSearch"]),
		searchProductByName: Helper.debounce(function(q) {
			let productSearch = [];
			if (q.trim()) {
				productSearch = this.searchProductByCategory(
					this.categorySearch
				).filter(value => {
					return (
						Helper.changeAlias(value.fullName.toLowerCase()).includes(
							Helper.changeAlias(q.toLowerCase())
						) || value.code.toLowerCase().includes(q.toLowerCase())
					);
				});
			} else {
				productSearch = this.searchProductByCategory(this.categorySearch);
			}

			this.productKiotvietData = productSearch;
			this.request.page = 1;
		}, 500),
		categoryIdSearch(value) {
			this.productSearch = [];
			this.categorySearch = [];
			const categoryActive = this.getCategoryActive(value);
			Array.prototype.push.apply(this.categorySearch, categoryActive);
			this.setHistoryCategorySearch(this.categorySearch);
			this.productKiotvietData = this.searchProductByCategory(
				categoryActive
			);
		},
		categoryIdAdd(value) {
			this.productSearch = [];
			if (this.categorySearch.indexOf(value) !== -1) {
				this.$toaster.error("Nhóm đã tồn tại trong danh sách");
				return;
			} else {
				const categoryActive = this.getCategoryActive(value);
				this.categorySearch.forEach(value => {
					const index = categoryActive.indexOf(value);
					if (index !== -1) {
						categoryActive.splice(index, true);
					}
				});

				this.categorySearch = this.categorySearch.concat(categoryActive);
				this.setHistoryCategorySearch(this.categorySearch);
				const productKiotvietData = this.productKiotvietData;
				Array.prototype.push.apply(
					productKiotvietData,
					this.searchProductByCategory(categoryActive)
				);

				this.productKiotvietData = productKiotvietData;
				this.totalItem = productKiotvietData.length;
				this.getDataByPaginate(productKiotvietData);
			}
		},
		getCategoryActive(value) {
			let categoryId = [];
			if (value == -1) {
				categoryId = this.categoryKiotvietAll.map(item => item.categoryId);
			} else {
				categoryId = HelperCategory.getChildId(
					HelperCategory.getArrayByMenuId(
						HelperCategory.getNestedChildren(this.categoryKiotvietAll),
						value
					)
				);
			}
			return categoryId;
		},
		categoryName(id) {
			const category = this.categoryKiotvietAll.find(item => {
				return id == item.categoryId;
			});

			const categoryName =
				category.categoryName.length >= 30
					? `${category.categoryName.substring(0, 30)} ...`
					: category.categoryName;

			return categoryName;
		},
		categoryFullName(id) {
			const category = this.categoryKiotvietAll.find(item => {
				return id == item.categoryId;
			});

			return category.categoryName;
		},
		searchProductByCategory(categoryId) {
			// Set active menu category
			this.request.page = 1;
			// Filter data by categoryId
			const productByCategory = this.productKiotvietAll.filter(value => {
				return categoryId.indexOf(value.categoryId) !== -1;
			});
			return productByCategory;
		},
		async getDataByPaginate() {
			this.productKiotviet = Helper.getDataByPaginate(
				this.productKiotvietData,
				this.request.page,
				this.request.pageSize
			);
			await this.productMap();
		},
		async productMap() {
			// reset active
			const productIds = this.productKiotviet.map(product => {
				return product.id;
			});

			this.waiting = true;

			const productMap = await ProductService.getProductMap(productIds);

			if (productMap.data.status == "success") {
				productMap.data.data.forEach(product => {
					this.productKiotviet.forEach(
						(productKiotviet, keyProductKiotviet) => {
							if (
								product.product_kv_id == productKiotviet.id &&
								parseInt(product.status) == 1
							) {
								const index = this.getIndex(keyProductKiotviet);
								this.checkBoxStatus[index] = true;
							}
						}
					);
				});
			}

			this.waiting = false;
		},
		productImage(product) {
			if (typeof product.images !== "undefined") {
				return product.images[0];
			} else {
				return "https://cdn-app.kiotviet.vn/retailler/Content/default-product.png";
			}
		},
		productName(product) {
			const name = `${product.name} - ${product.code}`;
			return name.length >= 30 ? `${name.substring(0, 30)} ...` : name;
		},
		productFullName(product) {
			if (product.unit !== undefined && product.unit !== "") {
				return `${product.name} - ${product.code} - ${product.unit}`;
			} else {
				return `${product.name} - ${product.code}`;
			}
		},
		getIndex(key) {
			return (this.request.page - 1) * this.request.pageSize + key;
		},
		toggleAll(checked) {
			const allProducts = this.productKiotvietData.map(
				product => product.id
			);
			if (checked) {
				allProducts.forEach(productId => {
					if (!this.productSelected.includes(productId)) {
						this.productSelected.push(productId);
					}
				});
			} else {
				this.productSelected = this.productSelected.filter(function(el) {
					return allProducts.indexOf(el) < 0;
				});
			}
		},
		toggleAllNew(checked) {
			const allProducts = this.productKiotvietData.map(
				product => product.id
			);
			if (checked) {
				allProducts.forEach(productId => {
					if (
						!this.productSelected.includes(productId) &&
						!this.productSynced.includes(productId)
					) {
						this.productSelected.push(productId);
					}
				});
			} else {
				allProducts.forEach(productId => {
					if (
						this.productSelected.includes(productId) &&
						!this.productSynced.includes(productId)
					) {
						const index = this.productSelected.indexOf(productId);
						if (index > -1) {
							this.productSelected.splice(index, 1);
						}
					}
				});
			}
		},
		checkIsAllByPage() {
			const allProducts = this.productKiotviet.map(product => product.id);
			for (let i = 0; i < allProducts.length; i++) {
				if (this.productSelected.includes(allProducts[i])) {
					this.selectAllOnePage = true;
				} else {
					this.selectAllOnePage = false;
					break;
				}
			}
		},
		checkIsAll() {
			const allProducts = this.productKiotvietData.map(
				product => product.id
			);
			for (let i = 0; i < allProducts.length; i++) {
				if (this.productSelected.includes(allProducts[i])) {
					this.selectAll = true;
				} else {
					this.selectAll = false;
					break;
				}
			}
		},
		checkIsAllNew() {
			const allProducts = this.productKiotvietData.map(
				product => product.id
			);

			let countNewProduct = [];
			allProducts.forEach(productId => {
				if (!this.productSynced.includes(productId)) {
					countNewProduct.push(productId);
				}
			});

			for (let i = 0; i < countNewProduct.length; i++) {
				if (this.productSelected.includes(countNewProduct[i])) {
					this.selectAllNew = true;
				} else {
					this.selectAllNew = false;
					break;
				}
			}
		},
		checkAllOnePage(checked) {
			const allProductsByPage = this.productKiotviet.map(
				product => product.id
			);
			if (checked) {
				allProductsByPage.forEach(productId => {
					if (!this.productSelected.includes(productId)) {
						this.productSelected.push(productId);
					}
				});
			} else {
				this.productSelected = this.productSelected.filter(function(el) {
					return allProductsByPage.indexOf(el) < 0;
				});
			}
		},
		changeAllStatus(checked) {
			const productChangeStatus = [];
			this.productKiotvietData.forEach(product => {
				const index = this.productSync.indexOf(product.id);
				if (this.productSelected.includes(product.id)) {
					productChangeStatus.push(product.id);
					if (checked) {
						if (index === -1) {
							this.productSync.push(product.id);
						}
					} else {
						if (index !== -1) {
							this.productSync.splice(index, true);
						}
					}
				}
			});
			this.productSync = Helper.uniqueArray(this.productSync);

			// update status
			if (productChangeStatus.length) {
				this.changeStatus(productChangeStatus, this.handleStatus);
			}

			this.checkStatusSync();
		},
		checkStatusSync() {
			this.productKiotviet.forEach((product, key) => {
				if (this.productSelected.includes(product.id)) {
					const index = this.getIndex(key);
					if (this.productSync.includes(product.id)) {
						this.checkBoxStatus[index] = true;
					} else {
						this.checkBoxStatus[index] = false;
					}
				}
			});
		},
		async changeStatus(id, status) {
			const newStatus = !status;
			let statusParam;
			if (newStatus === true) {
				statusParam = 1;
			} else {
				statusParam = 0;
			}

			const request = {
				product_kv_id: id,
				status: statusParam
			};
			await ProductService.updateStatus(request);
		},
		async changeStatusItem(id, status) {
			const newStatus = !status;
			let statusParam;
			if (newStatus === true) {
				this.productSync.push(id[0]);
				statusParam = 1;
			} else {
				this.productSync.splice(this.productSync.indexOf(id[0]), 1);
				statusParam = 0;
			}

			const request = {
				product_kv_id: id,
				status: statusParam
			};
			await ProductService.updateStatus(request);
		}
	}
};
</script>
