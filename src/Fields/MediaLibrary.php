<?php

namespace VI\MoonShineSpatieMediaLibrary\Fields;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Support\DTOs\FileItem;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Traits\Fields\FileDeletable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Image
{

    protected function prepareFill(array $raw = [], mixed $casted = null): mixed
    {
        $value = $casted->getOriginal()->getMedia($this->column);

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
        $oldValues = request()->collect($this->getHiddenRemainingValuesKey())->map(
            fn($model) => Media::make(json_decode($model, true))
        );

        $this->orderMedia($oldValues);
        
        $requestValue = $this->getRequestValue();

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

       $this->getData()->getOriginal()->refresh();

        return null;
    }

    protected function resolveAfterDestroy(mixed $data): mixed
    {
        $data
            ->getMedia($this->column)
            ->each(fn(Media $media) => $media->delete());

        return $data;
    }

    private function removeOldMedia(HasMedia $item, Collection $recentlyCreated, Collection $oldValues): void
    {
        foreach ($item->getMedia($this->column) as $media) {
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
            ->toMediaCollection($this->column);
    }

    private function orderMedia(Collection $recentlyCreated): void
    {
        Media::setNewOrder($recentlyCreated->pluck('id')->toArray());
    }

    protected function getFiles(): Collection
    {
        return collect($this->getFullPathValues())
            ->mapWithKeys(fn (string $path, int $index): array => [
                $index => new FileItem(
                    fullPath: $path,
                    rawValue: data_get($this->toValue(), $index, $this->toValue()),
                    name: (string) \call_user_func($this->resolveNames(), $path, $index, $this),
                    attributes: \call_user_func($this->resolveItemAttributes(), $path, $index, $this),
                ),
            ]);
    }

    public function removeExcludedFiles(null|array|string $newValue = null): void
    {
        $values = collect([
            $this->toValue(withDefault: false),
        ]);

        $values->diff([$this->getValue()])->each(fn (string $file) => $this->deleteFile($file));
    }

    public function getRequestValue(int|string|null $index = null): mixed
    {
        return $this->prepareRequestValue(
            $this->getCore()->getRequest()->getFile(
                $this->getRequestNameDot($index),
            ) ?? false
        );
    }

    public function apply(Closure $default, mixed $data): mixed
    {
        $item = parent::apply($default, $data);
        unset($item->{$this->column});

        return $item;
    }
}
