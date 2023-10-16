<?php

namespace VI\MoonShineSpatieMediaLibrary\Fields;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use MoonShine\Fields\Image;
use Spatie\MediaLibrary\HasMedia;

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

    protected function resolvePreview(): View|string
    {

        if ($this->isRawMode()) {
            return $this->isMultiple() ?
                implode(';', $this->value->map(fn($media): string => $media->getFullUrl())->toArray())
                : $this->value?->getFullUrl();
        }

        return view(
            'moonshine::ui.image',
            $this->isMultiple() ? [
                'values' => $this->getFullPathValues(),
            ] : ['value' => current($this->getFullPathValues())]
        );
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

    public function resolveAfterApply(mixed $data): mixed
    {
        $requestValue = $this->requestValue();

        if ($requestValue !== false) {
            if (!$this->isMultiple()) {
                $requestValue = [$requestValue];
            }

            foreach ($requestValue as $file) {
                $this->addMedia($data, $file);
            }
        }

        return null;
    }

    private function addMedia(HasMedia $item, UploadedFile $file): void
    {
        $item->addMedia($file)
            ->preservingOriginal()
            ->toMediaCollection($this->column());
    }
}
