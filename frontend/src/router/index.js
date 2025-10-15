import Vue from "vue";
import Router from "vue-router";
import Branch from "@/components/config/Branch";
import Config from "@/components/config/Config";
import Login from "@/components/login/Login";
import Sync from "@/components/sync/Sync";
import WebHook from "@/components/webhook/WebHook";
import store from "../store/index";

Vue.use(Router);

const router = new Router({
	routes: [
		{
			path: "/login",
			name: "Login",
			component: Login
		},
		{
			path: "/branch",
			name: "Branch",
			component: Branch,
			meta: {
				requiresAuth: true
			}
		},
		{
			path: "/config",
			name: "Config",
			component: Config,
			meta: {
				requiresAuth: true
			}
		},
		{
			path: "/sync",
			name: "Sync",
			component: Sync,
			meta: {
				checkStep: true
			}
		},
		{
			path: "/webhook",
			name: "WebHook",
			component: WebHook,
			meta: {
				requiresAuth: true
			}
		}
	]
});

router.beforeEach((to, from, next) => {
	// Check route require auth
	if (
		to.matched.some(record => record.meta.checkStep) &&
		store._modules.root.state.Step.currentStep !=
			store._modules.root.state.Step.lastStep
	) {
		next({
			name: "Config"
		});
		return true;
	} else {
		next();
	}
});

export default router;

export { router };
