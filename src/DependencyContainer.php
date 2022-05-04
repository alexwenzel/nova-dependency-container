<?php

namespace Alexwenzel\DependencyContainer;

use Laravel\Nova\Fields\Field;

class DependencyContainer extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'dependency-container';
}
