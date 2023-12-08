<?php

namespace Adeliom\EasyGutenbergBundle\Form;

use Adeliom\EasyGutenbergBundle\Blocks\BlockParser;
use Adeliom\EasyGutenbergBundle\Validator\ValidGutenberg;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GutenbergType extends AbstractType
{
    private BlockParser $parser;

    public function __construct(BlockParser $parser)
    {
        $this->parser = $parser;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $blocks = $this->parser->parse($event->getData());
            foreach ($blocks as $block) {
                $meta = $this->parser->validate($block);
                if (!empty($meta['errors'])) {
                    $block->setMode('edit');
                } else {
                    $block->setMode('preview');
                }
            }
            $event->setData($this->parser->serialize($blocks));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['pattern'] = null;
        unset($view->vars['attr']['pattern']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new ValidGutenberg(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return TextareaType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'gutenberg';
    }
}
