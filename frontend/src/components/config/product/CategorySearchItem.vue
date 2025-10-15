<template>
	<div class="tree-menu">
		<span
			@click="toggleChildren"
			class="icon-menu"
			v-if="item.children.length"
		>
			<span v-if="!showChildren">
				<font-awesome-icon v-if="!showChildren" icon="plus" />
			</span>
			<span v-else> <font-awesome-icon icon="minus" /> </span>
		</span>
		<div
			class="label-wrapper"
			:class="{ active: this.categoryIdSearch == item.categoryId }"
		>
			<div :style="indent" @click="searchProduct(item)">
				<span :title="label">{{
					label.length > 15 ? label.substring(0, 15) + " ..." : label
				}}</span>
			</div>
			<font-awesome-icon
				v-show="checkCategorySearch(item)"
				class="icon-check"
				icon="check"
			></font-awesome-icon>
			<font-awesome-icon
				@click="addProduct(item)"
				class="icon-add"
				icon="long-arrow-alt-right"
				title="Thêm"
			></font-awesome-icon>
		</div>
		<CategorySearchItem
			v-for="(value, index) in item.children"
			v-show="showChildren"
			:key="index"
			:item="value"
			:label="value.categoryName"
			:depth="depth + 1"
			:q="q"
		>
		</CategorySearchItem>
	</div>
</template>
<script>
import { mapState, mapMutations } from "vuex";
import mixins from "../../../mixins";

export default {
	name: "CategorySearchItem",
	props: ["item", "depth", "label", "q"],
	mixins: [mixins],
	data() {
		return {
			showChildren: false,
			activeCategory: false
		};
	},
	computed: {
		...mapState("Category", ["historyCategorySearch"]),
		...mapState("Product", ["categoryIdSearch"]),
		iconClasses() {
			return !this.showChildren ? "+" : "-";
		},
		indent() {
			return {
				position: "relative",
				paddingLeft: `${this.depth * 15}px`
			};
		}
	},
	methods: {
		...mapMutations("Product", ["setCategoryIdSearch"]),
		toggleChildren() {
			this.showChildren = !this.showChildren;
		},
		searchProduct(value) {
			this.setCategoryIdSearch(value.categoryId);
			this.getComponentParent(
				"ConfigProduct"
			).$refs.ProductTable.categoryIdSearch(value.categoryId);
		},
		isActive(value) {
			return value.categoryID == this.activeCategoryId;
		},
		addProduct(value) {
			this.getComponentParent(
				"ConfigProduct"
			).$refs.ProductTable.categoryIdAdd(value.categoryId);
		},
		checkCategorySearch(item) {
			if (this.historyCategorySearch.indexOf(item.categoryId) !== -1) {
				return true;
			} else {
				return false;
			}
		}
	},
	watch: {
		q(value) {
			if (value) {
				this.showChildren = true;
			} else {
				this.showChildren = false;
			}
		}
	}
};
</script>
