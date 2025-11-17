{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.before') !!}

<!-- Guest Address Vue Component -->
<v-checkout-address-guest
    :cart="cart"
    @processing="stepForward"
    @processed="stepProcessed"
></v-checkout-address-guest>

{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.after') !!}

@include('shop::checkout.onepage.address.form')

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-guest-template"
    >
        <!-- Address Form -->
        <x-shop::form
            v-slot="{ meta, errors, handleSubmit }"
            as="div"
        >
            <form @submit="handleSubmit($event, addAddress)" ref="guestAddressForm">
                <!-- Guest Billing Address -->
                <div class="mb-4">
                    {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.before') !!}

                    <!-- Billing Address Header -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-medium max-md:text-lg max-sm:text-base">
                            @lang('shop::app.checkout.onepage.address.billing-address')
                        </h2>
                    </div>
                
                    <!-- Billing Address Form -->
                    <v-checkout-address-form
                        control-name="billing"
                        :address="cart.billing_address || undefined"
                        v-on:required-complete="onRequiredComplete"
                    ></v-checkout-address-form>

                    <!-- Use for Shipping Checkbox -->
                    <x-shop::form.control-group
                        class="!mb-0 flex items-center gap-2.5"
                        v-if="cart.have_stockable_items"
                    >
                        <x-shop::form.control-group.control
                            type="checkbox"
                            name="billing.use_for_shipping"
                            id="use_for_shipping"
                            for="use_for_shipping"
                            value="1"
                            @change="useBillingAddressForShipping = ! useBillingAddressForShipping"
                            ::checked="!! useBillingAddressForShipping"
                        />

                        <label
                            class="cursor-pointer select-none text-base text-zinc-500 max-md:text-sm max-sm:text-xs ltr:pl-0 rtl:pr-0"
                            for="use_for_shipping"
                        >
                            @lang('shop::app.checkout.onepage.address.same-as-billing')
                        </label>
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.after') !!}
                </div>

                <!-- Guest Shipping Address -->
                <template v-if="cart.have_stockable_items">
                    <div
                        class="mt-8"
                        v-if="! useBillingAddressForShipping"
                    >
                        {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.shipping.before') !!}

                        <!-- Shipping Address Header -->
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-medium max-md:text-lg max-sm:text-base">
                                @lang('shop::app.checkout.onepage.address.shipping-address')
                            </h2>
                        </div>
                    
                        <!-- Shipping Address Form -->
                        <v-checkout-address-form
                            control-name="shipping"
                            :address="cart.shipping_address || undefined"
                            v-on:required-complete="onRequiredComplete"
                        ></v-checkout-address-form>

                        {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.shipping.after') !!}
                    </div>
                </template>

                <!-- Proceed Button -->
                <div class="mt-4 flex justify-end">
                    <x-shop::button
                        class="primary-button rounded-2xl px-11 py-3 max-md:w-full max-md:max-w-full max-md:rounded-lg"
                        :title="trans('shop::app.checkout.onepage.address.proceed')"
                        ::loading="isStoring"
                        ::disabled="isStoring"
                    />
                </div>
            </form>
        </x-shop::form>
    </script>

    <script type="module">
        app.component('v-checkout-address-guest', {
            template: '#v-checkout-address-guest-template',

            props: ['cart'],

            emits: ['processing', 'processed'],

            data() {
                return {
                    useBillingAddressForShipping: true,

                    isStoring: false,

                    requiredFormsCompleted: {
                        billing: false,
                        shipping: false,
                    },
                }
            },

            created() {
                if (this.cart.billing_address) {
                    this.useBillingAddressForShipping = this.cart.billing_address.use_for_shipping;
                }
            },

            methods: {
                onRequiredComplete(name) {
                    if (name === 'billing') {
                        this.requiredFormsCompleted.billing = true;
                    } else if (name === 'shipping') {
                        this.requiredFormsCompleted.shipping = true;
                    }

                    const needShipping = this.cart.have_stockable_items && !this.useBillingAddressForShipping;
                    const ready = needShipping
                        ? (this.requiredFormsCompleted.billing && this.requiredFormsCompleted.shipping)
                        : this.requiredFormsCompleted.billing;

                    if (ready && !this.isStoring) {
                        const form = this.$refs.guestAddressForm;
                        if (form && typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else if (form) {
                            const btn = form.querySelector('button[type="submit"], button');
                            btn && btn.click();
                        }
                    }
                },
                addAddress(params, { setErrors }) {
                    this.isStoring = true;

                    params['billing']['use_for_shipping'] = this.useBillingAddressForShipping;

                    this.moveToNextStep();

                    this.$axios.post('{{ route('shop.checkout.onepage.addresses.store') }}', params)
                        .then((response) => {
                            this.isStoring = false;

                            if (response.data.data.redirect_url) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                if (this.cart.have_stockable_items) {
                                    this.$emit('processed', response.data.data.shippingMethods);
                                } else {
                                    this.$emit('processed', response.data.data.payment_methods);
                                }
                            }
                        })
                        .catch(error => {
                            this.isStoring = false;

                            if (error.response.status == 422) {
                                const rawErrors = error.response.data.errors || {};
                                const normalized = {};

                                Object.keys(rawErrors).forEach((key) => {
                                    const fixedKey = key.replace(/(\.address)\.(\d+)/, '$1.[$2]');
                                    normalized[fixedKey] = rawErrors[key];
                                });

                                setErrors(normalized);

                                if (error.response.data.message) {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                                }
                            }
                        });
                },

                moveToNextStep() {
                    if (this.cart.have_stockable_items) {
                        this.$emit('processing', 'shipping');
                    } else {
                        this.$emit('processing', 'payment');
                    }
                }
            }
        });
    </script>
@endPushOnce