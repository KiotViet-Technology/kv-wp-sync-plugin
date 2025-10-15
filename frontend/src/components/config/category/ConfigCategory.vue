<template>
	<div class="step">
		<CategoryHeader ref="categoryHeader"></CategoryHeader>
		<CategoryTable
			:categoryKiotvietAll="categoryKiotvietAll"
			ref="categoryTable"
		></CategoryTable>
		<div class="step-wrapper text-right">
			<b-button size="md" @click="back">Quay lại</b-button>
			<b-button size="md" @click="saveAndNext" variant="success"
				>Tiếp tục</b-button
			>
			<b-button size="md" @click="skip" variant="danger">Hủy</b-button>
		</div>
	</div>
</template>
<script>
import { mapState, mapMutations } from "vuex";
import HelperCategory from "../../../utils/HelperCategory";
import CategoryHeader from "./CategoryHeader";
import CategoryTable from "./CategoryTable";

export default {
	name: "ConfigCategory",
	components: {
		CategoryHeader,
		CategoryTable
	},
	data() {
		return {
			configCategory: [],
			categoryKiotvietAll: []
		};
	},
	computed: {
		...mapState(["processBarGetData"])
	},
	methods: {
		...mapMutations("Step", ["nextStep", "backStep", "setCurrentStep"]),
		...mapMutations("Category", [
			"setCategoryMap",
			"setConfigCategory",
			"setCategoryAdd",
			"setCategoryIgnoreAdd"
		]),
		saveAndNext() {
			this.setCategoryMap(this.$refs.categoryTable.getDataMap());
			this.setCategoryIgnoreAdd(this.$refs.categoryTable.categoryIgnoreAdd);
			this.setConfigCategory(this.$refs.categoryHeader.configCategory);
			this.nextStep();
			window.scrollTo(0, 0);
		},
		skip() {
			this.setCurrentStep(1);
		},
		back() {
			this.backStep();
		}
	},
	watch: {
		async processBarGetData(value) {
			if (Math.ceil(value) >= 100) {
				this.categoryKiotvietAll = await HelperCategory.getCategoryByIndexDb();
			}
		}
	}
};
</script>
