<?php

namespace Alexwenzel\DependencyContainer\Http\Controllers;

use Alexwenzel\DependencyContainer\ActionHasDependencies;
use Alexwenzel\DependencyContainer\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\ActionRequest as NovaActionRequest;
use Laravel\Nova\Http\Controllers\ActionController as NovaActionController;

class ActionController extends NovaActionController
{
    /**
     * create custom request from base Nova ActionRequest
     *
     * @param  \Laravel\Nova\Http\Requests\ActionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(NovaActionRequest $request)
    {
        $action = $request->action();

        if (in_array(ActionHasDependencies::class, class_uses_recursive($action))) {
            $request = ActionRequest::createFrom($request);
        }

        return parent::store($request);
    }
}
