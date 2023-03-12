<?php

namespace Alexwenzel\DependencyContainer;

use Aqjw\MedialibraryField\Fields\Medialibrary;
use Aqjw\MedialibraryField\Fields\Support\MediaCollectionRules;
use Illuminate\Support\Arr;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Support\Str;

class DependencyContainer extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'dependency-container';

    /**
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * DependencyContainer constructor.
     *
     * @param      $fields
     * @param null $attribute
     * @param null $resolveCallback
     */
    public function __construct($fields, $attribute = null, $resolveCallback = null)
    {
        parent::__construct('', $attribute, $resolveCallback);

        $this->withMeta(['fields' => $fields]);
        $this->withMeta(['dependencies' => []]);
    }

    /**
     * Adds a dependency
     *
     * @param $field
     * @param $value
     * @return $this
     */
    public function dependsOn($field, $value)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                $this->getFieldLayout($field, $value),
            ]),
        ]);
    }

    /**
     * Adds a dependency for not
     *
     * @param $field
     * @return DependencyContainer
     */
    public function dependsOnNot($field, $value)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                array_merge($this->getFieldLayout($field), ['not' => $value]),
            ]),
        ]);
    }

    /**
     * Adds a dependency for not empty
     *
     * @param $field
     * @return DependencyContainer
     */
    public function dependsOnEmpty($field)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                array_merge($this->getFieldLayout($field), ['empty' => true]),
            ]),
        ]);
    }

    /**
     * Adds a dependency for not empty
     *
     * @param $field
     * @return DependencyContainer
     */
    public function dependsOnNotEmpty($field)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                array_merge($this->getFieldLayout($field), ['notEmpty' => true]),
            ]),
        ]);
    }

    /**
     * Adds a dependency for null or zero (0)
     *
     * @param $field
     * @param $value
     * @return $this
     */
    public function dependsOnNullOrZero($field)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                array_merge($this->getFieldLayout($field), ['nullOrZero' => true]),
            ]),
        ]);
    }

    /**
     * Adds a dependency for in
     *
     * @param $field
     * @param $array
     * @return $this
     */
    public function dependsOnIn($field, $array)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                array_merge($this->getFieldLayout($field), ['in' => $array]),
            ]),
        ]);
    }

    /**
     * Adds a dependency for not in
     *
     * @param $field
     * @param $array
     * @return $this
     */
    public function dependsOnNotIn($field, $array)
    {
        return $this->withMeta([
            'dependencies' => array_merge($this->meta['dependencies'], [
                array_merge($this->getFieldLayout($field), ['notin' => $array]),
            ]),
        ]);
    }

    /**
     * Get layout for a specified field. Dot notation will result in {field}.{property}. If no dot was found it will
     * result in {field}.{field}, as it was in previous versions by default.
     *
     * @param $field
     * @param $value
     * @return array
     */
    protected function getFieldLayout($field, $value = null)
    {
        if (count(($field = explode('.', $field))) === 1) {
            // backwards compatibility, property becomes field
            $field[1] = $field[0];
        }
        return [
            // literal form input name
            'field'    => $field[0],
            // property to compare
            'property' => $field[1],
            // value to compare
            'value'    => $value,
        ];
    }

    /**
     * Resolve dependency fields for display
     *
     * @param mixed $resource
     * @param null  $attribute
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        foreach ($this->meta['fields'] as $field) {
            $field->resolveForDisplay($resource);
        }

        foreach ($this->meta['dependencies'] as $index => $dependency) {

            $this->meta['dependencies'][$index]['satisfied'] = false;

            if (array_key_exists('empty', $dependency) && empty($resource->{$dependency['property']})) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
                continue;
            }
            // inverted `empty()`
            if (array_key_exists('notEmpty', $dependency) && !empty($resource->{$dependency['property']})) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
                continue;
            }
            // inverted
            if (array_key_exists('nullOrZero', $dependency) && in_array($resource->{$dependency['property']},
                    [null, 0, '0'], true)) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
                continue;
            }

            if (array_key_exists('not', $dependency) && $resource->{$dependency['property']} != $dependency['not']) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
                continue;
            }

            if (array_key_exists('in', $dependency) && in_array($resource->{$dependency['property']}, $dependency['in'])) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
                continue;
            }

            if (array_key_exists('notin', $dependency) && !in_array($resource->{$dependency['property']}, $dependency['notin'])) {
                $this->meta['dependencies'][$index]['satisfied'] = true;
                continue;
            }

            if (array_key_exists('value', $dependency)) {
                if (is_array($resource)) {
                    if (isset($resource[$dependency['property']]) && $dependency['value'] == $resource[$dependency['property']]) {
                        $this->meta['dependencies'][$index]['satisfied'] = true;
                    }
                    continue;
                } elseif ($dependency['value'] == $resource->{$dependency['property']}) {
                    $this->meta['dependencies'][$index]['satisfied'] = true;
                    continue;
                }
                // @todo: quickfix for MorphTo
                $morphable_attribute = $resource->getAttribute($dependency['property'] . '_type');
                if ($morphable_attribute !== null && Str::endsWith($morphable_attribute, '\\' . $dependency['value'])) {
                    $this->meta['dependencies'][$index]['satisfied'] = true;
                    continue;
                }
            }

        }
    }

    /**
     * Resolve dependency fields
     *
     * @param mixed  $resource
     * @param string $attribute
     * @return array|mixed
     */
    public function resolve($resource, $attribute = null)
    {
        foreach ($this->meta['fields'] as $field) {
            $field->resolve($resource, $attribute);
        }
    }

    /**
     * Forward fillInto request for each field in this container
     *
     * @trace fill/fillForAction -> fillInto -> *
     *
     * @param NovaRequest $request
     * @param             $model
     * @param             $attribute
     * @param null        $requestAttribute
     */
    public function fillInto(NovaRequest $request, $model, $attribute, $requestAttribute = null)
    {
        $callbacks = [];

        foreach ($this->meta['fields'] as $field) {
            /** @var Field $field */
            $callbacks[] = $field->fill($request, $model);
        }

        return function () use ($callbacks) {
            foreach ($callbacks as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                }
            }
        };
    }

    /**
     * Checks whether to add validation rules
     *
     * @param NovaRequest $request
     * @return bool
     */
    public function areDependenciesSatisfied(NovaRequest $request)
    {
        if (!isset($this->meta['dependencies'])
            || !is_array($this->meta['dependencies'])) {
            return false;
        }

        $satisfiedCounts = 0;
        foreach ($this->meta['dependencies'] as $index => $dependency) {

            // dependsOnEmpty
            if (array_key_exists('empty', $dependency) && empty($request->has($dependency['property']))) {
                $satisfiedCounts++;
            }

            // dependsOnNotEmpty
            if (array_key_exists('notEmpty', $dependency) && !empty($request->has($dependency['property']))) {
                $satisfiedCounts++;
            }

            // dependsOnNullOrZero
            if (array_key_exists('nullOrZero', $dependency)
                && in_array($request->get($dependency['property']), [null, 0, '0', ''], true)) {
                $satisfiedCounts++;
            }

            // dependsOnIn
            if (array_key_exists('in', $dependency)
                && in_array($request->get($dependency['property']), $dependency['in'])) {
                $satisfiedCounts++;
            }

            // dependsOnNotIn
            if (array_key_exists('notin', $dependency)
                && !in_array($request->get($dependency['property']), $dependency['notin'])) {
                $satisfiedCounts++;
            }

            // dependsOnNot
            if (array_key_exists('not', $dependency) && $dependency['not'] != $request->get($dependency['property'])) {
                $satisfiedCounts++;
            }

            // dependsOn
            if (array_key_exists('value', $dependency)
                && !array_key_exists('in', $dependency)
                && !array_key_exists('notin', $dependency)
                && !array_key_exists('nullOrZero', $dependency)
                && $dependency['value'] == $request->get($dependency['property'])) {
                $satisfiedCounts++;
            }
        }

        return $satisfiedCounts == count($this->meta['dependencies']);
    }

    /**
     * Get a rule set based on field property name
     *
     * @param NovaRequest $request
     * @param string      $propertyName
     * @return array
     */
    protected function getSituationalRulesSet(NovaRequest $request, string $propertyName = 'rules')
    {
        $fieldsRules = [$this->attribute => []];

        // if dependencies are not satisfied
        // or no fields as dependency exist
        // return empty rules for dependency container
        if (!$this->areDependenciesSatisfied($request)
            || !isset($this->meta['fields'])
            || !is_array($this->meta['fields'])) {
            return $fieldsRules;
        }

        /** @var Field $field */
        foreach ($this->meta['fields'] as $field) {
            // if field is DependencyContainer, then add rules from dependant fields
            if ($field instanceof DependencyContainer && $propertyName === "rules") {
                $fieldsRules[Str::random()] = $field->getSituationalRulesSet($request, $propertyName);
            } elseif ($field instanceof Medialibrary) {
                $rules = is_callable($field->{$propertyName})
                    ? call_user_func($field->{$propertyName}, $request)
                    : $field->{$propertyName};

                $fieldsRules[$field->attribute] = MediaCollectionRules::make(
                    $rules,
                    $request,
                    $field,
                );
            } else {
                $fieldsRules[$field->attribute] = is_callable($field->{$propertyName})
                    ? call_user_func($field->{$propertyName}, $request)
                    : $field->{$propertyName};
            }
        }

        // simplify nested rules to one level
        return $this->array_simplify($fieldsRules);
    }

    /**
     * @param $array
     * @return array
     */
    protected function array_simplify($array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_string($key) && is_array($value) && !empty($value)) {
                if (count(array_filter(array_keys($value), 'is_string')) > 0) {
                    $result = array_merge($result, $this->array_simplify($value));
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get the validation rules for this field.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function getRules(NovaRequest $request)
    {
        return $this->getSituationalRulesSet($request);
    }

    /**
     * Get the creation rules for this field.
     *
     * @param NovaRequest $request
     * @return array|string
     */
    public function getCreationRules(NovaRequest $request)
    {
        $fieldsRules = $this->getSituationalRulesSet($request, 'creationRules');

        return array_merge_recursive(
            $this->getRules($request),
            $fieldsRules
        );
    }

    /**
     * Get the update rules for this field.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function getUpdateRules(NovaRequest $request)
    {
        $fieldsRules = $this->getSituationalRulesSet($request, 'updateRules');

        return array_merge_recursive(
            $this->getRules($request),
            $fieldsRules
        );
    }
}
