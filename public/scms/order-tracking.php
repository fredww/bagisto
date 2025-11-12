<?php
/**
 * 函数说明（中文）：获取 Vite 开发服务器（hot）地址，如果存在返回其 URL
 * Purpose (English): Return Vite dev server URL if the hot file exists
 */
function getHotServerUrl(): ?string
{
    // Read Laravel Vite hot file inside `public/`
    $hotFile = __DIR__ . '/../shop-default-vite.hot';

    if (is_file($hotFile)) {
        $url = trim(@file_get_contents($hotFile));
        return $url ? rtrim($url, '/') : null; // ensure no trailing slash
    }

    return null;
}

/**
 * 函数说明（中文）：读取主题构建目录中的 Vite manifest.json
 * Purpose (English): Read Vite manifest.json from the shop theme build directory
 */
function readShopManifest(): ?array
{
    // Build directory relative to `public/`
    $manifestPath = __DIR__ . '/../themes/shop/default/build/manifest.json';

    if (is_file($manifestPath)) {
        $json = @file_get_contents($manifestPath);
        $data = $json ? @json_decode($json, true) : null;
        return is_array($data) ? $data : null;
    }

    return null;
}

/**
 * 函数说明（中文）：生成 CSS 样式标签，自动适配 dev（hot）与 build（manifest）两种模式
 * Purpose (English): Generate CSS <link> tags for dev (hot) or build (manifest) modes
 */
function renderShopCssLinks(): string
{
    $hotUrl = getHotServerUrl();
    $tags = '';

    if ($hotUrl) {
        // Dev server: directly reference source entry
        $cssSrc = $hotUrl . '/src/Resources/assets/css/app.css';
        $tags .= '<link rel="preload" as="style" href="' . htmlspecialchars($cssSrc, ENT_QUOTES) . '" />' . "\n";
        $tags .= '<link rel="stylesheet" href="' . htmlspecialchars($cssSrc, ENT_QUOTES) . '" />' . "\n";
        return $tags;
    }

    // Build mode: read manifest and add hashed css
    $manifest = readShopManifest();
    $base = '/themes/shop/default/build/';

    if ($manifest) {
        // Prefer CSS listed under JS entry for proper code-split outputs
        $jsEntryKey = 'src/Resources/assets/js/app.js';
        if (!empty($manifest[$jsEntryKey]['css']) && is_array($manifest[$jsEntryKey]['css'])) {
            foreach ($manifest[$jsEntryKey]['css'] as $cssRelPath) {
                $href = $base . ltrim($cssRelPath, '/');
                $tags .= '<link rel="preload" as="style" href="' . htmlspecialchars($href, ENT_QUOTES) . '" />' . "\n";
                $tags .= '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES) . '" />' . "\n";
            }
        }

        // Also include direct CSS entry if present
        $cssEntryKey = 'src/Resources/assets/css/app.css';
        if (!empty($manifest[$cssEntryKey]['file'])) {
            $href = $base . ltrim($manifest[$cssEntryKey]['file'], '/');
            $tags .= '<link rel="preload" as="style" href="' . htmlspecialchars($href, ENT_QUOTES) . '" />' . "\n";
            $tags .= '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES) . '" />' . "\n";
        }
    }

    return $tags; // Empty when no build yet; page still loads
}

/**
 * 函数说明（中文）：生成 JS 脚本标签，自动适配 dev（hot）与 build（manifest）两种模式
 * Purpose (English): Generate JS <script type="module"> tag for dev (hot) or build (manifest)
 */
function renderShopJsModule(): string
{
    $hotUrl = getHotServerUrl();

    if ($hotUrl) {
        $src = $hotUrl . '/src/Resources/assets/js/app.js';
        return '<script type="module" src="' . htmlspecialchars($src, ENT_QUOTES) . '"></script>' . "\n";
    }

    $manifest = readShopManifest();
    $base = '/themes/shop/default/build/';
    $jsEntryKey = 'src/Resources/assets/js/app.js';

    if (!empty($manifest[$jsEntryKey]['file'])) {
        $src = $base . ltrim($manifest[$jsEntryKey]['file'], '/');
        return '<script type="module" src="' . htmlspecialchars($src, ENT_QUOTES) . '"></script>' . "\n";
    }

    // No build found; return empty to avoid 404s
    return '';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <title>Order Tracking</title>

    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="content-language" content="en" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="title" content="Order Tracking" />
    <meta name="description" content="Order Tracking" />
    <meta name="keywords" content="Order Tracking" />

    <!-- Favicon (fallback to project default in public/) -->
    <link rel="icon" sizes="16x16" href="/favicon.ico" />

    <!-- Auto-loaded Shop Theme assets (dev hot or built manifest) -->
    <?php echo renderShopCssLinks(); ?>
    <?php echo renderShopJsModule(); ?>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap" />
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap" />

    <!-- Optional Toggle Switch CSS (static) -->
    <link rel="stylesheet" href="https://kiaoa.com/themes/shop/default/assets/css/toggle-switch.css" />

    <!-- Speculation Rules to prerender non-account/checkout routes -->
    <script type="speculationrules">
      {"prerender":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":"account"}},{"not":{"href_matches":"checkout"}},{"not":{"href_matches":"onepage"}},{"not":{"href_matches":"cart"}}]},"eagerness":"moderate"}]}
    </script>
</head>

<body>
    <a href="#main" class="skip-to-main-content-link">Skip to main content</a>

    <div id="app">
        <!-- Flash Message Blade Component -->
        <v-flash-group ref="flashes"></v-flash-group>

        <!-- Confirm Modal Blade Component -->
        <v-modal-confirm ref="confirmModal"></v-modal-confirm>

        <!-- Page Header Blade Component (shimmer placeholders until app.js mounts) -->
        <header class="shadow-gray sticky top-0 z-10 bg-white shadow-sm max-lg:shadow-none">
            <v-header-switcher>
                <div class="flex flex-wrap max-lg:hidden">
                    <div class="flex min-h-[78px] w-full justify-between border border-b border-l-0 border-r-0 border-t-0 px-[60px] max-1180:px-8">
                        <div class="flex items-center gap-x-10 max-[1180px]:gap-x-5">
                            <span class="shimmer block h-[29px] w-[131px] rounded" role="presentation"></span>
                            <div class="flex items-center gap-5">
                                <span class="shimmer h-6 w-20 rounded" role="presentation"></span>
                                <span class="shimmer h-6 w-20 rounded" role="presentation"></span>
                                <span class="shimmer h-6 w-20 rounded" role="presentation"></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-x-9 max-[1100px]:gap-x-6 max-lg:gap-x-8">
                            <div class="relative w-full max-w-[445px]">
                                <span class="shimmer block h-[42px] w-[250px] rounded-lg px-11 py-3" role="presentation"></span>
                            </div>
                            <div class="mt-1.5 flex gap-x-8 max-[1100px]:gap-x-6 max-lg:gap-x-8">
                                <span class="shimmer h-6 w-6 rounded" role="presentation"></span>
                                <span class="shimmer h-6 w-6 rounded" role="presentation"></span>
                                <span class="shimmer h-6 w-6 rounded" role="presentation"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4 px-4 pb-4 pt-6 shadow-sm lg:hidden">
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center gap-x-1.5">
                            <span class="shimmer block h-6 w-6 rounded" role="presentation"></span>
                            <span class="shimmer block h-[29px] w-[131px] rounded" role="presentation"></span>
                        </div>
                        <div class="flex items-center gap-x-5 max-md:gap-x-4">
                            <span class="shimmer block h-6 w-6 rounded" role="presentation"></span>
                            <span class="shimmer block h-6 w-6 rounded" role="presentation"></span>
                            <span class="shimmer block h-6 w-6 rounded" role="presentation"></span>
                        </div>
                    </div>
                    <div class="flex w-full items-center">
                        <div class="relative w-full">
                            <span class="shimmer block h-[42px] w-full rounded-xl px-11 py-3.5 max-md:rounded-lg" role="presentation"></span>
                        </div>
                    </div>
                </div>
            </v-header-switcher>
        </header>

        <!-- Page Content -->
        <main id="main" class="bg-white">
            <div class="container mt-8 px-[60px] max-lg:px-8 container-tracking" style="padding:80px;text-align:center">
                <p style="margin:20px 0"><b>Order Tracking</b></p>
                <p></p>
                <!-- Tracking number input box. -->
                <input type="text" id="YQNum" maxlength="50" style="border:solid 1px;border-radius:5px;height: 40px;text-align: center;min-width:200px" />
                <!-- The button is used to call script method. -->
                <input type="button" value="TRACK" onclick="doTrack()" style="background:#cccccc;border-radius:5px;margin-left:5px;padding: 0 10px;height: 40px;" />
                <!-- Container to display the tracking result. -->
                <div id="YQContainer"></div>

                <!-- 17TRACK external script -->
                <script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
                <script type="text/javascript">
                    function doTrack() {
                        var num = document.getElementById('YQNum').value;
                        if (num === '') {
                            alert('Enter your number.');
                            return;
                        }
                        YQV5.trackSingle({
                            YQ_ContainerId: 'YQContainer',
                            YQ_Height: 560,
                            YQ_Fc: '0',
                            YQ_Lang: 'en',
                            YQ_Num: num
                        });
                    }
                </script>
            </div>

            <style>
                div.container-tracking {
                    padding: 50px !important;
                    margin: auto !important;
                }

                div.container-tracking input {
                    border: solid 1px !important;
                    border-color: #cccccc !important;
                    border-width: 1px !important;
                }

                #YQContainer {
                    padding: 50px !important;
                    margin: auto !important;
                }

                #YQContainer input {
                    border: solid 1px !important;
                    border-color: #cccccc !important;
                    border-width: 1px !important;
                }
            </style>
        </main>

        <!-- Features (static content) -->
        <div class="container mt-20 max-lg:px-8 max-md:mt-10 max-md:px-4">
            <div class="max-md:max-y-6 flex justify-center gap-6 max-lg:flex-wrap max-md:grid max-md:grid-cols-2 max-md:gap-x-2.5 max-md:text-center">
                <div class="flex items-center gap-5 bg-white max-md:grid max-md:gap-2.5 max-sm:gap-1 max-sm:px-2">
                    <span class="icon-truck flex items-center justify-center w-[60px] h-[60px] bg-white border border-black rounded-full text-4xl text-navyBlue p-2.5 max-md:m-auto max-md:w-16 max-md:h-16 max-sm:w-10 max-sm:h-10 max-sm:text-2xl" role="presentation"></span>
                    <div class="max-lg:grid max-lg:justify-center">
                        <p class="font-dmserif text-base font-medium max-md:text-xl max-sm:text-sm">Free Shipping</p>
                        <p class="mt-2.5 max-w-[217px] text-sm font-medium text-zinc-500 max-md:mt-0 max-md:text-base max-sm:text-xs">Free shipping on all items over $59.99.</p>
                    </div>
                </div>
                <div class="flex items-center gap-5 bg-white max-md:grid max-md:gap-2.5 max-sm:gap-1 max-sm:px-2">
                    <span class="icon-product flex items-center justify-center w-[60px] h-[60px] bg-white border border-black rounded-full text-4xl text-navyBlue p-2.5 max-md:m-auto max-md:w-16 max-md:h-16 max-sm:w-10 max-sm:h-10 max-sm:text-2xl" role="presentation"></span>
                    <div class="max-lg:grid max-lg:justify-center">
                        <p class="font-dmserif text-base font-medium max-md:text-xl max-sm:text-sm">Product Replace</p>
                        <p class="mt-2.5 max-w-[217px] text-sm font-medium text-zinc-500 max-md:mt-0 max-md:text-base max-sm:text-xs">Easy Product Replacement Available!</p>
                    </div>
                </div>
                <div class="flex items-center gap-5 bg-white max-md:grid max-md:gap-2.5 max-sm:gap-1 max-sm:px-2">
                    <span class="icon-dollar-sign flex items-center justify-center w-[60px] h-[60px] bg-white border border-black rounded-full text-4xl text-navyBlue p-2.5 max-md:m-auto max-md:w-16 max-md:h-16 max-sm:w-10 max-sm:h-10 max-sm:text-2xl" role="presentation"></span>
                    <div class="max-lg:grid max-lg:justify-center">
                        <p class="font-dmserif text-base font-medium max-md:text-xl max-sm:text-sm">Emi Available</p>
                        <p class="mt-2.5 max-w-[217px] text-sm font-medium text-zinc-500 max-md:mt-0 max-md:text-base max-sm:text-xs">No cost EMI available on all major credit cards</p>
                    </div>
                </div>
                <div class="flex items-center gap-5 bg-white max-md:grid max-md:gap-2.5 max-sm:gap-1 max-sm:px-2">
                    <span class="icon-support flex items-center justify-center w-[60px] h-[60px] bg-white border border-black rounded-full text-4xl text-navyBlue p-2.5 max-md:m-auto max-md:w-16 max-md:h-16 max-sm:w-10 max-sm:h-10 max-sm:text-2xl" role="presentation"></span>
                    <div class="max-lg:grid max-lg:justify-center">
                        <p class="font-dmserif text-base font-medium max-md:text-xl max-sm:text-sm">24/7 Support</p>
                        <p class="mt-2.5 max-w-[217px] text-sm font-medium text-zinc-500 max-md:mt-0 max-md:text-base max-sm:text-xs">Dedicated 24/7 support via chat and email</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>