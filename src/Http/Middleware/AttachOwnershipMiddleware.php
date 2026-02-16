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
     * Ownable models aliases (class => name).
     *
     * @var array
     */
    protected $ownableModelAliases;

    /**
     * @var \Sowailem\Ownable\Services\OwnershipService
     */
    protected $ownershipService;

    /**
     * @var \Sowailem\Ownable\Services\OwnableModelService
     */
    protected $ownableModelService;

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $activeOwnableModels;

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
        $this->ownableModelAliases = $this->ownableModelService->getActiveModelClassesWithNames();
        $this->activeOwnableModels = $this->ownableModelService->getActiveModels();

        $response = $next($request);

        if (!config('ownable.automatic_attachment.enabled', true)) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getOriginalContent();
            $modifiedData = $this->attachOwnershipToData($data);
            $response->setData($modifiedData);
            
            // Debug if needed
            // fwrite(STDERR, "Modified Data: " . print_r($response->getData(true), true) . "\n");
        } else {
            // Handle case where response content is just JSON string
            $content = $response->getContent();
            $data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $modifiedData = $this->attachOwnershipToData($data);
                $response->setContent(json_encode($modifiedData));
            }
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
            
            $isOwnable = isset($this->ownableModelAliases[$class]);
            $ownedItemsLoaded = $data->relationLoaded('ownedItems');

            if ($ownedItemsLoaded) {
                $array = $data->toArray();

                if (isset($array['owned_items'])) {
                    $array['owned_items'] = $this->transformOwnedItems($array['owned_items']);
                }

                if ($isOwnable) {
                    $ownership = $this->ownershipService->getCurrentOwnership($class, $data->getKey());

                    if ($ownership) {
                        $array[$key] = $this->transformOwnership($ownership);
                    }
                }
                
                return $array;
            }

            if ($isOwnable) {
                $ownership = $this->ownershipService->getCurrentOwnership($class, $data->getKey());

                if ($ownership) {
                    $data->setAttribute($key, $this->transformOwnership($ownership));
                }
            }
            
            return $data;
        }

        if (is_array($data)) {
            // Check if this array looks like a serialized model with owned_items
            if (isset($data['owned_items'])) {
                $data['owned_items'] = $this->transformOwnedItems($data['owned_items']);
            }

            foreach ($data as $key => $value) {
                $data[$key] = $this->attachOwnershipToData($value);
            }
            return $data;
        }

        return $data;
    }

    /**
     * Transform an ownership model for response.
     *
     * @param Ownership $ownership
     * @return array
     */
    protected function transformOwnership(Ownership $ownership): array
    {
        $ownershipArray = $ownership->toArray();
        
        // Replace class names with unique names
        if (isset($this->ownableModelAliases[$ownershipArray['ownable_type']])) {
            $ownershipArray['ownable_type'] = $this->ownableModelAliases[$ownershipArray['ownable_type']];
        }
        
        if (isset($this->ownableModelAliases[$ownershipArray['owner_type']])) {
            $ownershipArray['owner_type'] = $this->ownableModelAliases[$ownershipArray['owner_type']];
        } else {
            $ownershipArray['owner_type'] = class_basename($ownershipArray['owner_type']);
        }

        return $ownershipArray;
    }

    /**
     * Transform owned items to grouped and filtered format.
     *
     * @param array $ownedItems
     * @return array
     */
    protected function transformOwnedItems(array $ownedItems): array
    {
        if (empty($ownedItems)) {
            return [];
        }

        // Group by ownable_type
        $grouped = [];
        foreach ($ownedItems as $item) {
            if (!isset($item['ownable_type']) || !isset($item['ownable'])) {
                continue;
            }

            $type = $item['ownable_type'];
            $ownable = $item['ownable'];

            // Find matching OwnableModel config
            $ownableModel = $this->activeOwnableModels->firstWhere('model_class', $type);
            
            $typeName = $ownableModel ? $ownableModel->name : class_basename($type);
            $fields = $ownableModel ? $ownableModel->response_fields : null;

            if ($fields && is_array($fields)) {
                $ownable = array_intersect_key($ownable, array_flip($fields));
            }

            $grouped[$typeName][] = $ownable;
        }

        // Convert to the required structure: [{ "type": [items] }, ...]
        $result = [];
        foreach ($grouped as $name => $items) {
            $result[] = [$name => $items];
        }

        return $result;
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
        
        return isset($this->ownableModelAliases[$class]);
    }
}
