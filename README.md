
![Adeliom](https://adeliom.com/public/uploads/2017/09/Adeliom_logo.png)
[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=agence-adeliom_easy-faq-bundle)](https://sonarcloud.io/dashboard?id=agence-adeliom_easy-faq-bundle)

# Easy Gutenberg Bundle

Provide Wordpress's Gutenberg Editor into Easyadmin.

## Versions

| Repository Branch | Version | Symfony Compatibility | PHP Compatibility | Status                     |
|-------------------|---------|-----------------------|-------------------|----------------------------|
| `2.x`             | `2.x`   | `5.4`, and `6.x`      | `8.0.2` or higher | New features and bug fixes |


## Installation with Symfony Flex

Add our recipes endpoint

```json
{
  "extra": {
    "symfony": {
      "endpoint": [
        "https://api.github.com/repos/agence-adeliom/symfony-recipes/contents/index.json?ref=flex/main",
        ...
        "flex://defaults"
      ],
      "allow-contrib": true
    }
  }
}
```

Install with composer

```bash
composer require agence-adeliom/easy-gutenberg-bundle
```

## Documentation

### Use GutenbergField

Go to your crud controller, example : `src/Controller/Admin/PageCrudController.php`

```php
<?php

namespace App\Controller\Admin;

...
use App\Entity\EasyFaq\Entry;
use App\Entity\EasyFaq\Category;

abstract class PageCrudController extends AbstractCrudController
{
    ...
    
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            // Add the form theme
            ->addFormTheme('@EasyGutenberg/form/gutenberg_widget.html.twig')
            ;
    }
    
    public function configureFields(string $pageName): iterable
    {
        ...
        yield GutenbergField::new("content");
        ...
```

### Create a new Block

```bash
php bin/console make:gutenberg
```

Then setup your form field

```php
<?php

namespace App\Blocks;

use Adeliom\EasyGutenbergBundle\Blocks\AbstractBlockType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TwoColsType extends AbstractBlockType
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        $builder->add("left_col", TextType::class, ["label" => 'Left column content']);
        $builder->add("right_col", TextType::class, ["label" => 'Right column content']);
    }

    public static function getName(): string
    {
        return 'Two columns';
    }

    public static function getDescription(): string
    {
        return 'Make a two columns layout';
    }

    public static function getIcon(): string
    {
        return '';
    }

    public static function getTemplate(): string
    {
        return "blocks/two_cols.html.twig";
    }
}
```

### Frontend usage

In your template you can use these functions :

```php
# This convert and render the content 
{{ easy_gutenberg(page.content) }}

# This render the blocks's assets must be called after 'easy_gutenberg'
{{ easy_gutenberg_assets() }}
```

### Extra features

#### Add frontend assets

```php
    public static function configureAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
            'webpack' => [],
        ];
    }
```

#### Add backend assets

```php
    public static function configureAdminAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
        ];
    }
```

#### Add extra form themes

```php
    public static function configureAdminFormTheme(): array
    {
        return [];
    }
```

#### Specify a category

The provided categories are:

* common
* text
* media
* design
* widgets
* theme
* embed

```php
    public static function getCategory(): string
    {
        return 'common';
    }
```

#### Specify variations

```php
    public static function getVariations(): array
    {
        return [
            [
                "name": 'variation_with_bg',
                "isDefault": true,
                "title": "Variation With background",
                "icon": '',
                "attributes": [
                    "with-bg": true
                ],
            ]
        ];
    }
```

#### Specify extra attributes

```php
    public static function getAttributes(): array
    {
        return [
             "with-bg" => ['type' => 'boolean']
        ];
    }
```

#### Specify supports

Allowed supports are :

* [anchor](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#anchor)
* [align](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#align)
* [alignWide](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#alignWide)
* [className](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#className)
* [lock](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#lock)


```php
    public static function getSupports(): array
    {
        return array_merge(parent::getSupports(),[
            'align' => false,
        ]);
    }
```

## License

[MIT](https://choosealicense.com/licenses/mit/)


## Authors

- [@arnaud-ritti](https://github.com/arnaud-ritti)
- [@JeromeEngelnAdeliom](https://github.com/JeromeEngelnAdeliom)

  
