<?php

namespace Webkul\Marketing\Repositories;

use Webkul\Marketing\Models\AbandonedOrderTemplate;

class AbandonedOrderTemplateRepository
{
    public function allActive()
    {
        return AbandonedOrderTemplate::query()->where('active', 1)->orderBy('trigger_hours')->get();
    }

    public function find(int $id): ?AbandonedOrderTemplate
    {
        return AbandonedOrderTemplate::query()->find($id);
    }

    public function create(array $data): AbandonedOrderTemplate
    {
        return AbandonedOrderTemplate::query()->create($data);
    }

    public function update(AbandonedOrderTemplate $template, array $data): AbandonedOrderTemplate
    {
        $template->fill($data)->save();

        return $template;
    }
}

