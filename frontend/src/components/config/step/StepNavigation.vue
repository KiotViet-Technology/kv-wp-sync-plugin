<template>
	<div>
		<ol class="step-indicator">
			<div class="line-throw"></div>
			<li
				v-for="step in steps"
				is="StepNavigationStep"
				:key="step.id"
				:step="step"
			></li>
		</ol>
	</div>
</template>

<script>
import StepNavigationStep from "./StepNavigationStep";

export default {
	name: "step-navigation",
	components: {
		StepNavigationStep
	},
	props: ["steps"]
};
</script>
