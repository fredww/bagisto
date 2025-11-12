<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\Core\Rules\PostCode;

class ConfigurationForm extends FormRequest
{
    /**
     * Determine if the Configuration is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return collect(request()->input('keys', []))->mapWithKeys(function ($item) {
            $data = json_decode($item, true);

            return collect($data['fields'])->mapWithKeys(function ($field) use ($data) {
                $key = "{$data['key']}.{$field['name']}";

                // Check delete key exist in the request
                if (! $this->has("{$key}.delete")) {
                    return [$key => $this->getValidationRules($field['validation'] ?? 'nullable')];
                }

                return [];
            })->toArray();
        })->toArray();
    }

    /**
     * Transform validation rules into an array and map custom validation rules
     *
     * @param  string|array  $validation
     * @return array
     */
    protected function getValidationRules($validation)
    {
        $validations = is_array($validation) ? $validation : explode('|', $validation);

        /**
         * 功能说明（中文）：
         * 将系统配置中的校验规则字符串转换为可用的 Laravel 规则；
         * 同时兼容前端不带分隔符的 regex 表达式，自动转换为 Laravel 所需的 `/pattern/` 形式。
         */
        return array_map(function ($rule) {
            return match ($rule) {
                'phone'    => new PhoneNumber,
                'postcode' => new PostCode,
                'decimal'  => new Decimal,
                default    => (
                    // Normalize regex rule for Laravel if it lacks delimiters
                    // Example: 'regex:^G-[A-Za-z0-9\-]{6,}$' -> 'regex:/^G-[A-Za-z0-9\-]{6,}$/'
                    (strpos($rule, 'regex:') === 0)
                        ? (function () use ($rule) {
                            $pattern = substr($rule, 6);

                            // If pattern does not start with '/', wrap with '/'
                            if ($pattern !== '' && $pattern[0] !== '/') {
                                return 'regex:/' . $pattern . '/';
                            }

                            // Already formatted with delimiters
                            return $rule;
                        })()
                        : $rule
                ),
            };
        }, $validations);
    }
}
