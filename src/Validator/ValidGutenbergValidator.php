<?php

namespace Adeliom\EasyGutenbergBundle\Validator;

use Adeliom\EasyGutenbergBundle\Blocks\BlockParser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidGutenbergValidator extends ConstraintValidator
{
    private BlockParser $parser;

    public function __construct(BlockParser $parser)
    {
        $this->parser = $parser;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidGutenberg) {
            throw new UnexpectedTypeException($constraint, ValidGutenberg::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');
            // separate multiple types using pipes
            // throw new UnexpectedValueException($value, 'string|int');
        }
        $isValid = true;
        $blocks = $this->parser->parse($value);
        foreach ($blocks as $block) {
            $meta = $this->parser->validate($block);
            if (!empty($meta['errors'])) {
                $isValid = false;
                $block->setMode('edit');
            }
        }

        if (!$isValid) {
            $this->context->buildViolation('validation.invalid_block')
                ->setTranslationDomain('EasyGutenbergBundle')
                ->addViolation();
        }
    }
}
