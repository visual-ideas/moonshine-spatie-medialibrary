<?php

namespace VI\MoonShineSpatieMediaLibrary\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use MoonShine\Fields\Image;

class MediaLibrary extends Image
{
    protected bool $isDeleteFiles = false;

    protected array $wasRecentlyCreated = [];

    public function hasManyOrOneSave(array|UploadedFile|null $valueOrValues = null): array|string|null
    {
        return $valueOrValues;
    }

    public function afterSave(Model $item): void
    {
        $this->storeMedia($item);
    }

    public function storeMedia(Model $item): void
    {
        $oldValues = request()->collect($this->hiddenOldValuesKey());
        $requestValue = $this->requestValue();

        if ($requestValue) {
            if (!$this->isMultiple()) {
                $requestValue = [$requestValue];
            }

            foreach ($requestValue as $file) {
                $this->addMedia($item, $file);
            }
        }

        $this->removeOldMedia($item, $oldValues);
    }

    private function addMedia(Model $item, UploadedFile $file)
    {
        $media = $item->addMedia($file)
            ->preservingOriginal()
            ->toMediaCollection($this->field());

        $this->wasRecentlyCreated[$media->getUrl()] = $media->getUrl();
    }

    private function removeOldMedia(Model $item, Collection $oldValues): void
    {
        foreach ($item->getMedia($this->field()) as $media) {
            if (!isset($this->wasRecentlyCreated[$media->getUrl()]) && !$oldValues->contains($media->getUrl())) {
                $media->delete();
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

    public function getDir(): string
    {
        return '';
    }

    public function save(Model $item): Model
    {
        return $item;
    }
}
