<template>
	<div class="step">
		<b-row class="justify-content-center branch auto-sync-order">
			<b-col md="12">
				<div class="branch-sync-order">
					<p>Chọn chi nhánh đồng bộ đơn hàng</p>
					<v-select
						placeholder="Chọn chi nhánh"
						label="branchName"
						v-model="config[1]"
						:options="branchKiotvietAll"
					>
						<template slot="no-options">
							Không có kết quả phù hợp
						</template>
					</v-select>
				</div>
			</b-col>
			<b-col md="12">
				<b-form-checkbox @change="saveConfig" v-model="autoSyncOrder"
					>Tự động đồng bộ đơn hàng về Kiotviet
				</b-form-checkbox>
				<div class="description">
					Lưu ý: Đơn hàng sẽ không được đồng bộ tự động về KiotViet nếu bạn
					tắt tính năng này. Vui lòng chọn <b>Đồng bộ lại</b> cho từng đơn
					hàng trong <b>Danh sách đơn đặt hàng</b> hoặc bật tính năng
					<b>Tự đồng bộ lại đơn hàng</b> trong phần <b>Cài đặt</b> để đồng
					bộ đơn hàng.
				</div>
				<div class="step-wrapper text-right">
					<b-button size="md" @click="back">Quay lại</b-button>
					<b-button size="md" @click="saveAndNext" variant="success"
						>Tiếp tục</b-button
					>
					<b-button size="md" @click="skip" variant="danger">Hủy</b-button>
				</div>
			</b-col>
		</b-row>
	</div>
</template>
<script>
import Vue from "vue";
import { mapState, mapMutations } from "vuex";
import { router } from "../../../router";
import { indexDbService } from "../../../services/indexDbService";
import { BranchService } from "../../../services/BranchService";
import { AuthService } from "../../../services/AuthService";

export default {
	name: "ConfigBranch",
	data() {
		return {
			configBranch: [],
			branchKiotvietAll: [],
			branchAll: [],
			autoSyncOrder: false,
			config: [],
			displayDescription: false
		};
	},
	computed: {
		...mapState("Step", ["currentStep", "lastStep"]),
		...mapState(["processBarGetData", "configKiotviet", "branch"])
	},
	watch: {
		async processBarGetData(value) {
			if (Math.ceil(value) >= 100) {
				this.branchAll.push(this.branch);
				this.configBranchStock();
				//this.configBranchOrder();

				this.branchKiotvietAll = await indexDbService.getAll("branchs");
				const response = await BranchService.getConfig();
				this.configBranch = response.data.data;
				if (response.data.status == "success") {
					//this.configBranchStock();
					this.configBranchOrder();
				}
			}
		},
		configKiotviet(value) {
			if (value) {
				this.autoSyncOrder = value.auto_sync_order ? true : false;
			}
		}
	},
	methods: {
		...mapMutations("Step", ["nextStep", "backStep", "setCurrentStep"]),
		...mapMutations("Branch", [
			"setConfigBranchStock",
			"setConfigBranchOrder"
		]),
		configBranchStock() {
			Vue.set(this.config, 0, this.branchAll[0]);
			this.setConfigBranchStock(this.branchAll[0]);
		},
		configBranchOrder() {
			// Vue.set(this.config, 1, this.branchAll[0]);
			// this.setConfigBranchOrder(this.branchAll[0]);

			const indexBranchOrder = this.branchKiotvietAll.findIndex(value => {
				if (this.configBranch.config_branch_order) {
					return value.id == this.configBranch.config_branch_order.id;
				}
			});

			if (indexBranchOrder !== -1) {
				Vue.set(this.config, 1, this.branchKiotvietAll[indexBranchOrder]);
				this.setConfigBranchOrder(this.configBranch.config_branch_order);
			} else {
				Vue.set(this.config, 1, this.branchKiotvietAll[0]);
			}
		},
		saveAndNext() {
			const validate = this.validate();
			if (!validate) {
				return;
			}

			this.setConfigBranchStock(this.branchAll[0]);
			//this.setConfigBranchOrder(this.branchAll[0]);
			this.setConfigBranchOrder(this.config[1]);
			this.nextStep();
			if (this.currentStep == this.lastStep) {
				router.push({
					name: "Sync"
				});
			}
		},
		skip() {
			this.setCurrentStep(1);
		},
		back() {
			this.backStep();
		},
		validate() {
			if (!this.config[1]) {
				this.$toaster.error("Bạn chưa chọn chi nhánh đồng bộ đơn đặt hàng");
				return false;
			}

			return true;
		},
		async saveConfig() {
			const request = {
				auto_sync_order: !this.autoSyncOrder
			};

			await AuthService.saveConfig(request);
		},
		overDescription() {
			this.displayDescription = true;
		},
		leaveDescription() {
			this.displayDescription = false;
		}
	}
};
</script>
