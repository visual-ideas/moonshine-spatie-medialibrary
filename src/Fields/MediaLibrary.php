<?php

declare(strict_types=1);

namespace VI\MoonShineSpatieMediaLibrary\Fields;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use MoonShine\Support\DTOs\FileItem;
use MoonShine\UI\Fields\Image;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Image
{
    /**
     * @param  array      $raw
     * @param  mixed|null $casted
     * @return mixed
     */
    protected function prepareFill(array $raw = [], mixed $casted = null): mixed
    {
        $value = $casted->getOriginal()->getMedia($this->getColumn());

        if (! $this->isMultiple()) {
            $value = $value->first();
        }

        return $value;
    }

    /**
     * @return array|null[]|string[]
     */
    public function getFullPathValues(): array
    {
        $values = $this->value;

        if (! $values) {
            return [];
        }

        return $this->isMultiple()
            ? $this->value->map(fn ($media): string => $media->getFullUrl())->toArray()
            : [$this->value?->getFullUrl()];
    }

    /**
     * @return Closure|null
     */
    protected function resolveOnApply(): ?Closure
    {
        return static fn ($item) => $item;
    }

    /**
     * @param mixed $data
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     *
     * @return mixed
     */
    protected function resolveAfterApply(mixed $data): mixed
    {
        $oldValues = request()->collect($this->getHiddenRemainingValuesKey())->map(
            fn ($model) => Media::make(json_decode($model, true))
        );

        $this->orderMedia($oldValues);

        $requestValue = $this->getRequestValue();

        $recentlyCreated = collect();
        if ($requestValue !== false) {
            if (! $this->isMultiple()) {
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

    /**
     * @param  mixed $data
     * @return mixed
     */
    protected function resolveAfterDestroy(mixed $data): mixed
    {
        $data
            ->getOriginal()
            ->getMedia($this->getColumn())
            ->each(fn (Media $media) => $media->delete());

        return $data;
    }

    /**
     * @param HasMedia   $item
     * @param Collection $recentlyCreated
     * @param Collection $oldValues
     */
    private function removeOldMedia(HasMedia $item, Collection $recentlyCreated, Collection $oldValues): void
    {
        foreach ($item->getMedia($this->getColumn()) as $media) {
            if (
                ! $recentlyCreated->contains('id', $media->getKey())
                && ! $oldValues->contains('id', $media->getKey())
            ) {
                $media->delete();
            }
        }
    }

    /**
     * @param HasMedia     $item
     * @param UploadedFile $file
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     *
     * @return Media
     */
    private function addMedia(HasMedia $item, UploadedFile $file): Media
    {
        return $item->addMedia($file)
            ->preservingOriginal()
            ->toMediaCollection($this->getColumn());
    }

    /**
     * @param Collection $recentlyCreated
     */
    private function orderMedia(Collection $recentlyCreated): void
    {
        Media::setNewOrder($recentlyCreated->pluck('id')->toArray());
    }

    /**
     * @return Collection
     */
    protected function getFiles(): Collection
    {
        $mediaItems = collect($this->toValue());

        return collect($this->getFullPathValues())
            ->mapWithKeys(function (string $path, int $index) use ($mediaItems): array {
                $item = $mediaItems->get($index);

                $rawValue = $item instanceof Media ? $item->file_name : $path;

                return [
                    $index => new FileItem(
                        fullPath: $path,
                        rawValue: (string) $rawValue,
                        name: (string) \call_user_func($this->resolveNames(), $path, $index, $this),
                        attributes: \call_user_func($this->resolveItemAttributes(), $path, $index, $this),
                    ),
                ];
            });
    }

    /**
     * @param array|string|null $newValue
     */
    public function removeExcludedFiles(null|array|string $newValue = null): void
    {
        $values = collect([
            $this->toValue(withDefault: false),
        ]);

        $values->diff([$this->getValue()])->each(fn (string $file) => $this->deleteFile($file));
    }

    /**
     * @param  int|string|null $index
     * @return mixed
     */
    public function getRequestValue(int|string|null $index = null): mixed
    {
        return $this->prepareRequestValue(
            $this->getCore()->getRequest()->getFile(
                $this->getRequestNameDot($index),
            ) ?? false
        );
    }

    /**
     * @param  Closure $default
     * @param  mixed   $data
     * @return mixed
     */
    public function apply(Closure $default, mixed $data): mixed
    {
        return $data;
    }
}
