<template>
	<b-col md="12">
		<div class="select-branch">
			<div class="select-branch-logo">
				<img
					src="https://cdn-app.kiotviet.vn/retailler/Content/kiotvietLogo.png"
				/>
			</div>
			<div class="select-branch-loading" v-show="loading === true">
				<b-spinner variant="success"></b-spinner>
				<p>Lấy dữ liệu chi nhánh...</p>
				<b-button variant="danger" @click="stopConnection"
					>Ngắt kết nối</b-button
				>
			</div>
			<div class="select-branch-main" v-show="loading === false">
				<h2>Chọn chi nhánh đồng bộ sản phẩm</h2>
				<span
					style="display: block; margin-bottom: 20px;"
					v-show="currentBranchName !== ''"
					>(Hiện tại: {{ currentBranchName }})</span
				>
				<div class="select-input">
					<v-select
						label="branchName"
						:options="allBranch"
						v-model="selected"
					></v-select>
				</div>
				<b-button variant="success" @click="next">Tiếp tục</b-button>
				<b-button variant="danger" @click="stopConnection"
					>Ngắt kết nối</b-button
				>
			</div>
			<StopConnection ref="StopConnection"></StopConnection>
		</div>
	</b-col>
</template>

<script>
import { mapActions, mapMutations } from "vuex";
import StopConnection from "./connection/StopConnection";
import { indexDbService } from "../../services/indexDbService";
import { AuthService } from "../../services/AuthService";
import { router } from "../../router";
import { StorageService } from "../../services/Storage";
import { BranchService } from "../../services/BranchService";

export default {
	name: "Branch",
	components: {
		StopConnection
	},
	data() {
		return {
			checkLogin: false,
			retailer: "",
			request: {
				branch: {
					currentItem: 0,
					pageSize: 100,
					includeRemoveIds: true,
					orderBy: "id",
					orderDirection: "asc",
					isMykiot: true
				}
			},
			allBranch: [],
			currentBranchName: "",
			selected: null,
			loading: true
		};
	},
	methods: {
		...mapActions("Branch", ["getAllBranch"]),
		...mapMutations(["setConfigKiotviet", "setBranch"]),
		stopConnection() {
			this.$refs["StopConnection"].showModal();
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
					this.retailer = response.data.retailer;
					StorageService.setItem("retailer", this.retailer);
				}
			}
		},
		async createIndexDB(dbName) {
			await indexDbService.openDB(dbName, process.env.VERSION_INDEXDB, {
				async upgrade(db, oldVersion, newVersion, transaction) {
					// Product
					const products = indexDbService.createObjectStore(
						db,
						transaction,
						"products",
						"key"
					);

					indexDbService.createIndex(products, "update");
					indexDbService.createIndex(products, "code", true);
					indexDbService.createIndex(products, "categoryId");

					// Categories
					const categories = indexDbService.createObjectStore(
						db,
						transaction,
						"categories",
						"categoryId"
					);

					indexDbService.createIndex(categories, "update");
					indexDbService.createIndex(categories, "categoryName");
					indexDbService.createIndex(categories, "parentId");

					// Branch
					const branchs = indexDbService.createObjectStore(
						db,
						transaction,
						"branchs",
						"id"
					);

					indexDbService.createIndex(branchs, "update");

					// PriceBooks
					const priceBooks = indexDbService.createObjectStore(
						db,
						transaction,
						"priceBooks",
						"id"
					);

					indexDbService.createIndex(priceBooks, "update");
				}
			});
		},
		async getData() {
			this.allBranch = await this.getAllBranch(this.request.branch);
			this.selected = this.allBranch[0];
		},
		next() {
			this.setBranch(this.selected);
			router.push({
				name: "Config"
			});
		}
	},
	async mounted() {
		await this.verifyLogin();
		if (this.checkLogin) {
			const response = await BranchService.getConfig();
			if (response.data.status == "success") {
				if (response.data.data.config_branch_stock != null) {
					this.currentBranchName =
						response.data.data.config_branch_stock.branchName;
				}
			}
			await this.createIndexDB(this.retailer + "_kiotviet_sync");
			await this.getData();
			this.loading = false;
		}
	}
};
</script>

<style scoped></style>
