<?php

namespace Sowailem\Ownable\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sowailem\Ownable\Models\Ownership;
use Sowailem\Ownable\Models\OwnableModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use Sowailem\Ownable\Services\OwnershipService;
use Sowailem\Ownable\Services\OwnableModelService;

class AttachOwnershipMiddleware
{
    /**
     * Ownable models cache.
     *
     * @var array
     */
    protected $ownableModels;

    /**
     * @var \Sowailem\Ownable\Services\OwnershipService
     */
    protected $ownershipService;

    /**
     * @var \Sowailem\Ownable\Services\OwnableModelService
     */
    protected $ownableModelService;

    /**
     * @param \Sowailem\Ownable\Services\OwnershipService $ownershipService
     * @param \Sowailem\Ownable\Services\OwnableModelService $ownableModelService
     */
    public function __construct(OwnershipService $ownershipService, OwnableModelService $ownableModelService)
    {
        $this->ownershipService = $ownershipService;
        $this->ownableModelService = $ownableModelService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->ownableModels = $this->ownableModelService->getActiveModelClasses();

        $response = $next($request);

        if (!config('ownable.automatic_attachment.enabled', true)) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getOriginalContent();
            $modifiedData = $this->attachOwnershipToData($data);
            $response->setData($modifiedData);
        }

        return $response;
    }

    /**
     * Recursively attach ownership info to data.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function attachOwnershipToData($data): mixed
    {
        if ($data instanceof Collection) {
            return $data->map(fn($item) => $this->attachOwnershipToData($item));
        }

        if ($data instanceof Model) {
            $class = get_class($data);
            $key = config('ownable.automatic_attachment.key', 'ownership');
            
            if (in_array($class, $this->ownableModels)) {
                $ownership = $this->ownershipService->getCurrentOwnership($class, $data->getKey());

                if ($ownership) {
                    $array = $data->toArray();
                    $array[$key] = $ownership->toArray();
                    return $array;
                }
            }
            
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->attachOwnershipToData($value);
            }
            return $data;
        }

        if ($data instanceof \ArrayAccess) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->attachOwnershipToData($value);
            }
        }

        return $data;
    }

    /**
     * Attach ownership info to a model if it's ownable.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function attachToModel(Model $model): Model
    {
        $class = get_class($model);

        if (in_array($class, $this->ownableModels)) {
            $key = config('ownable.automatic_attachment.key', 'ownership');
            
            $ownership = $this->ownershipService->getCurrentOwnership($class, $model->getKey());

            if ($ownership) {
                $model->setAttribute($key, $ownership);
            }
        }

        return $model;
    }

    /**
     * Determine if a model is ownable.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    protected function isOwnable(Model $model): bool
    {
        $class = get_class($model);

        return in_array($class, $this->ownableModels);
    }
}
