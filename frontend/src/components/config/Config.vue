<template>
	<b-container class="config">
		<div class="main" v-show="checkLogin">
			<p class="branch-name">
				Chi nhánh: <span>{{ branch.branchName }}</span>
			</p>
			<b-row
				class="justify-content-center"
				v-show="Math.ceil(processBar) < 100"
			>
				<b-col md="6">
					<div class="process-get-data">
						<b-progress
							class="mb-3"
							:max="max"
							variant="success"
							height="1.3rem"
							show-progress
						>
							<b-progress-bar :value="processBar">
								<strong>{{ processBar }}%</strong>
							</b-progress-bar>
						</b-progress>
						<p class="loading-data-notice">{{ notice }}</p>
						<div class="cancel-connect text-center mt-4">
							<b-button
								variant="outline-secondary"
								size="sm"
								@click="stopConnection"
								>Hủy kết nối
								<font-awesome-icon
									class="ml-1"
									icon="times"
								></font-awesome-icon>
							</b-button>
						</div>
					</div>
				</b-col>
			</b-row>

			<div v-show="Math.ceil(processBar) >= 100">
				<StepNavigation :steps="steps"></StepNavigation>

				<component
					v-show="currentStep === step.id"
					v-for="step in steps"
					:key="step.name"
					:is="step.component"
				>
				</component>
			</div>

			<b-modal
				ref="NotifyGetData"
				centered
				title="Thông báo"
				@ok="reGetData"
				@cancel="cancelReGetData"
			>
				<div slot="modal-ok">Đồng ý</div>
				<div slot="modal-cancel">Hủy</div>
				<p class="my-6" style="text-align: left">
					Quá trình lấy thông tin từ Kiotviet có lỗi xảy ra, bạn có muốn
					thực hiện lại?
				</p>
			</b-modal>
			<StopConnection ref="StopConnection"></StopConnection>
		</div>
	</b-container>
</template>

<script>
import { mapState, mapMutations, mapActions } from "vuex";
import StepNavigation from "./step/StepNavigation";
import ConfigCategory from "./category/ConfigCategory";
import ConfigProduct from "./product/ConfigProduct";
import ConfigPriceBook from "./pricebook/ConfigPriceBook";
import ConfigBranch from "./branch/ConfigBranch";
import ConfigInfoSync from "./info/ConfigInfoSync";
import ConfigSetting from "./ConfigSetting";
import StopConnection from "./connection/StopConnection";
import { router } from "../../router";
import { AuthService } from "../../services/AuthService";
import { StorageService } from "../../services/Storage";
import mixins from "../../mixins";

export default {
	name: "Config",
	mixins: [mixins],
	components: {
		StepNavigation,
		ConfigCategory,
		ConfigProduct,
		ConfigPriceBook,
		ConfigBranch,
		ConfigInfoSync,
		StopConnection
	},
	data() {
		return {
			dismissSecs: 20,
			dismissCountDown: 0,
			max: 100,
			steps: ConfigSetting.STEPS,
			checkLogin: false,
			retailer: "",
			request: {
				category: {
					// REVIEW - Logic chuyển qua get all category
					includeRemoveIds: true,
					orderBy: "createdDate",
					orderDirection: "desc",
					isMykiot: true
				},
				product: {
					currentItem: 0,
					pageSize: 1000,
					includePricebook: true,
					includeInventory: true,
					includeRemoveIds: true,
					orderBy: "createdDate",
					orderDirection: "desc",
					isMykiot: true,
					BranchIds: null,
					IncludeSoftDeletedAttribute: false,
					isActive: true
				},
				branch: {
					currentItem: 0,
					pageSize: 100,
					includeRemoveIds: true,
					orderBy: "createdDate",
					orderDirection: "desc",
					isMykiot: true
				},
				priceBooks: {
					currentItem: 0,
					pageSize: 100,
					includeRemoveIds: true,
					orderDirection: "desc",
					isMykiot: true
				}
			},
			processBar: 0,
			notice: "Đang lấy dữ liệu từ Kiotviet"
		};
	},
	computed: {
		...mapState("Step", ["currentStep"]),
		...mapState(["processBarGetData", "branch"]),
		...mapState("Sync", ["doneSync"])
	},
	watch: {
		processBarGetData(value) {
			this.processBar = parseFloat(value.toFixed(2));
			if (Math.ceil(this.processBar) >= 100) {
				this.notice = "Lấy dữ liệu thành công!";
			}
		}
	},
	methods: {
		...mapActions("Category", ["getAllCategoryKiotviet"]),
		...mapActions("Product", ["getAllProductKiotviet", "getProductSynced"]),
		...mapActions("Branch", ["getAllBranchKiotviet"]),
		...mapActions("PriceBook", ["getAllPriceBookKiotviet"]),
		...mapMutations("Step", ["setLastStep", "setCurrentStep"]),
		...mapMutations("Auth", ["setRetailer"]),
		...mapMutations(["resetProcessBarGetData", "setConfigKiotviet"]),
		countDownChanged(dismissCountDown) {
			this.dismissCountDown = dismissCountDown;
		},
		async checkVersion() {
			const response = await AuthService.checkVersion();
			if (response.data) {
				this.dismissCountDown = this.dismissSecs;
			}
		},
		async verifyLogin() {
			const response = await AuthService.getConfig();
			if (response.status == "success") {
				this.setConfigKiotviet(response.data);
				if (!response.data.retailer) {
					router.push({
						name: "Login"
					});
					this.checkLogin = false;
				} else {
					this.checkLogin = true;
					if (!this.doneSync) {
						// Set active step 2 when login success and done sync
						this.setCurrentStep(2);
					}
					this.retailer = response.data.retailer;
					StorageService.setItem("retailer", this.retailer);
				}
			}
		},
		async cancelReGetData() {
			this.$refs["NotifyGetData"].hide();
			location.reload();
		},
		async reGetData() {
			this.resetProcessBarGetData(0);
			await this.getData();
		},
		async getData() {
			this.request.product.BranchIds = this.branch.id;
			try {
				await this.getAllCategoryKiotviet(this.request.category);
				await this.getAllProductKiotviet(this.request.product);
				await this.getProductSynced();
				await this.getAllBranchKiotviet(this.request.branch);
				await this.getAllPriceBookKiotviet(this.request.priceBooks);
			} catch (error) {
				console.log(error);
				this.$refs["NotifyGetData"].show();
				return;
			}
		},
		stopConnection() {
			this.$refs["StopConnection"].showModal();
		}
	},
	async mounted() {
		if (this.branch === null) {
			router.push({
				name: "Branch"
			});
		} else {
			this.setLastStep(this.steps.length + 1);
			// check login
			await this.verifyLogin();
			if (this.checkLogin) {
				await this.getData();
			}
		}
	}
};
</script>
<style></style>
