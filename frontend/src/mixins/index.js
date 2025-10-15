import { mapMutations } from "vuex";

export default {
	methods: {
		...mapMutations([
			"resetProcessBar",
			"resetProcessBarGetData",
			"setFastSync"
		]),
		getComponentParent(componentName) {
			let component = null;
			let parent = this.$parent;
			while (parent && !component) {
				if (parent.$options.name === componentName) {
					component = parent;
				}
				parent = parent.$parent;
			}
			return component;
		},
		getComponentChildren(self, componentName) {
			const component = self.$children.find(children => {
				return children.$options.name === componentName;
			});

			return component;
		},
		resetSetting() {
			this.resetProcessBar(0);
			this.resetProcessBarGetData(0);
		}
	}
};
