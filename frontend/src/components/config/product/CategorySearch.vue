<template>
	<b-list-group class="category-search">
		<b-list-group-item class="header">Danh mục hàng hóa</b-list-group-item>
		<b-list-group-item class="search">
			<div class="search-box">
				<img src="../../../assets/img/icon_search.png" />
				<input
					type="text"
					placeholder="Tìm kiếm danh mục"
					v-model="q"
					@input="searchCategory"
					class="input-search"
				/>
			</div>
		</b-list-group-item>
		<div class="category-search-list">
			<CategorySearchItem
				v-for="(item, index) in categorySearch"
				:key="index"
				:item="item"
				:label="item.categoryName"
				:depth="0"
				:q="q"
			></CategorySearchItem>
		</div>
	</b-list-group>
</template>

<script>
import HelperCategory from "../../../utils/HelperCategory";
import CategorySearchItem from "./CategorySearchItem";

export default {
	name: "CategorySearch",
	components: {
		CategorySearchItem
	},
	props: ["categoryKiotvietAll"],
	data() {
		return {
			categorySearch: [],
			categoryParent: [],
			q: ""
		};
	},
	watch: {
		categoryKiotvietAll(value) {
			this.categorySearch = HelperCategory.getNestedChildren(value).filter(
				value => value.parentId == 0
			);
			// add item all category
			this.insertCategoryAll();
			this.categoryParent = this.categorySearch;
		}
	},
	methods: {
		insertCategoryAll() {
			this.categorySearch.unshift({
				categoryId: -1,
				parentId: 0,
				categoryName: "Tất cả",
				children: []
			});
		},
		searchCategory() {
			if (this.q.trim()) {
				const dataSearch = this.categoryKiotvietAll.filter(value => {
					return (
						value.categoryName
							.toLowerCase()
							.indexOf(this.q.toLowerCase().trim()) !== -1
					);
				});

				const categoryParentIds = [];
				dataSearch.forEach(item => {
					categoryParentIds.push(
						HelperCategory.getParentMasterId(
							this.categoryKiotvietAll,
							item
						)
					);
				});

				this.categorySearch = categoryParentIds.filter(
					(value, index, self) => {
						return self.indexOf(value) === index;
					}
				);
			} else {
				this.categorySearch = this.categoryParent;
			}
		}
	}
};
</script>
