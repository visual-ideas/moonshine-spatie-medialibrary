<?php

namespace VI\MoonShineSpatieMediaLibrary\Fields;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use MoonShine\Fields\Image;
use MoonShine\Traits\Fields\FileDeletable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Image
{

    protected function prepareFill(array $raw = [], mixed $casted = null): mixed
    {
        $value = $casted->getMedia($this->column());

        if (!$this->isMultiple()) {
            $value = $value->first();
        }

        return $value;
    }

    public function getFullPathValues(): array
    {
        $values = $this->value;

        if (!$values) {
            return [];
        }

        return $this->isMultiple()
            ? $this->value->map(fn($media): string => $media->getFullUrl())->toArray()
            : [$this->value?->getFullUrl()];
    }

    protected function resolveOnApply(): ?Closure
    {
        return static fn($item) => $item;
    }

    protected function resolveAfterApply(mixed $data): mixed
    {
        $oldValues = request()->collect($this->hiddenOldValuesKey())->map(
            fn($model) => Media::make(json_decode($model, true))
        );

        $requestValue = $this->requestValue();

        $recentlyCreated = collect();
        if ($requestValue !== false) {
            if (!$this->isMultiple()) {
                $requestValue = [$requestValue];
            }


            foreach ($requestValue as $file) {
                $recentlyCreated->push($this->addMedia($data, $file));
            }
        }

        $this->removeOldMedia($data, $recentlyCreated, $oldValues);

        $this->orderMedia($recentlyCreated, $oldValues);

        return null;
    }

    protected function resolveAfterDestroy(mixed $data): mixed
    {
        $data
            ->getMedia($this->column())
            ->each(fn(Media $media) => $media->delete());

        return $data;
    }

    private function removeOldMedia(HasMedia $item, Collection $recentlyCreated, Collection $oldValues): void
    {
        foreach ($item->getMedia($this->column()) as $media) {
            if (
                !$recentlyCreated->contains('id', $media->getKey())
                && !$oldValues->contains('id', $media->getKey())
            ) {
                $media->delete();
            }
        }
    }

    private function addMedia(HasMedia $item, UploadedFile $file): Media
    {
        return $item->addMedia($file)
            ->preservingOriginal()
            ->toMediaCollection($this->column());
    }

    private function orderMedia(Collection $recentlyCreated, Collection $oldValues): void
    {
        $ids = $oldValues->merge($recentlyCreated)->pluck('id')->toArray();

        Media::setNewOrder($ids);
    }
}
