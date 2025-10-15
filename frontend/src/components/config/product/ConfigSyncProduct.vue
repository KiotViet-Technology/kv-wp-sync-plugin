<template>
	<b-modal ref="ConfigSyncProduct" hide-header centered @ok="saveConfig">
		<div slot="modal-ok">
			OK
			<b-spinner v-show="waiting" small />
		</div>
		<b-form-group class="text-left">
			<label
				>Thông tin <b style="color: red">KHÔNG</b> được phép thay đổi khi
				đồng bộ sản phẩm trên Kiotviet</label
			>
			<b-form-checkbox
				v-for="option in options"
				v-model="selected"
				:key="option.value"
				:value="option.value"
				name="flavour-3a"
			>
				{{ option.text }}
			</b-form-checkbox>
		</b-form-group>
	</b-modal>
</template>
<script>
import { mapState, mapMutations } from "vuex";
import { AuthService } from "../../../services/AuthService";

export default {
	name: "ConfigSyncProduct",
	data() {
		return {
			waiting: false,
			selected: [], // Must be an array reference!
			options: [
				{ text: "Tên sản phẩm", value: 1 },
				{ text: "Danh mục sản phẩm", value: 2 },
				{ text: "Ảnh sản phẩm", value: 3 },
				{ text: "Mô tả / Ghi chú", value: 4 },
				{ text: "Giá bán", value: 5 },
				{ text: "Giá khuyến mại", value: 6 },
				{ text: "Tồn kho", value: 7 },
				{
					text:
						"Thuộc tính (Chỉ áp dụng với sản phẩm đơn giản có thuộc tính)",
					value: 8
				}
			]
		};
	},
	computed: {
		...mapState(["configKiotviet"])
	},
	watch: {
		configKiotviet(value) {
			if (value) {
				const productSync = value.product_sync.map(item => {
					return parseInt(item);
				});

				this.selected = productSync;
				this.setConfigSyncProduct(this.selected);
			}
		}
	},
	methods: {
		...mapMutations("Product", ["setConfigSyncProduct"]),
		showModal() {
			this.$refs["ConfigSyncProduct"].show();
		},
		async saveConfig(evt) {
			evt.preventDefault();
			this.waiting = true;
			const request = {
				product_sync: this.selected
			};

			const response = await AuthService.saveConfig(request);
			this.setConfigSyncProduct(this.selected);
			this.waiting = false;
			if (response.status == "success") {
				this.$nextTick(() => {
					this.$refs["ConfigSyncProduct"].hide();
				});
			}
		}
	}
};
</script>
