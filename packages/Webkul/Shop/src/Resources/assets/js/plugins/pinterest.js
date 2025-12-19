export default {
    install(app) {
        const config = window.__PIN_CONFIG__ || { enabled: false, tagId: '', debug: false, currency: 'USD' };
        const dlog = (...args) => { if (config.debug) console.log('[Pinterest]', ...args); };
        const safe = (...args) => { try { if (window.pintrk) window.pintrk(...args); } catch (e) { dlog('pintrk error', e); } };

        const once = (key) => {
            try {
                const k = `pin_once_${key}`;
                if (sessionStorage.getItem(k)) return false;
                sessionStorage.setItem(k, '1');
                return true;
            } catch (_) { return true; }
        };

        window.PinterestIntegration = {
            trackAddToCart(payload = {}) {
                const p = {
                    event_id: payload.event_id || `addtocart_${Date.now()}`,
                    value: Number(payload.value ?? 0),
                    order_quantity: Number(payload.quantity ?? 1),
                    currency: payload.currency || config.currency || 'USD'
                };
                dlog('addtocart', p);
                safe('track', 'addtocart', p);
            },

            trackCheckout(payload = {}) {
                const p = {
                    event_id: payload.event_id || `checkout_${Date.now()}`,
                    value: Number(payload.value ?? 0),
                    order_quantity: Number(payload.order_quantity ?? 1),
                    currency: payload.currency || config.currency || 'USD'
                };
                dlog('checkout', p);
                safe('track', 'checkout', p);
            },

            trackPageVisit(payload = {}) {
                const p = {
                    event_id: payload.event_id || `pagevisit_${Date.now()}`
                };
                dlog('pagevisit', p);
                safe('track', 'pagevisit', p);
            },

            trackSignup(payload = {}) {
                const p = { event_id: payload.event_id || `signup_${Date.now()}` };
                dlog('signup', p);
                safe('track', 'signup', p);
            },

            trackLead(payload = {}) {
                const p = { event_id: payload.event_id || `lead_${Date.now()}`, lead_type: payload.lead_type || 'Newsletter' };
                dlog('lead', p);
                safe('track', 'lead', p);
            },

            trackSearch(payload = {}) {
                const p = { event_id: payload.event_id || `search_${Date.now()}`, search_query: payload.search_query || '' };
                dlog('search', p);
                safe('track', 'search', p);
            },

            trackViewCategory(payload = {}) {
                const p = { event_id: payload.event_id || `viewcategory_${Date.now()}` };
                dlog('viewcategory', p);
                safe('track', 'viewcategory', p);
            },
        };

        try {
            if (once('pagevisit')) {
                window.PinterestIntegration.trackPageVisit();
            }
        } catch (e) {
            dlog('auto pagevisit error', e);
        }
    },
};
