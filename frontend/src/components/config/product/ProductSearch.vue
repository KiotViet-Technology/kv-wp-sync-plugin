<template>
	<b-col md="12" class="search-box">
		<img
			class="position-absolute"
			src="../../../assets/img/icon_search.png"
		/>
		<input
			ref="input"
			type="text"
			@input="searchProductByName"
			v-model="qProduct"
			placeholder="Tìm kiếm hàng hóa"
			class="input-search"
		/>
		<div class="line-bottom"></div>
	</b-col>
</template>
<script>
import mixins from "../../../mixins";

export default {
	name: "ProductSearch",
	mixins: [mixins],
	data() {
		return {
			qProduct: ""
		};
	},
	methods: {
		searchProductByName() {
			this.$parent.$parent.$refs.ProductTable.searchProductByName(
				this.qProduct
			);
		}
	}
};
</script>
