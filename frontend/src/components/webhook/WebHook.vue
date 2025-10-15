<template>
	<div class="kvsync-webhook">
		<h2>Danh sách webhook</h2>
		<b-table bordered striped hover :items="webhooks"></b-table>
		<button @click="backToConfig()">Trở về trang đồng bộ</button>
	</div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";
import { router } from "../../router";

export default {
	name: "WebHook",
	computed: mapGetters("Webhook", ["webhooks"]),
	created() {
		this.getAllProductKiotviet();
	},
	methods: {
		...mapActions("Webhook", ["getAllProductKiotviet"]),
		backToConfig() {
			router.push({
				name: "Config"
			});
		}
	}
};
</script>

<style scoped>
h2 {
	font-size: 20px;
	margin-top: 30px;
	text-align: center;
	margin-bottom: 30px;
}
</style>
