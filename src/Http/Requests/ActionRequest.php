<?php

namespace Alexwenzel\DependencyContainer\Http\Requests;

use Alexwenzel\DependencyContainer\HasDependencies;
use Laravel\Nova\Http\Requests\NovaRequest;
use Alexwenzel\DependencyContainer\DependencyContainer;
use Laravel\Nova\Http\Requests\ActionRequest as NovaActionRequest;

class ActionRequest extends NovaActionRequest
{

    use HasDependencies;

    /**
     * Handles child fields.
     *
     * @return void
     */
    public function validateFields()
    {
        $availableFields = [];

        foreach ($this->action()->fields($this->novaRequest()) as $field) {
            if ($field instanceof DependencyContainer) {
                // do not add any fields for validation if container is not satisfied
                if ($field->areDependenciesSatisfied($this)) {
                    $availableFields[] = $field;
                    $this->extractChildFields($field->meta['fields']);
                }
            } else {
                $availableFields[] = $field;
            }
        }

        if ($this->childFieldsArr) {
            $availableFields = array_merge($availableFields, $this->childFieldsArr);
        }

        $this->validate(collect($availableFields)->mapWithKeys(function ($field) {
            return $field->getCreationRules($this);
        })->all());
    }
    
    public function novaRequest() {
        return new NovaRequest;
    }

    
}
