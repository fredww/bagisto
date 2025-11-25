{!! view_render_event('bagisto.shop.layout.footer.before') !!}

<!--
    The category repository is injected directly here because there is no way
    to retrieve it from the view composer, as this is an anonymous component.
-->
@inject('themeCustomizationRepository', 'Webkul\Theme\Repositories\ThemeCustomizationRepository')

<!--
    This code needs to be refactored to reduce the amount of PHP in the Blade
    template as much as possible.
-->
@php
    $channel = core()->getCurrentChannel();

    $customization = $themeCustomizationRepository->findOneWhere([
        'type'       => 'footer_links',
        'status'     => 1,
        'theme_code' => $channel->theme,
        'channel_id' => $channel->id,
    ]);
@endphp

<footer class="mt-9 bg-lightOrange max-sm:mt-10">
    <!--
        自适应重构：统一使用网格布局，避免桌面/移动端两套结构，
        并确保桌面端 4 列显示，减少 Information 与 Subscribe 之间的空隙。
    -->
    <div class="grid grid-cols-4 gap-x-6 gap-y-8 p-[60px] max-md:gap-5 max-md:p-8 max-1060:grid-cols-2 max-sm:grid-cols-1 max-sm:px-4 max-sm:py-5">
        <!-- 公司信息列：统一第一列 -->
        <div class="grid gap-5">
        <h3 class="text-base font-semibold text-gray-800 mb-1 cursor-pointer select-none flex items-center justify-between"
                    data-toggle-for="footer-section-column_0"
                    aria-controls="footer-section-column_0"
                    aria-expanded="true"
                    role="button"
                    tabindex="0">
            <span>Company</span>
            <!-- 中文：箭头图标仅在移动端显示，用于提示可点击展开；英文：Chevron icon shows on mobile to indicate collapsible -->
            <svg data-arrow class="w-4 h-4 ml-2 transition-transform duration-200 text-gray-500" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.5 7.5L10 12l4.5-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </h3>
            @php
                $companyInfoHtml = core()->getConfigData('general.content.footer_company.company_info') ?? '<ul class="grid gap-3 text-sm"><li>Store Name:Kiaoa</li><li>Hours: Monday - Friday : 9am-5pm</li><li>Saturday - Sunday : Closed</li><li>Address:715 S Washington St</li><li>apt c2,Alexandria,VA,22314</li><li>Phoen:+1（703）356-7108</li><li>Email:help@kiaoa.com</li></ul>';
            @endphp

            <div id="footer-section-column_0" class="grid gap-3 text-sm" data-collapsible="mobile">
                {!! $companyInfoHtml !!}
            </div>
        </div>

        <!-- 动态链接列（从主题定制取数据） -->
        @if ($customization?->options)
            @foreach ($customization->options as $columnKey => $footerLinkSection)
                @php
                    // decide collapsible for mobile: column_1, column_2, column_3
                    $isCollapsible = in_array($columnKey, ['column_1', 'column_2', 'column_3']);
                    $columnTitles = [
                        'column_1' => 'Customer Service',
                        'column_2' => 'Information', 
                        'column_3' => 'Company'
                    ];
                    $sectionId = 'footer-section-' . $columnKey;
                @endphp

                <div class="grid gap-3">
                    @if (isset($columnTitles[$columnKey]))
                        <h3
                            class="text-base font-semibold text-gray-800 mb-1 {{ $isCollapsible ? 'cursor-pointer select-none flex items-center justify-between' : '' }}"
                            @if($isCollapsible)
                                data-toggle-for="{{ $sectionId }}"
                                aria-controls="{{ $sectionId }}"
                                aria-expanded="true"
                                role="button"
                                tabindex="0"
                            @endif
                        >
                            <span>{{ $columnTitles[$columnKey] }}</span>
                            @if($isCollapsible)
                                <!-- 中文：箭头图标仅在移动端显示；英文：Chevron icon shows on mobile only -->
                                <svg data-arrow class="w-4 h-4 ml-2 transition-transform duration-200 text-gray-500" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M5.5 7.5L10 12l4.5-4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            @endif
                        </h3>
                    @endif

                    <ul
                        id="{{ $sectionId }}"
                        class="grid gap-3 text-sm"
                        @if($isCollapsible)
                            data-collapsible="mobile"
                        @endif
                    >
                        @php
                            // sort by configured order
                            usort($footerLinkSection, function ($a, $b) {
                                return $a['sort_order'] - $b['sort_order'];
                            });
                        @endphp

                        @foreach ($footerLinkSection as $link)
                            <li>
                                <a href="{{ $link['url'] }}" class="text-gray-600 hover:text-gray-800 transition-colors">
                                    {{ $link['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        @endif

        {!! view_render_event('bagisto.shop.layout.footer.newsletter_subscription.before') !!}

        <!-- 订阅列：作为网格中的一列，保证与其他列对齐 -->
        @if (core()->getConfigData('customer.settings.newsletter.subscription'))
            <div class="grid gap-2.5">
                <p
                    class="max-w-[288px] text-3xl italic leading-[45px] text-navyBlue max-md:text-2xl max-sm:text-lg"
                    role="heading"
                    aria-level="2"
                >
                    @lang('shop::app.components.layouts.footer.newsletter-text')
                </p>

                <p class="text-xs">
                    @lang('shop::app.components.layouts.footer.subscribe-stay-touch')
                </p>

                <div>
                    <x-shop::form
                        :action="route('shop.subscription.store')"
                        class="mt-2.5 rounded max-sm:mt-0"
                    >
                        <div class="relative w-full footer-subscribe">
                            <x-shop::form.control-group.control
                                type="email"
                                class="block w-[420px] max-w-full rounded-xl border-2 border-[#e9decc] bg-[#F1EADF] px-5 py-4 text-base max-1060:w-full max-md:p-3.5 max-sm:mb-0 max-sm:rounded-lg max-sm:border-2 max-sm:p-2 max-sm:text-sm"
                                name="email"
                                rules="required|email"
                                label="Email"
                                :aria-label="trans('shop::app.components.layouts.footer.email')"
                                placeholder="email@example.com"
                            />

                            <x-shop::form.control-group.error control-name="email" />

                            <button
                                type="submit"
                                class="absolute top-1.5 flex w-max items-center rounded-xl bg-white px-7 py-2.5 font-medium hover:bg-zinc-100 max-md:top-1 max-md:px-5 max-md:text-xs max-sm:mt-0 max-sm:rounded-lg max-sm:px-4 max-sm:py-2 ltr:right-2 rtl:left-2"
                            >
                                @lang('shop::app.components.layouts.footer.subscribe')
                            </button>
                        </div>
                    </x-shop::form>
                </div>
            </div>
        @endif

        {!! view_render_event('bagisto.shop.layout.footer.newsletter_subscription.after') !!}
    </div>

    <script>
        /**
         * 中文说明：初始化页脚折叠逻辑
         * 目标：在移动端（<=1060px）默认折叠 Customer Service 与 Information；
         *      桌面端始终展开；点击标题在移动端可展开/折叠。
         */
        (function() {
            // english internal comments
            const mobileBreakpoint = 1060;

            /**
             * 中文：同步 aria-expanded 与箭头方向；英文：Sync aria-expanded and arrow rotation
             */
            function updateToggleVisual(toggle, expanded) {
                // English internal: update aria state
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                // English internal: rotate chevron if exists
                const arrow = toggle.querySelector('[data-arrow]');
                if (arrow) {
                    arrow.classList.toggle('rotate-180', expanded);
                    // fallback: inline transform to ensure rotation even if utility classes fail
                    arrow.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }

            /**
             * 中文说明：根据当前视口宽度设置折叠/展开的初始状态
             * 英文：Apply initial state based on viewport width
             */
            function applyInitialState() {
                const isMobile = window.innerWidth <= mobileBreakpoint;
                const sections = document.querySelectorAll('[data-collapsible="mobile"]');
                const toggles = document.querySelectorAll('[data-toggle-for]');

                sections.forEach(section => {
                    if (isMobile) {
                        section.classList.add('hidden');
                    } else {
                        section.classList.remove('hidden');
                    }
                });

                toggles.forEach(toggle => {
                    // update aria-expanded to match visibility and arrow rotation
                    const targetId = toggle.getAttribute('data-toggle-for');
                    const target = document.getElementById(targetId);
                    if (!target) return;
                    const expanded = !target.classList.contains('hidden');
                    updateToggleVisual(toggle, expanded);
                    // 中文：在移动端显示箭头，在桌面隐藏；英文：show arrow on mobile, hide on desktop
                    const arrow = toggle.querySelector('[data-arrow]');
                    if (arrow) {
                        arrow.style.display = isMobile ? 'inline-block' : 'none';
                    }
                });
            }

            /**
             * 中文说明：为标题绑定点击事件，仅在移动端切换折叠
             * 英文：Attach click handlers for toggles; only active on mobile
             */
            function setupDelegatedHandlers() {
                // 中文：使用事件委托处理点击，确保即使 DOM 动态更新也能响应
                // English: Delegate click handling so it works even with dynamic DOM updates
                document.addEventListener('click', function (evt) {
                    const toggleEl = evt.target.closest('[data-toggle-for]');
                    if (!toggleEl) return;

                    const isMobile = window.innerWidth <= mobileBreakpoint;
                    if (!isMobile) return; // desktop stays expanded

                    const targetId = toggleEl.getAttribute('data-toggle-for');
                    const target = document.getElementById(targetId);
                    if (!target) return;

                    evt.preventDefault();
                    target.classList.toggle('hidden');
                    const expanded = !target.classList.contains('hidden');
                    updateToggleVisual(toggleEl, expanded);
                }, true);

                // 中文：键盘可访问性（Enter/Space）同样委托处理
                // English: Keyboard accessibility (Enter/Space) using delegation
                document.addEventListener('keydown', function (evt) {
                    const toggleEl = evt.target.closest('[data-toggle-for]');
                    if (!toggleEl) return;

                    const isMobile = window.innerWidth <= mobileBreakpoint;
                    if (!isMobile) return;

                    if (evt.key !== 'Enter' && evt.key !== ' ') return;

                    const targetId = toggleEl.getAttribute('data-toggle-for');
                    const target = document.getElementById(targetId);
                    if (!target) return;

                    evt.preventDefault();
                    target.classList.toggle('hidden');
                    const expanded = !target.classList.contains('hidden');
                    updateToggleVisual(toggleEl, expanded);
                }, true);
            }

            /**
             * 中文说明：窗口尺寸变更时，重新应用初始状态，保证桌面端始终展开
             * 英文：Re-apply initial state on resize to enforce desktop expanded
             */
            function handleResize() {
                applyInitialState();
            }

            // 初始化：立即执行，避免 DOMContentLoaded 已触发导致未初始化
            applyInitialState();
            setupDelegatedHandlers();
            window.addEventListener('resize', handleResize);
        })();
    </script>

    <div class="flex justify-between bg-[#F1EADF] px-[60px] py-3.5 max-md:justify-center max-sm:px-5">
        {!! view_render_event('bagisto.shop.layout.footer.footer_text.before') !!}

        <p class="text-sm text-zinc-600 max-md:text-center">
            @lang('shop::app.components.layouts.footer.footer-text', ['current_year'=> date('Y') ])
        </p>

        {!! view_render_event('bagisto.shop.layout.footer.footer_text.after') !!}
    </div>
</footer>

{!! view_render_event('bagisto.shop.layout.footer.after') !!}
