<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle\Blocks;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\String\Slugger\AsciiSlugger;

abstract class AbstractBlockType extends AbstractType implements BlockTypeInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildBlock($builder, $options);
    }

    abstract public function buildBlock(FormBuilderInterface $builder, array $options): void;

    abstract public static function getName(): string;

    abstract public static function getIcon(): string;

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = [];
        $attr['block-title'] = static::getName();
        $attr['block-icon'] = static::getIcon();
        $view->vars['attr'] = $attr;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'tabs_class' => 'nav nav-pills nav-stacked',
            'csrf_protection' => false,
            'cascade_validation' => true,
        ]);
    }

    public static function configureAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
            'webpack' => [],
        ];
    }

    public static function configureAdminAssets(): array
    {
        return [
            'js' => [],
            'css' => [],
        ];
    }

    public static function configureAdminFormTheme(): array
    {
        return [];
    }

    public function getPosition(): int
    {
        return 100;
    }

    public static function getDescription(): string
    {
        return '';
    }

    public static function getCategory(): string
    {
        return 'common';
    }

    public static function getVariations(): array
    {
        return [];
    }

    public static function getAttributes(): array
    {
        return [];
    }

    public static function getSupports(): array
    {
        return [
            'html' => false,
        ];
    }

    public static function getStyles(): array
    {
        return [];
    }

    public function supports(string $objectClass, $instance = null): bool
    {
        return true;
    }

    public static function isDynamic(): bool
    {
        return true;
    }

    public static function getKey(): string
    {
        $name = (new CamelCaseToSnakeCaseNameConverter())->normalize(static::getName());

        return sprintf('%s/%s', self::getPrefix(), (new AsciiSlugger())->slug($name)->toString());
    }

    public static function getPrefix(): string
    {
        return 'easy-gutenberg';
    }
}
