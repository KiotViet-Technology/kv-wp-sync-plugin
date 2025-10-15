export default {
	STEPS: [
		{
			id: 1,
			title: "Cấu hình",
			component: "ConfigInfoSync",
			icon: "cog",
			display: true
		},
		{
			id: 2,
			title: "Hàng hóa",
			component: "ConfigProduct",
			icon: "box",
			display: true
		},
		{
			id: 3,
			title: "Bảng giá",
			component: "ConfigPriceBook",
			icon: "tag",
			display: true
		},
		{
			id: 4,
			title: "Đơn hàng",
			component: "ConfigBranch",
			icon: "clipboard-list",
			display: true
		}
	],
	PROCESS_BAR: {
		categories: 30,
		products: 50,
		branchs: 10,
		priceBooks: 10
	}
};
