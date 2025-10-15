<template>
	<div>
		<b-form-group label="Chọn nhóm để đồng bộ">
			<b-form-checkbox-group
				stacked
				v-model="configCategory"
				:options="[
					{ text: 'Xóa các nhóm hàng hiện có trên Website', value: 1 }
				]"
			>
			</b-form-checkbox-group>
			<b-form-checkbox-group
				stacked
				v-model="configCategory"
				@change="changeConfig($event)"
				:options="[
					{
						text:
							'Thêm mới nhóm hàng trên KiotViet chưa tồn tại trên Website',
						value: 2
					}
				]"
			>
			</b-form-checkbox-group>
		</b-form-group>
	</div>
</template>
<script>
export default {
	name: "CategoryHeader",
	data() {
		return {
			configCategory: []
		};
	},
	watch: {
		// eslint-disable-next-line no-unused-vars
		configCategory(value) {
			this.changeConfig();
		}
	},
	methods: {
		changeConfig() {
			if (this.configCategory.indexOf(2) !== -1) {
				this.$parent.$refs.categoryTable.setDefaultNewData(true);
			} else {
				this.$parent.$refs.categoryTable.setDefaultNewData(false);
			}
		}
	}
};
</script>
