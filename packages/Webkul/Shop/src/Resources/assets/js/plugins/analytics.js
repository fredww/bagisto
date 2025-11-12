/**
 * 插件目的：统一管理 Google Ads 与 GA4 的事件上报
 * Purpose: Manage Google Ads and GA4 event reporting in a unified way
 */
export default {
    /**
     * 插件安装入口：挂载 GA 集成对象，并提供常用方法
     * Install entry: mount GA integration object and provide common methods
     */
    install(app) {
        const config = window.__GA_CONFIG__ || {
            ga4Enabled: false,
            measurementId: '',
            adsEnabled: false,
            adsConversionId: '',
            adsPurchaseLabel: '',
            adsAddToCartLabel: '',
            adsBeginCheckoutLabel: '',
            adsPageViewLabel: '',
            debug: false,
            currency: 'USD'
        };

        // Chinese comment: 暴露全局对象，方便在 Blade 或组件中直接调用
        // Expose global object for direct usage in blades or components
        window.GAIntegration = {
            /**
             * 方法：输出调试日志
             * Function: Output debug logs
             */
            debugLog(...args) {
                if (config.debug) console.log('[GA]', ...args);
            },

            /**
             * 方法：获取 Google Ads `send_to` 字段值
             * Function: Compute Google Ads `send_to` parameter
             */
            /**
             * 方法：获取指定标签键的 Google Ads `send_to` 值
             * Chinese: 根据标签键（如 adsPurchaseLabel）拼接 send_to
             * English: Build Ads `send_to` using a specific label key
             */
            getAdsSendTo(labelKey = 'adsPurchaseLabel') {
                try {
                    if (!config.adsEnabled || !config.adsConversionId) return null;
                    const label = config[labelKey];
                    if (!label) return null;
                    return `${config.adsConversionId}/${label}`;
                } catch (e) {
                    window.GAIntegration.debugLog('getAdsSendTo error', e);
                    return null;
                }
            },

            /**
             * 方法：追踪 GA4 add_to_cart 事件
             * Function: Track GA4 add_to_cart event
             */
            trackAddToCart(payload = {}) {
                try {
                    const item = payload.item || {};
                    const currency = payload.currency || config.currency || 'USD';
                    const value = Number(payload.value ?? item.total ?? item.price ?? 0);

                    // internal: items array follows GA4 spec
                    const items = payload.items ?? [{
                        item_id: payload.sku || payload.id || item.sku || item.id || item.product_id || item.id,
                        item_name: payload.name || item.name || '',
                        quantity: Number(payload.quantity ?? item.quantity ?? 1),
                        price: Number(item.price ?? value)
                    }];

                    window.GAIntegration.debugLog('add_to_cart', { currency, value, items });
                    window.gtag && gtag('event', 'add_to_cart', { currency, value, items });
                } catch (e) {
                    window.GAIntegration.debugLog('add_to_cart error', e);
                }
            },

            /**
             * 方法：追踪 Google Ads 的加入购物车转化事件
             * Chinese: 触发 Ads add_to_cart 转化（使用 adsAddToCartLabel）
             * English: Fire Ads add_to_cart conversion (uses adsAddToCartLabel)
             */
            trackAdsAddToCart(payload = {}) {
                try {
                    const sendTo = window.GAIntegration.getAdsSendTo('adsAddToCartLabel');
                    if (!sendTo) return;

                    const currency = payload.currency || config.currency || 'USD';
                    const value = Number(payload.value ?? 1);

                    window.GAIntegration.debugLog('ads add_to_cart', { send_to: sendTo, value, currency });
                    window.gtag && gtag('event', 'conversion', { send_to: sendTo, value, currency });
                } catch (e) {
                    window.GAIntegration.debugLog('ads add_to_cart error', e);
                }
            },

            /**
             * 方法：追踪 GA4 purchase 事件
             * Function: Track GA4 purchase event
             */
            trackPurchase(payload = {}) {
                try {
                    const currency = payload.currency || config.currency || 'USD';
                    const value = Number(payload.value ?? 0);
                    const items = payload.items || [];
                    const transaction_id = payload.transactionId || payload.orderId || undefined;

                    window.GAIntegration.debugLog('purchase', { currency, value, items, transaction_id });
                    window.gtag && gtag('event', 'purchase', { currency, value, items, transaction_id });
                } catch (e) {
                    window.GAIntegration.debugLog('purchase error', e);
                }
            },

            /**
             * 方法：追踪 Google Ads 的购买转化事件
             * Function: Track Google Ads purchase conversion event
             */
            trackAdsPurchase(payload = {}) {
                try {
                    const sendTo = window.GAIntegration.getAdsSendTo('adsPurchaseLabel');
                    if (!sendTo) return;

                    const currency = payload.currency || config.currency || 'USD';
                    const value = Number(payload.value ?? 0);

                    window.GAIntegration.debugLog('ads conversion', { send_to: sendTo, value, currency });
                    window.gtag && gtag('event', 'conversion', { send_to: sendTo, value, currency });
                } catch (e) {
                    window.GAIntegration.debugLog('ads conversion error', e);
                }
            },

            /**
             * 方法：追踪 Google Ads 的 Begin Checkout 转化事件
             * Chinese: 触发 Ads begin_checkout 转化（使用 adsBeginCheckoutLabel）
             * English: Fire Ads begin_checkout conversion (uses adsBeginCheckoutLabel)
             */
            trackAdsBeginCheckout(payload = {}) {
                try {
                    const sendTo = window.GAIntegration.getAdsSendTo('adsBeginCheckoutLabel');
                    if (!sendTo) return;

                    const currency = payload.currency || config.currency || 'USD';
                    const value = Number(payload.value ?? 1);

                    window.GAIntegration.debugLog('ads begin_checkout', { send_to: sendTo, value, currency });
                    window.gtag && gtag('event', 'conversion', { send_to: sendTo, value, currency });
                } catch (e) {
                    window.GAIntegration.debugLog('ads begin_checkout error', e);
                }
            },

            /**
             * 方法：追踪 Google Ads 的 Page View 转化事件
             * Chinese: 触发 Ads page_view 转化（使用 adsPageViewLabel）
             * English: Fire Ads page_view conversion (uses adsPageViewLabel)
             */
            trackAdsPageView(payload = {}) {
                try {
                    const sendTo = window.GAIntegration.getAdsSendTo('adsPageViewLabel');
                    if (!sendTo) return;

                    const currency = payload.currency || config.currency || 'USD';
                    const value = Number(payload.value ?? 1);

                    window.GAIntegration.debugLog('ads page_view', { send_to: sendTo, value, currency });
                    window.gtag && gtag('event', 'conversion', { send_to: sendTo, value, currency });
                } catch (e) {
                    window.GAIntegration.debugLog('ads page_view error', e);
                }
            },

            /**
             * 方法：追踪自定义事件
             * Function: Track a custom event
             */
            trackCustomEvent(name, params = {}) {
                try {
                    window.GAIntegration.debugLog('custom_event', name, params);
                    window.gtag && gtag('event', name, params);
                } catch (e) {
                    window.GAIntegration.debugLog('custom_event error', e);
                }
            },
        };

        // Optional: listen to global events (e.g., mini cart update) – debug only
        if (app.config.globalProperties.$emitter) {
            app.config.globalProperties.$emitter.on('update-mini-cart', (cart) => {
                window.GAIntegration.debugLog('emitter:update-mini-cart', cart);
            });
        }

        // Chinese: 如果配置了 Page View 标签，则在页面加载后自动触发一次
        // English: Auto-fire Ads page_view once per page load if label configured
        try {
            window.GAIntegration.trackAdsPageView();
        } catch (e) {
            window.GAIntegration.debugLog('auto page_view error', e);
        }
    },
};