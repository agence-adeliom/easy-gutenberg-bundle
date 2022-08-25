<?php

namespace Adeliom\EasyGutenbergBundle\Admin\Field\Configurator;

use Adeliom\EasyGutenbergBundle\Admin\Field\GutenbergField;
use Adeliom\EasyGutenbergBundle\Blocks\BlockTypeRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

use function Symfony\Component\String\u;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class GutenbergConfigurator implements FieldConfiguratorInterface
{
    private BlockTypeRegistry $collection;

    public function __construct(BlockTypeRegistry $collection)
    {
        $this->collection = $collection;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return GutenbergField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        if (GutenbergField::class === $field->getFieldFqcn()) {
            $field->setFormTypeOptionIfNotSet('attr.data-ea-gutenberg-field', true);
        }

        $blocks = $this->collection->blockTypes();
        foreach ($blocks as $block) {
            if (method_exists($block, 'configureAdminAssets')) {
                $assets = call_user_func([$block, 'configureAdminAssets']);
                if (!empty($assets['js'])) {
                    foreach ($assets['js'] as $file) {
                        $found = false;
                        foreach ($context->getAssets()->getJsAssets() as $assetDto) {
                            if ($assetDto->getValue() === $file) {
                                $found = true;
                            }
                        }

                        if (!$found) {
                            $context->getAssets()->addJsAsset(new AssetDto($file));
                        }
                    }
                }

                if (!empty($assets['css'])) {
                    foreach ($assets['css'] as $file) {
                        $found = false;
                        foreach ($context->getAssets()->getCssAssets() as $assetDto) {
                            if ($assetDto->getValue() === $file) {
                                $found = true;
                            }
                        }

                        if (!$found) {
                            $context->getAssets()->addCssAsset(new AssetDto($file));
                        }
                    }
                }
            }
        }

        if (null === $value = $field->getValue()) {
            return;
        }

        if (!\is_string($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new \RuntimeException(sprintf('The value of the "%s" field of the entity with ID = "%s" can\'t be converted into a string, so it cannot be represented by a TextField or a TextareaField.', $field->getProperty(), $entityDto->getPrimaryKeyValue()));
        }

        $renderAsHtml = $field->getCustomOption(TextField::OPTION_RENDER_AS_HTML);
        if ($renderAsHtml) {
            $formattedValue = (string) $field->getValue();
        } else {
            $formattedValue = htmlspecialchars((string) $field->getValue(), \ENT_NOQUOTES, null, false);
        }

        // when contents are rendered as HTML, "max length" option is ignored to prevent
        // truncating contents in the middle of an HTML tag, which messes the entire backend
        if (!$renderAsHtml) {
            $isDetailAction = Action::DETAIL === $context->getCrud()->getCurrentAction();
            $defaultMaxLength = $isDetailAction ? \PHP_INT_MAX : 64;
            $formattedValue = u($formattedValue)->truncate($defaultMaxLength, '…')->toString();
        }

        $field->setFormattedValue($formattedValue);
    }
}
