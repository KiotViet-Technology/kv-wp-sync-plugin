<template>
	<b-container>
		<b-row class="justify-content-center">
			<b-col md="12">
				<div class="step config-info">
					<ul class="text-left description">
						<li>
							Hàng hóa chỉ đồng bộ 1 chiều duy nhất từ Kiotviet sang
							Website thông qua SKU. Trường hợp ngắt kết nối gian hàng
							đang đồng bộ với Website để kết nối với 1 gian hàng mới,
							quý khách vui lòng "Xóa hàng hóa" của gian hàng cũ trên
							Website để đảm bảo thông tin hàng hóa mới được đồng bộ đầy
							đủ.
						</li>
						<li>
							Khi khách hàng đặt hàng tại Website, đơn hàng sẽ nhanh
							chóng được đồng bộ về KiotViet. Các đơn hàng chỉ đồng bộ 1
							chiều duy nhất từ Website sang Kiotviet mà không có chiều
							ngược lại. Nếu đơn hàng có chứa hàng hóa và khách hàng chưa
							có trên KiotViet, thông tin của hàng hóa và khách hàng sẽ
							được thêm mới vào KiotViet.
						</li>
					</ul>
					<div class="info-login">
						<b-col md="12">
							<b-row align-h="center" class="box-info">
								<b-col><span>Gian hàng kết nối</span></b-col>
								<b-col>
									<p>{{ retailer }}</p>
									<a
										target="_blank"
										:href="`https://${retailer}.kiotviet.vn`"
										>{{ `https://${retailer}.kiotviet.vn` }}
									</a>
								</b-col>
								<b-col>
									<b-button @click="stopConnection" variant="danger">
										Ngắt kết nối
										<img
											class="icon-disconnect"
											src="./../../../assets/img/icon_disconnect.png"
										/>
									</b-button>

									<b-button @click="fastSync()" variant="success">
										Đồng bộ nhanh
									</b-button>

									<b-button @click="toBranch()" variant="info"
										>Chọn chi nhánh</b-button
									>

									<!--<b-button @click="toWebhook()" variant="warning">
										Webhooks
									</b-button>-->

									<StopConnection
										ref="StopConnection"
									></StopConnection>
								</b-col>
							</b-row>
						</b-col>
					</div>
				</div>
			</b-col>
		</b-row>
	</b-container>
</template>
<script>
import { mapMutations } from "vuex";
import { router } from "../../../router";
import ConfigSetting from "./../ConfigSetting";
import StopConnection from "../connection/StopConnection";
// import HelperProduct from "../../../utils/HelperProduct";
import mixins from "../../../mixins";
import { indexDbService } from "../../../services/indexDbService";

export default {
	name: "ConfigInfoSync",
	components: {
		StopConnection
	},
	mixins: [mixins],
	data() {
		return {
			waiting: false
		};
	},
	methods: {
		toBranch() {
			router.push({
				name: "Branch"
			});
		},
		toWebhook() {
			router.push({
				name: "WebHook"
			});
		},
		...mapMutations("Step", ["setLastStep", "setCurrentStep"]),
		...mapMutations("Product", ["setProductUpdate"]),
		...mapMutations(["setFastSync"]),
		stopConnection() {
			this.$refs["StopConnection"].showModal();
		},
		async fastSync() {
			this.setCurrentStep(ConfigSetting.STEPS.length + 1);
			const productUpdate = await this.getIdProductKiotvietUpdate();
			this.setProductUpdate(productUpdate);
			router.push({
				name: "Sync"
			});
		},
		async getIdProductKiotvietUpdate() {
			const productIndexDb = await indexDbService.getAll("products");
			const productKiotviet = productIndexDb;

			// const productKiotviet = HelperProduct.filterProductKiotviet(
			// 	productIndexDb
			// );

			const productId = productKiotviet.map(product => {
				return product.id;
			});
			return productId;
		}
	},
	computed: {
		retailer() {
			return this.$parent.retailer;
		}
	}
};
</script>
