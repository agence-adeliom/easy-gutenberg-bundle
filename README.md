
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

### Customize faq's root path

```yaml
#config/packages/easy_faq.yaml
easy_faq:
  ...
  page:
    root_path: '/blog'
```
NOTE : You will need to clear your cache after change because the RouteLoader need to be cleared.


## License

[MIT](https://choosealicense.com/licenses/mit/)


## Authors

- [@arnaud-ritti](https://github.com/arnaud-ritti)
- [@JeromeEngelnAdeliom](https://github.com/JeromeEngelnAdeliom)

  
