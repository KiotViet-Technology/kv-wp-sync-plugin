import Vue from "vue";

Vue.directive("click-outside", {
	bind: function(el, binding, vNode) {
		// Provided expression must evaluate to a function.
		if (typeof binding.value !== "function") {
			const compName = vNode.context.name;
			let warn = `[Vue-click-outside:] provided expression '${binding.expression}' is not a function, but has to be`;
			if (compName) {
				warn += `Found in component '${compName}'`;
			}

			console.warn(warn);
		}
		// Define Handler and cache it on the element
		const bubble = binding.modifiers.bubble;
		const handler = e => {
			if (bubble || (!el.contains(e.target) && el !== e.target)) {
				binding.value(e);
			}
		};
		// eslint-disable-next-line no-param-reassign
		el.__vueClickOutside__ = handler;

		// add Event Listeners
		document.addEventListener("click", handler);
	},

	// eslint-disable-next-line no-unused-vars
	unbind: function(el, binding) {
		// Remove Event Listeners
		document.removeEventListener("click", el.__vueClickOutside__);
		// eslint-disable-next-line no-param-reassign
		el.__vueClickOutside__ = null;
	}
});
