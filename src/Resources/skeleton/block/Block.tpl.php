<?php

declare(strict_types=1);

echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use Adeliom\EasyGutenbergBundle\Blocks\AbstractBlockType;
use Symfony\Component\Form\FormBuilderInterface;

class <?php echo $class_name; ?> extends AbstractBlockType<?php echo "\n"; ?>
{
    public function buildBlock(FormBuilderInterface $builder, array $options): void
    {
        // Implement with your fields
    }

    public static function getName(): string
    {
        return '<?php echo $name; ?>';
    }

    public static function getDescription(): string
    {
        return '<?php echo $description; ?>';
    }

    public static function getIcon(): string
    {
        return '';
    }

    public static function getTemplate(): string
    {
        return "<?php echo $template_name; ?>";
    }
}
