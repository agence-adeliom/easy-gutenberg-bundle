<?php

namespace Adeliom\EasyGutenbergBundle\Admin\Field;

use Adeliom\EasyGutenbergBundle\Form\GutenbergType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class GutenbergField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_RENDER_AS_HTML = TextField::OPTION_RENDER_AS_HTML;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('@EasyGutenberg/crud/field/gutenberg.html.twig')
            ->setFormType(GutenbergType::class)
            ->addCssClass('field-gutenberg')
            ->addJsFiles(Asset::new('https://unpkg.com/react@17.0.2/umd/react.production.min.js')->onlyOnForms())
            ->addJsFiles(Asset::new('https://unpkg.com/react-dom@17.0.2/umd/react-dom.production.min.js')->onlyOnForms())
            ->addJsFiles(Asset::new('bundles/easygutenberg/js/easy-gutenberg.js')->onlyOnForms())
            ->addCssFiles(Asset::new('bundles/easygutenberg/css/easy-gutenberg.css')->onlyOnForms())
            ->setDefaultColumns('col-12')
            ->setCustomOption(self::OPTION_RENDER_AS_HTML, true)
        ;
    }

    /**
     * This option is ignored when using 'renderAsHtml()' to avoid
     * truncating contents in the middle of an HTML tag.
     */
    public function renderAsHtml(bool $asHtml = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_HTML, $asHtml);

        return $this;
    }
}
