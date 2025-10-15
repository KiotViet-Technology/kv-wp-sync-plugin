<template>
	<b-modal
		ref="ConfirmDelete"
		centered
		title="Xóa thông tin"
		@ok="stopConnection"
	>
		<div slot="modal-ok">
			OK
			<b-spinner v-show="waiting" small />
		</div>
		<b-form-group
			class="text-left"
			label="Bạn có muốn xóa các thông tin đã đồng bộ?"
		>
			<b-form-checkbox-group
				stacked
				v-model="dataSync"
				:options="[{ text: 'Danh mục', value: 1 }]"
			>
			</b-form-checkbox-group>
			<b-form-checkbox-group
				stacked
				v-model="dataSync"
				:options="[
					{
						text: 'Hàng hóa',
						value: 2
					}
				]"
			>
			</b-form-checkbox-group>
		</b-form-group>
		<p class="my-6 text-left" style="font-size: 14px">
			Thông tin về nhóm hàng, thuộc tính, hàng hóa, giá bán sẽ không được cập
			nhật sau khi ngắt kết nối. Bạn có chắc chắn muốn ngắt hay không ?
		</p>
	</b-modal>
</template>
<script>
import { AuthService } from "../../../services/AuthService";
import { CategoryService } from "../../../services/CategoryService";
import { ProductService } from "../../../services/ProductService";
import { router } from "../../../router";

export default {
	name: "StopConnection",
	data() {
		return {
			waiting: false,
			dataSync: []
		};
	},
	methods: {
		showModal() {
			this.$refs["ConfirmDelete"].show();
		},
		async stopConnection(evt) {
			evt.preventDefault();
			this.waiting = true;
			const response = await AuthService.removeWebhook();

			if (response.status == "success") {
				await AuthService.removeConfig();
				await AuthService.removeLog();
				await this.removeDataSync();
				this.waiting = false;
				this.$nextTick(() => {
					this.$root.$emit("bv::hide::modal", "ConfirmDelete");
				});

				router.push({
					name: "Login"
				});
			} else {
				this.waiting = false;
			}
		},
		async removeDataSync() {
			// remove Category
			if (this.dataSync.indexOf(1) !== -1) {
				await CategoryService.deleteCategoryMap();
			}

			// remove Product
			if (this.dataSync.indexOf(2) !== -1) {
				await ProductService.deleteProductMap();
			}
		}
	}
};
</script>
