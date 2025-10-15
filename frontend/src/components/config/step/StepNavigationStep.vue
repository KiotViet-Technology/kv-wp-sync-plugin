<template>
	<li :class="indicatorclass" v-if="step.display">
		<div class="step" @click="setCurrentStep(step.id)">
			<font-awesome-icon class="mr-1" :icon="step.icon"></font-awesome-icon>
			<span class="title" v-text="step.title"> </span>
		</div>
	</li>
</template>

<script>
import { mapState, mapMutations } from "vuex";

export default {
	name: "step-navigation-step",
	props: ["step"],
	methods: {
		...mapMutations("Step", ["setCurrentStep"])
	},
	computed: {
		...mapState("Step", ["currentStep"]),
		indicatorclass() {
			return {
				complete: this.currentStep >= this.step.id
			};
		}
	}
};
</script>
