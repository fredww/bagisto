<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Google;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Webkul\Admin\Http\Controllers\Controller;

class GoogleFeedController extends Controller
{
    public function index()
    {
        return view('admin::marketing.google.feed');
    }

    public function generate()
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);

        $this->validate(request(), [
            'channel'           => 'nullable|string',
            'locale'            => 'nullable|string',
            'output'            => 'required|string',
            'base_url'          => 'nullable|url',
            'category'          => 'nullable|string',
            'exclude_category'  => 'nullable|string',
        ]);

        $params = [];

        if ($channel = request('channel')) {
            $params['--channel'] = $channel;
        }

        if ($locale = request('locale')) {
            $params['--locale'] = $locale;
        }

        if ($output = request('output')) {
            $params['--output'] = $output;
        }

        if ($baseUrl = request('base_url')) {
            $params['--base-url'] = $baseUrl;
        }

        if ($category = request('category')) {
            $params['--category'] = $category;
        }

        if ($excludeCategory = request('exclude_category')) {
            $params['--exclude-category'] = $excludeCategory;
        }

        try {
            Artisan::call('google:generate-feed', $params);

            $downloadUrl = $this->toPublicUrl(request('output'));

            session()->flash('success', 'Google feed generated successfully');
            if ($downloadUrl) {
                session()->flash('download_url', $downloadUrl);
            } else {
                session()->flash('download_path', request('output'));
            }

            return redirect()->back();
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back();
        }
    }

    protected function toPublicUrl(string $path): ?string
    {
        if (str_starts_with($path, 'public/')) {
            $relative = substr($path, 7);
            return url('/' . ltrim($relative, '/'));
        }

        if ($path !== '' && $path[0] === DIRECTORY_SEPARATOR) {
            $public = public_path();
            if (str_starts_with($path, $public)) {
                $relative = ltrim(substr($path, strlen($public)), '/');
                return url('/' . $relative);
            }
        }

        return null;
    }
}
