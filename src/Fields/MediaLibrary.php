<?php

namespace VI\MoonShineSpatieMediaLibrary\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use MoonShine\Fields\Image;

class MediaLibrary extends Image
{

    public static string $view = 'moonshine::fields.image';

    public function save(Model $item): Model
    {
        return $item;
    }

    public function afterSave(Model $item): void
    {
        if ($this->isCanSave()) {
            $requestValue = $this->requestValue();

            if ($this->isMultiple()) {
                $oldValues = collect(request("hidden_{$this->field()}", []));

                if ($oldValues->count() < $item->getMedia($this->field())->count()) {
                    foreach ($item->getMedia($this->field()) as $media) {
                        if (!$oldValues->contains($media->getUrl())) {
                            $media->delete();
                        }
                    }
                }
                if ($requestValue) {
                    foreach ($requestValue as $file) {
                        $item->addMedia($file)
                            ->preservingOriginal()
                            ->toMediaCollection($this->field());
                    }
                }
            } else {
                if ($requestValue) {
                    $item->addMedia($requestValue)
                        ->preservingOriginal()
                        ->toMediaCollection($this->field());
                }
            }
        }
    }

    public function indexViewValue(Model $item, bool $container = true): string
    {
        if ($this->isMultiple()) {
            return view('moonshine::ui.image', [
                'values' => $item->getMedia($this->field())
                    ->map(fn($value) => $value->getUrl())
                    ->toArray(),
            ])->render();
        }

        $url = $item->getFirstMediaUrl($this->field());

        if (empty($url)) {
            return '';
        }

        return view('moonshine::ui.image', [
            'value' => $url,
        ])->render();
    }

    public function formViewValue(Model $item): Collection|string
    {
        if ($this->isMultiple()) {
            return $item->getMedia($this->field())
                ->map(fn($value) => $value->getUrl());
        }

        return $item->getFirstMediaUrl($this->field());
    }


    public function path(string $value): string
    {
        return '';
    }
}
