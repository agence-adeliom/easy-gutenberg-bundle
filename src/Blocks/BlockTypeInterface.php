<?php

namespace Adeliom\EasyGutenbergBundle\Blocks;

interface BlockTypeInterface
{
    public static function isDynamic(): bool;

    public static function getKey(): string;

    public static function getName(): string;

    public static function getDescription(): string;

    public static function getIcon(): string;

    public static function getPrefix(): string;

    public static function getTemplate(): string;

    public static function configureAssets(): array;

    public static function configureAdminAssets(): array;

    public static function configureAdminFormTheme(): array;

    public static function getCategory(): string;

    public static function getAttributes(): array;

    public static function getVariations(): array;

    public static function getSupports(): array;

    public static function getStyles(): array;

    public function supports(string $objectClass, $instance = null): bool;
}
