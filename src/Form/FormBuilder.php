<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Form;

/**
 * Class FormBuilder
 */
class FormBuilder
{
    private array $fields;
    private string $method;
    private string $action;
    private string $enctype;
    private string $name;
    private string $cssClass;
    private string $id;
    private array $fieldRenderers;

    public function __construct()
    {
    }
}
