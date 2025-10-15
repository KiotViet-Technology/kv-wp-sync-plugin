<template>
	<b-container class="sync">
		<p class="branch-name">
			Chi nhánh: <span>{{ branch.branchName }}</span>
		</p>
		<b-row class="justify-content-center" style="margin-top: 50px">
			<b-col md="12" style="text-align: center;">
				<img
					v-show="!doneSync"
					src="./../../assets/img/icon_document.png"
				/>
				<img v-show="doneSync" src="./../../assets/img/icon_success.png" />
			</b-col>
			<b-col md="4" v-show="!doneSync">
				<b-progress
					class="mb-3"
					:max="max"
					variant="warning"
					height="1.3rem"
					show-progress
				>
					<b-progress-bar :value="processValue">
						<strong>{{ processValue }}%</strong>
					</b-progress-bar>
				</b-progress>
				<p
					style="font-size: 16px; text-align: center;"
					v-html="message"
				></p>
			</b-col>
		</b-row>
		<div class="text-center box-warning">
			<p v-show="doneSync" class="success">{{ success }}</p>
			<span class="warning">
				<img
					style="position: relative; top: -2px; left: -2px;"
					src="./../../assets/img/icon_warning.png"
				/>
				<span v-show="!doneSync">{{ warning }} </span>
				<span v-show="doneSync">
					{{ messageRedirect }} {{ timeWait }}{{ second }}
				</span>
			</span>
		</div>
		<b-modal
			ref="notifyResync"
			centered
			title="Thông báo"
			@ok="reSync"
			@cancel="cancelResync"
		>
			<div slot="modal-ok">Đồng ý</div>
			<div slot="modal-cancel">Hủy</div>
			<p class="my-6" style="text-align: left">
				Quá trình đồng bộ không thành công, bạn có muốn thực hiện lại?
			</p>
		</b-modal>
	</b-container>
</template>
<script>
import { mapState, mapActions, mapMutations } from "vuex";
import mixins from "../../mixins";

export default {
	name: "Sync",
	mixins: [mixins],
	data() {
		return {
			max: 100,
			message: "",
			warning:
				"Vui lòng không đóng trình duyệt hoặc ngắt kết nối trong quá trình đồng bộ",
			messageRedirect: "",
			second: "",
			doneSync: false,
			success: "",
			timeWait: 0,
			processValue: 0
		};
	},
	watch: {
		processBar(value) {
			this.processValue = parseFloat(value.toFixed(2));
		}
	},
	computed: {
		...mapState("Step", ["currentStep", "lastStep"]),
		...mapState(["processBar", "branch"])
	},
	methods: {
		...mapActions("Category", ["syncCategory"]),
		...mapActions("Product", ["syncProduct"]),
		...mapActions("PriceBook", ["syncPriceBook"]),
		...mapActions("Branch", ["syncBranch", "syncStock"]),
		...mapMutations("Step", ["setCurrentStep"]),
		...mapMutations(["resetProcessBar", "resetProcessBarGetData"]),
		async execSync() {
			if (this.currentStep == this.lastStep) {
				this.message = "Đang đồng bộ <span class='type'>Chi nhánh</span>";
				await this.syncBranch();

				this.message = "Đang đồng bộ <span class='type'>Nhóm hàng</span>";
				await this.syncCategory();

				this.message = "Đang đồng bộ <span class='type'>Hàng hóa</span>";
				await this.syncProduct();

				// Đồng bộ bảng giá
				this.message = "Đang đồng bộ <span class='type'>Bảng giá</span>";
				await this.syncPriceBook();

				// Đồng bộ tồn kho
				this.message = "Đang đồng bộ <span class='type'>Tồn kho</span>";
				await this.syncStock();

				await this.finishSync();
			}
		},
		finishSync() {
			this.message = "";
			this.warning = "";
			this.success = "Đồng bộ hàng hóa thành công";
			this.messageRedirect =
				"Hệ thống sẽ tự động chuyển sang trang Danh sách hàng hóa đồng bộ sau ";
			this.doneSync = true;
			this.timeWait = 5;
			this.second = "s";
			// count down 5s redirect
			let redirect = setInterval(() => {
				this.timeWait--;
				if (this.timeWait <= 0) {
					clearInterval(redirect);
					// eslint-disable-next-line no-undef
					window.location.replace(wp_obj.urlProduct);
				}
			}, 1000);
		},
		cancelResync() {
			this.$refs["notifyResync"].hide();
			location.reload();
		},
		async reSync() {
			this.resetProcessBar(0);
			await this.execSync();
		}
	},
	mounted() {
		this.execSync();
	}
};
</script>
