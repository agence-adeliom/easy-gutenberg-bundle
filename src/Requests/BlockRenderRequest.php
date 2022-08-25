<?php

namespace Adeliom\EasyGutenbergBundle\Requests;

use Symfony\Component\Validator\Constraints as Assert;

class BlockRenderRequest extends BaseRequest
{
    #[Assert\Type('string')]
    #[Assert\NotBlank]
    public $blockName;

    #[Assert\Type('array')]
    public $attributes = [];
}
