<template>
	<b-container class="step login">
		<b-row class="justify-content-center">
			<b-col md="9">
				<ul class="text-left">
					<li>
						Hàng hóa chỉ đồng bộ 1 chiều duy nhất từ Kiotviet sang Website
						thông qua SKU. Trường hợp ngắt kết nối gian hàng đang đồng bộ
						với Website để kết nối với 1 gian hàng mới, quý khách vui lòng
						"Xóa hàng hóa" của gian hàng cũ trên Website để đảm bảo thông
						tin hàng hóa mới được đồng bộ đầy đủ.
					</li>
					<li style="margin-bottom: 0px;">
						Khi khách hàng đặt hàng tại Website, đơn hàng sẽ nhanh chóng
						được đồng bộ về KiotViet. Các đơn hàng chỉ đồng bộ 1 chiều duy
						nhất từ Website sang Kiotviet mà không có chiều ngược lại. Nếu
						đơn hàng có chứa hàng hóa và khách hàng chưa có trên KiotViet,
						thông tin của hàng hóa và khách hàng sẽ được thêm mới vào
						KiotViet.
					</li>
				</ul>
			</b-col>
		</b-row>

		<div v-show="waitingConfig" style="text-align: center; margin-top: 30px">
			<b-spinner variant="success" label="Spinning" />
		</div>
		<b-row v-show="!waitingConfig" class="justify-content-center">
			<b-col md="5">
				<b-card-group>
					<b-card
						style="max-width: 100%; padding: 0px"
						no-body
						class="p-8"
					>
						<div class="errors" v-if="errorMessage">
							<p v-html="errorMessage"></p>
							<font-awesome-icon
								style="position: absolute; left: 35px; top: 40px; color: #fff"
								icon="exclamation-triangle"
							></font-awesome-icon>
						</div>
						<img
							v-show="!errorMessage"
							class="mt-2 mb-2 logo"
							src="https://cdn-app.kiotviet.vn/retailler/Content/kiotvietLogo.png"
							alt="Fluid image"
						/>
						<p
							class="text-center"
							style="font-size: 14px; font-weight: bold"
						>
							Thông số kết nối
						</p>
						<b-card-body>
							<b-form style="position: relative">
								<b-input-group class="mb-3">
									<input
										autofocus
										type="text"
										class="form-control input-login"
										:class="{ error: errorRetailer }"
										v-model="request.retailer"
										require
										placeholder="Địa chỉ truy cập cửa hàng"
										style="padding-right: 100px"
									/>
									<span class="text-appden">kiotviet.vn</span>
								</b-input-group>
								<b-input-group class="mb-3">
									<input
										type="text"
										class="form-control input-login"
										:class="{ error: errorClientId }"
										v-model="request.client_id"
										require
										placeholder="Client ID"
									/>
								</b-input-group>
								<b-input-group class="mb-3">
									<input
										type="text"
										class="form-control input-login"
										:class="{ error: errorClientSecret }"
										v-model="request.client_secret"
										require
										placeholder="Mã bảo mật"
									/>
								</b-input-group>
								<div class="text-center">
									<b-button
										style="font-size: inherit"
										:disabled="waiting"
										variant="success"
										class="text-center"
										@click="login()"
										@keyup.enter="login()"
										>Tiếp theo
										<b-spinner v-show="waiting" small />
										<span class="sr-only">Loading...</span>
									</b-button>
								</div>
							</b-form>
						</b-card-body>
					</b-card>
				</b-card-group>
			</b-col>
		</b-row>
	</b-container>
</template>
<script>
import { mapMutations } from "vuex";
import { router } from "../../router";
import { AuthService } from "../../services/AuthService";
import mixins from "../../mixins";

export default {
	name: "ConfigInfoSync",
	mixins: [mixins],
	data() {
		return {
			request: {
				client_id: "",
				client_secret: "",
				retailer: ""
			},
			waiting: false,
			waitingConfig: false,
			errorMessage: "",
			checkLogin: false,
			errorClientId: false,
			errorClientSecret: false,
			errorRetailer: false
		};
	},
	methods: {
		...mapMutations("Auth", ["setCheckLogin"]),
		...mapMutations(["resetProcessBar", "resetProcessBarGetData"]),
		async login() {
			const messageError =
				"Thông tin đăng nhập chưa đúng, vui lòng nhập </br> lại thông tin hoặc liên hệ 18006162 để được hỗ trợ!";

			if (!this.request.retailer) {
				this.errorRetailer = true;
				this.errorMessage = messageError;
				return;
			} else {
				this.errorRetailer = false;
			}

			if (!this.request.client_id) {
				this.errorClientId = true;
				this.errorMessage = messageError;
				return;
			} else {
				this.errorClientId = false;
			}

			if (!this.request.client_secret) {
				this.errorClientSecret = true;
				this.errorMessage = messageError;
				return;
			} else {
				this.errorClientSecret = false;
			}

			this.waiting = true;

			const response = await AuthService.getAccessToken(this.request);
			if (response.status == "success") {
				const accessToken = response.data.access_token;
				const payload = JSON.parse(atob(accessToken.split(".")[1]));

				const request = {
					retailer: this.request.retailer
				};

				if (payload.client_RetailerCode == request.retailer) {
					await AuthService.saveConfigRetailer(request);
					await AuthService.registerWebhook();
					this.resetSetting();
					// remove indexDB
					this.waiting = false;

					router.push({
						name: "Branch"
					});
				} else {
					this.waiting = false;
					this.errorMessage = messageError;
				}
			} else {
				this.waiting = false;
				this.errorMessage = messageError;
			}
		},
		async getConfig() {
			const response = await AuthService.getConfig();
			this.waitingConfig = false;
			if (response.status == "success") {
				this.request.client_id = response.data.client_id;
				this.request.client_secret = response.data.client_secret;
				this.request.retailer = response.data.retailer;
			}
		}
	},
	mounted() {
		this.getConfig();
	}
};
</script>
