<template>
	<div class="step">
		<b-table class="table-select" :fields="fields" :items="items">
			<template slot="price_book_kv" slot-scope="data">
				<v-select
					placeholder="Chọn bảng giá"
					label="name"
					v-model="config[data.index]"
					:options="priceBookActive"
				>
					<template slot="no-options">
						Không có kết quả phù hợp
					</template>
					<template slot="clear-selection">
						<img src="./../../../assets/img/icon_trash.png" />
					</template>
				</v-select>
			</template>
		</b-table>

		<div class="step-wrapper text-right">
			<b-button size="md" @click="back">Quay lại</b-button>
			<b-button size="md" @click="saveAndNext" variant="success"
				>Tiếp tục</b-button
			>
			<b-button size="md" @click="skip" variant="danger">Hủy</b-button>
		</div>
	</div>
</template>
<script>
import Vue from "vue";
import { mapState, mapMutations } from "vuex";
import { PriceBookService } from "../../../services/PriceBookService";
import { indexDbService } from "../../../services/indexDbService";

export default {
	name: "ConfigPriceBook",
	data() {
		return {
			config: [],
			fields: [
				{
					key: "price_book_website",
					label: "Chính sách giá trên Website",
					thClass: "thead-table",
					thStyle: "width: inherit"
				},
				{
					key: "price_book_kv",
					label: "Danh sách bảng giá trên KiotViet",
					thClass: "thead-table",
					thStyle: "width: inherit"
				},
				{
					key: "price_book_description",
					label: "Mô tả",
					thClass: "thead-table",
					thStyle: "width: inherit"
				}
			],
			items: [
				{
					price_book_website: "Giá bán (Regular Price)",
					price_book_description: "Giá đồng bộ với giá bán tại Website"
				},
				{
					price_book_website: "Giá khuyến mại (Sale Price)",
					price_book_description:
						"Giá đồng bộ với giá khuyến mại tại Website"
				}
			],
			priceBookKiotvietAll: [],
			configPriceBook: [],
			priceBookActive: []
		};
	},
	watch: {
		async processBarGetData(value) {
			if (Math.ceil(value) >= 100) {
				this.priceBookActive = await this.getPriceBookActive();
				const response = await PriceBookService.getConfig();
				this.configPriceBook = response.data.data;
				if (response.data.status == "success") {
					this.configRegularPrice();
					this.configSalePrice();
				}
			}
		}
	},
	methods: {
		...mapMutations("Step", ["nextStep", "backStep", "setCurrentStep"]),
		...mapMutations("PriceBook", ["setRegularPrice", "setSalePrice"]),
		configRegularPrice() {
			const indexRegularPrice = this.priceBookActive.findIndex(value => {
				if (this.configPriceBook.regular_price) {
					return value.id === this.configPriceBook.regular_price.id;
				}
			});

			if (indexRegularPrice !== -1) {
				Vue.set(this.config, 0, this.configPriceBook.regular_price);
				this.setRegularPrice(this.configPriceBook.regular_price);
			} else {
				Vue.set(this.config, 0, this.priceBookActive[0]);
			}
		},
		configSalePrice() {
			const indexSalePrice = this.priceBookActive.findIndex(value => {
				if (this.configPriceBook.sale_price) {
					return value.id === this.configPriceBook.sale_price.id;
				}
			});

			if (indexSalePrice !== -1) {
				Vue.set(this.config, 1, this.configPriceBook.sale_price);
				this.setSalePrice(this.configPriceBook.sale_price);
			}
		},
		saveAndNext() {
			if (this.checkExitsData()) return;
			this.setRegularPrice(this.config[0]);
			this.setSalePrice(this.config[1]);
			this.nextStep();
		},
		async getPriceBookActive() {
			this.priceBookKiotvietAll = await indexDbService.getAll("priceBooks");
			const priceBookActive = this.priceBookKiotvietAll.filter(value => {
				const endDate = new Date(value.endDate);
				return (
					value.id == -1 ||
					(value.isActive && endDate.getTime() > Date.now())
				);
			});
			return priceBookActive;
		},
		skip() {
			this.setCurrentStep(1);
		},
		back() {
			this.backStep();
		},
		checkExitsData() {
			if (
				this.config[0] &&
				this.config[1] &&
				this.config[0].id == this.config[1].id
			) {
				this.$toaster.error("Không thể chọn cùng 1 bảng giá");
				return true;
			}
		}
	},
	computed: {
		...mapState(["processBarGetData"])
	}
};
</script>
