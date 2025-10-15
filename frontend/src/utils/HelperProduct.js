import get from "lodash.get";
import Helper from "./Helper";
import { indexDbService } from "../services/indexDbService";

export default {
	getProductVariant: function(product, regularPrice, salePrice, branchStock) {
		const self = this;
		// get product has variant
		const productVariantAll = product.filter(productVariant => {
			return productVariant.hasVariants;
		});

		// get product master
		const productVariantsMaster = productVariantAll.reduce(
			(filtered, product) => {
				if (!product.masterProductId) {
					filtered.push([product]);
					// add product variant master unit
					if (product.units) {
						product.units.forEach(unit => {
							const productMasterUnit = productVariantAll.find(
								productVariant => {
									return productVariant.id == unit.id;
								}
							);

							if (productMasterUnit) {
								filtered.push([productMasterUnit]);
							}
						});
					}
				}
				return filtered;
			},
			[]
		);

		productVariantAll.forEach(productVariant => {
			if (productVariant.masterProductId && productVariant.hasVariants) {
				if (!productVariant.masterUnitId) {
					const index = productVariantsMaster.findIndex(
						productVariantMaster => {
							return (
								productVariantMaster[0].id ==
								productVariant.masterProductId
							);
						}
					);
					if (productVariantsMaster[index]) {
						productVariantsMaster[index].push(productVariant);
					}
				} else {
					// Add product variant unit
					const productMaster = productVariantsMaster.find(product => {
						return product[0].id === productVariant.masterProductId;
					});

					if (productMaster) {
						if (get(productMaster[0], "units")) {
							const productMasterUnitId = productMaster[0].units.find(
								productUnit => {
									return productUnit.unit == productVariant.unit;
								}
							);

							if (productMasterUnitId) {
								const index = productVariantsMaster.findIndex(
									product => {
										return product[0].id === productMasterUnitId.id;
									}
								);

								if (index !== -1) {
									const checkExits = productVariantsMaster[index].find(
										product => {
											return product.id == productVariant.id;
										}
									);

									if (!checkExits) {
										productVariantsMaster[index].push(productVariant);
									}
								}
							}
						}
					}
				}
			}
		});

		const productVariants = productVariantsMaster;
		const productVariant = [];

		productVariants
			// tranform product variation (array in array)
			.map(productVariant => {
				return productVariant.map(product => {
					return self.transformProduct(
						product,
						"variation",
						0,
						regularPrice,
						salePrice,
						branchStock,
						productVariant[0].id
					);
				});
			})
			// get attribute parent
			.forEach(products => {
				const attributeParent = self.getAttributeParent(products);

				// transform parent product
				const parentProduct = self.transformProductVariantMaster(
					products,
					attributeParent
				);

				productVariant.push(parentProduct);
				products.forEach(item => {
					productVariant.push(item);
				});
			});

		return productVariant;
	},
	getProductSimple: function(products, regularPrice, salePrice, branchStock) {
		const self = this;
		return products
			.filter(product => {
				return !product.hasVariants;
			})
			.map(product => {
				return self.transformProduct(
					product,
					"simple",
					0,
					regularPrice,
					salePrice,
					branchStock
				);
			});
	},
	getRegularPrice: function(product, regularPriceId) {
		let regularPrice = 0;
		if (!regularPriceId || regularPriceId === -1) {
			regularPrice = product.basePrice;
		} else {
			if (product.priceBooks) {
				const pricebook = product.priceBooks.find(value => {
					return value.priceBookId == regularPriceId;
				});

				regularPrice = pricebook ? pricebook.price : product.basePrice;
			} else {
				regularPrice = product.basePrice;
			}
		}
		return regularPrice;
	},
	getSalePrice: function(product, salePriceId) {
		let salePrice = "";
		if (!salePriceId) {
			salePrice = "";
		} else if (salePriceId === -1) {
			salePrice = product.basePrice;
		} else {
			if (product.priceBooks) {
				const pricebook = product.priceBooks.find(value => {
					return value.priceBookId == salePriceId;
				});

				salePrice = pricebook ? pricebook.price : "";
			} else {
				salePrice = "";
			}
		}
		return salePrice;
	},
	getStock: function(product, branchStockId) {
		let stock = 0;
		if (!branchStockId) {
			stock = 0;
		} else {
			if (product.inventories) {
				const inventory = product.inventories.find(value => {
					return value.branchId == branchStockId;
				});

				stock = inventory
					? parseFloat(inventory.onHand) - parseFloat(inventory.reserved)
					: 0;
			} else {
				stock = 0;
			}
		}

		stock = stock < 0 ? 0 : Math.floor(stock);

		return stock;
	},
	getLowStock: function(product, branchStockId) {
		let lowStock = 0;
		if (!branchStockId) {
			lowStock = 0;
		} else {
			if (product.inventories) {
				const inventory = product.inventories.find(value => {
					return value.branchId == branchStockId;
				});

				lowStock = inventory ? inventory.minQuantity : 0;
			} else {
				lowStock = 0;
			}
		}

		return lowStock;
	},
	transformProduct: function(
		product,
		type,
		id = 0,
		regularPriceId = 0,
		salePriceId = 0,
		branchStock = 0,
		masterProductId = 0
	) {
		const regular_price = this.getRegularPrice(product, regularPriceId);
		const sale_price = this.getSalePrice(product, salePriceId);
		const stock = this.getStock(product, branchStock);
		const low_stock = this.getLowStock(product, branchStock);
		const data = {
			id: id,
			kv_id: product.id,
			data_raw: JSON.stringify(product),
			type: type,
			sku: product.code,
			nameMaster: product.name,
			name: product.name,
			description: product.description ? product.description : "",
			stock_quantity: stock,
			sale_price: sale_price,
			regular_price: regular_price,
			category_kv: product.categoryId,
			manage_stock: product.type == 3 ? false : true,
			weight: product.weight,
			low_stock_amount: low_stock
		};

		if (masterProductId) {
			data.master_product_id = masterProductId;
		} else {
			data.master_product_id =
				product.hasVariants && product.masterProductId
					? product.masterProductId
					: product.id;
		}

		// set image
		if (product.images) {
			if (product.images.length == 1) {
				data.raw_image_id = product.images[0];
			}

			if (product.images.length > 1) {
				data.raw_image_id = product.images[0];
				data.raw_gallery_image_ids = product.images
					.filter((value, key) => {
						return key > 0;
					})
					.map(image => {
						return image.trim();
					});
			}
		} else {
			data.raw_image_id = [];
			data.raw_gallery_image_ids = [];
		}

		// set attribute
		if (product.attributes) {
			data.raw_attributes = product.attributes.map(attribute => {
				return {
					name: attribute.attributeName,
					value: [attribute.attributeValue],
					visible: true,
					taxonomy: true
				};
			});
		} else {
			data.raw_attributes = [];
		}

		// set time sale
		if (product.priceBooks) {
			if (sale_price !== "") {
				const priceBookSales = product.priceBooks.filter(value => {
					return value.priceBookId == salePriceId;
				});

				if (priceBookSales.length) {
					const startDate = new Date(priceBookSales[0].startDate);
					const endDate = new Date(priceBookSales[0].endDate);
					data.date_on_sale_from = startDate;
					data.date_on_sale_to = endDate;
				}
			}
		}

		return data;
	},
	transformProductVariantMaster: function(product, attributes) {
		const data = Helper.cloneObject(product[0]);
		data.id = 0;
		data.type = "variable";
		data.sku = product[0].sku + "Master";
		data.name = product[0].nameMaster;
		data.sale_price = "";
		data.regular_price = "";
		data.master_product_id = product[0].kv_id;
		data.stock_quantity = product.reduce((filtered, option) => {
			return parseInt(option.stock_quantity) + filtered;
		}, 0);
		data.raw_attributes = attributes;
		return data;
	},
	getAttributeParent: function(products) {
		const attributeParent = [];
		const arrayName = [];
		products.forEach(product => {
			product.raw_attributes.forEach(raw_attribute => {
				if (arrayName.indexOf(raw_attribute.name) == -1) {
					arrayName.push(raw_attribute.name);
					attributeParent.push({
						name: raw_attribute.name,
						value: [raw_attribute.value[0]]
					});
				} else {
					const index = attributeParent.findIndex(value => {
						return value.name == raw_attribute.name;
					});

					if (
						attributeParent[index].value.indexOf(
							raw_attribute.value[0]
						) == -1
					) {
						attributeParent[index].value.push(raw_attribute.value[0]);
					}
				}
			});
		});

		const uniqueAttributeParent = attributeParent.map(value => {
			const attribute = value.value.filter((value, index, self) => {
				return self.indexOf(value) === index;
			});

			return {
				name: value.name,
				value: attribute,
				visible: true,
				taxonomy: true
			};
		});

		return uniqueAttributeParent;
	},
	getProductByIndexDb: async function() {
		const products = await indexDbService.getAll("products");
		return products;
	},
	getProductChildVariant: function(products) {
		return products.filter(product => {
			return (
				product.isActive &&
				product.hasVariants &&
				product.masterProductId &&
				product.masterUnitId !== product.masterProductId
			);
		});
	},
	mergeProductVariantChild: function(products = [], productChildVariants) {
		products.forEach(product => {
			if (product.hasVariants && !product.masterProductId) {
				productChildVariants.forEach(productChildVariant => {
					if (product.id == productChildVariant.masterProductId) {
						const existFlag = products.find(item => {
							return item.id == productChildVariant.id;
						});

						if (!existFlag) {
							products.push(productChildVariant);
						}
					}
				});
			}
		});

		return products;
	},
	filterProductKiotviet(product) {
		return product
			.filter(value => {
				return (
					(!value.isActive &&
						value.hasVariants &&
						!value.masterProductId &&
						this.checkChildActive(value.id, product)) ||
					(value.isActive &&
						(!value.hasVariants ||
							(value.hasVariants && !value.masterProductId) ||
							(value.hasVariants &&
								value.masterProductId &&
								value.masterProductId == value.masterUnitId)))
				);
			})
			.sort((a, b) => {
				return a.id > b.id ? -1 : a.id < b.id ? 1 : 0;
			});
	},
	checkChildActive(productId, products) {
		let hasChild = false;
		products.forEach(item => {
			if (item.masterProductId === productId && item.isActive === true) {
				hasChild = true;
				return false;
			}
		});
		return hasChild;
	}
};
