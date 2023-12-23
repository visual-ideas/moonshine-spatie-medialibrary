# Spatie\MediaLibrary field for [MoonShine](https://moonshine-laravel.com) Laravel admin panel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/visual-ideas/moonshine-spatie-medialibrary.svg?style=flat-square)](https://packagist.org/packages/visual-ideas/laravel-site-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/visual-ideas/moonshine-spatie-medialibrary.svg?style=flat-square)](https://packagist.org/packages/visual-ideas/moonshine-spatie-medialibrary)

## Compatibility

|       MoonShine       | Moonshine Spatie Medialibrary | Currently supported |
|:---------------------:|:-----------------------------:|:-------------------:|
| \>= v1.52 and < v2.0  |           <= v1.2.0           |         no          |
|        >= v2.0        |           >= v2.0.1           |         yes         |


## Installation
The field is purposed for work with the [Laravel-MediaLibrary](https://github.com/spatie/laravel-medialibrary) 
package made by [Spatie](https://github.com/spatie/laravel-medialibrary) and extends default field
[Image](https://moonshine-laravel.com/docs/section/fields-image)

```php
composer require visual-ideas/moonshine-spatie-medialibrary
```

Before using the Spatie\MediaLibrary field, make sure that:

- The spatie/laravel-medialibrary package is installed and configured
- The visual-ideas/moonshine-spatie-medialibrary package is installed
- The field passed to Spatie\MediaLibrary is added as the name of the collection via ```->addMediaCollection('Field')```

In the model:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
 
class ModelClass extends Model implements HasMedia
{
    use InteractsWithMedia;
 
    //...
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover');
    }
    
    //...
}
```
In the MoonShine:

```php
use VI\MoonShineSpatieMediaLibrary\Fields\MediaLibrary;

//...

MediaLibrary::make('Cover', 'cover'),

//...
```

By default, the field works in a single image mode

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
 
class ModelClass extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    //...
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }
    
    //...
}
```

If you want to use a field to load multiple images, add the ```->multiple()``` method when declaring the field

```php
use VI\MoonShineSpatieMediaLibrary\Fields\MediaLibrary;

//...

MediaLibrary::make('Gallery', 'gallery')->multiple(),

//...
```
