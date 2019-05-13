<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace Eurotext\TranslationManagerCategory\Entity;

use Eurotext\TranslationManager\Api\EntityTypeInterface;

class CategoryEntityType implements EntityTypeInterface
{
    const CODE = 'category';
    const DESCRIPTION = 'Category';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getDescription(): string
    {
        return (string)__(self::DESCRIPTION);
    }
}