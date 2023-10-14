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

    public function resolveFill(array $raw = [], mixed $casted = null, int $index = 0): static
    {
        if ($this->value) {
            return $this;
        }

        if (!$casted instanceof HasMedia) {
            throw new InvalidArgumentException('Model must be an instance of \Spatie\MediaLibrary\HasMedia');
        }

        $value = $casted->getMedia($this->column());

        if (!$this->isMultiple()) {
            $value = $value->first();
        }

        $this->setRawValue($value);

        if (is_closure($this->formattedValueCallback())) {
            $this->setFormattedValue(
                value(
                    $this->formattedValueCallback(),
                    empty($casted) ? $this->toRawValue() : $casted,
                    $index
                )
            );
        }

        $this->setValue($value);

        return $this;
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

    public function afterApply(mixed $data): mixed
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
