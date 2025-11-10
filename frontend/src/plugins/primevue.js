import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import ToastService from "primevue/toastservice";
import Tooltip from "primevue/tooltip";
import "primeflex/primeflex.css";
import "primeicons/primeicons.css";

export default {
  install(app) {
    app.use(PrimeVue, { theme: { preset: Aura } });
    app.use(ToastService);
    app.directive("tooltip", Tooltip);
  }
};
