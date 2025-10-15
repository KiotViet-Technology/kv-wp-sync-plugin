<template>
	<div class="step product">
		<b-row>
			<b-col md="3">
				<span class="position-relative kv-notice-top">
					Tùy chọn <b style="color: red">KHÔNG</b> đồng bộ
					<font-awesome-icon
						@click="openModal"
						class="ml-1"
						icon="cog"
					></font-awesome-icon>
				</span>
				<CategorySearch
					:categoryKiotvietAll="categoryKiotvietAll"
				></CategorySearch>
			</b-col>
			<b-col md="9" class="product-table">
				<ProductTable
					:productKiotvietAll="productKiotvietAll"
					:categoryKiotvietAll="categoryKiotvietAll"
					ref="ProductTable"
				></ProductTable>
			</b-col>
		</b-row>

		<div class="step-wrapper text-right">
			<b-button size="md" @click="back">Quay lại</b-button>
			<b-button size="md" @click="saveAndNext" variant="success"
				>Tiếp tục</b-button
			>
			<b-button size="md" @click="skip" variant="danger">Hủy</b-button>
		</div>
		<ConfigSyncProduct ref="ConfigSyncProduct"></ConfigSyncProduct>
	</div>
</template>
<script>
import { mapState, mapMutations } from "vuex";
import HelperCategory from "../../../utils/HelperCategory";
import Helper from "../../../utils/Helper";
import HelperProduct from "../../../utils/HelperProduct";
import CategorySearch from "./CategorySearch";
import ProductTable from "./ProductTable";
import ConfigSyncProduct from "./ConfigSyncProduct";

export default {
	name: "ConfigProduct",
	components: {
		CategorySearch,
		ProductTable,
		ConfigSyncProduct
	},
	data() {
		return {
			productKiotvietAll: [],
			categoryKiotvietAll: []
		};
	},
	computed: {
		...mapState(["processBarGetData"]),
		...mapState("Sync", ["doneSync"])
	},
	methods: {
		...mapMutations("Step", ["nextStep", "backStep", "setCurrentStep"]),
		...mapMutations("Product", ["setCategoryIdSearch", "setProductAdd"]),
		openModal() {
			this.$refs["ConfigSyncProduct"].showModal();
		},
		back() {
			this.backStep();
		},
		saveAndNext() {
			this.getProductAdd();
			this.nextStep();
			window.scrollTo(0, 0);
		},
		skip() {
			this.setCurrentStep(1);
		},
		async getProductAdd() {
			const productAdd = this.$refs.ProductTable.productSelected;
			// get product Add
			this.setProductAdd(productAdd);
		},
		async getCategoryKiotvietAll() {
			const categories = await HelperCategory.getCategoryByIndexDb();
			const convertCategory = categories.map(value => {
				const item = value;
				if (!item.parentId) {
					item.parentId = 0;
				}
				return item;
			});

			return HelperCategory.recursionMenu(
				convertCategory.sort(function(a, b) {
					const textA = Helper.changeAlias(a.categoryName.toUpperCase());
					const textB = Helper.changeAlias(b.categoryName.toUpperCase());
					return textA < textB ? -1 : textA > textB ? 1 : 0;
				})
			);
		}
	},
	watch: {
		async processBarGetData(value) {
			if (Math.ceil(value) >= 100) {
				this.categoryKiotvietAll = await this.getCategoryKiotvietAll();

				const product = await HelperProduct.getProductByIndexDb();
				this.productKiotvietAll = product;
				// filtered product isActive in getAllProductKiotviet
				// this.productKiotvietAll = HelperProduct.filterProductKiotviet(
				// 	product
				// );
			}
		}
	}
};
</script>
