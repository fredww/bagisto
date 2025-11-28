@php
    $channel = core()->getCurrentChannel();
@endphp

<!-- SEO Meta Content -->
@push ('meta')
    <meta
        name="title"
        content="{{ $channel->home_seo['meta_title'] ?? '' }}"
    />

    <meta
        name="description"
        content="{{ $channel->home_seo['meta_description'] ?? '' }}"
    />

    <meta
        name="keywords"
        content="{{ $channel->home_seo['meta_keywords'] ?? '' }}"
    />
@endPush

@push('scripts')
    <script>
        // 将分类数据写入本地存储；当 $categories 未定义时使用空数组回退
        localStorage.setItem('categories', JSON.stringify(@json($categories ?? [])));
    </script>
@endpush

<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{  $channel->home_seo['meta_title'] ?? '' }}
    </x-slot>

    <!-- Loop over the theme customization -->
    <div class="flex flex-col gap-12 pb-12">
        @foreach ($customizations as $customization)
            @php ($data = $customization->options) @endphp

            <!-- Static content -->
            @switch ($customization->type)
                @case ($customization::IMAGE_CAROUSEL)
                    <!-- Image Carousel -->
                    <div class="homepage-hero">
                        <x-shop::carousel
                            :options="$data"
                            aria-label="{{ trans('shop::app.home.index.image-carousel') }}"
                        />
                    </div>

                    @break
                @case ($customization::STATIC_CONTENT)
                    <!-- push style -->
                    @if (! empty($data['css']))
                        @push ('styles')
                            <style>
                                {{ $data['css'] }}
                            </style>
                        @endpush
                    @endif

                    <!-- render html -->
                    @if (! empty($data['html']))
                        <div class="container px-[60px] max-lg:px-8 max-md:px-4">
                            {!! $data['html'] !!}
                        </div>
                    @endif

                    @break
                @case ($customization::CATEGORY_CAROUSEL)
                    <!-- Categories carousel -->
                    <div class="container px-[60px] max-lg:px-8 max-md:px-4">
                        <x-shop::categories.carousel
                            :title="$data['title'] ?? ''"
                            :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                            :navigation-link="route('shop.home.index')"
                            aria-label="{{ trans('shop::app.home.index.categories-carousel') }}"
                        />
                    </div>

                    @break
                @case ($customization::PRODUCT_CAROUSEL)
                    <!-- Product Carousel -->
                    <div class="container px-[60px] max-lg:px-8 max-md:px-4">
                        <x-shop::products.carousel
                            :title="$data['title'] ?? ''"
                            :src="route('shop.api.products.index', array_merge($data['filters'] ?? [], (($data['title'] ?? '') === 'All Products') ? ['exclude_name_contains' => 'zyn'] : []))"
                            :navigation-link="route('shop.search.index', array_merge($data['filters'] ?? [], (($data['title'] ?? '') === 'All Products') ? ['exclude_name_contains' => 'zyn'] : []))"
                            aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                        />
                    </div>

                    @break
            @endswitch
        @endforeach
    </div>
</x-shop::layouts>
