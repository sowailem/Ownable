<?php

namespace Sowailem\Ownable\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
            $originalData = $response->getOriginalContent();
            $data = $response->getData(true);

            if ($originalData instanceof JsonResource && isset($data['data'])) {
                $data = $this->attachOwnershipToData($data['data'], $originalData);
            } else {
                $data = $this->attachOwnershipToData($data, $originalData);
            }

            $response->setData($data);
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
     * @param mixed $original
     * @return mixed
     */
    protected function attachOwnershipToData($data, $original = null): mixed
    {
        if ($data instanceof Collection) {
            return $data->map(fn($item, $key) => $this->attachOwnershipToData($item, $original[$key] ?? null));
        }

        if ($original instanceof JsonResource) {
            $original = $original->resource;
        }

        if ($original instanceof Model) {
            $class = get_class($original);
            $key = config('ownable.automatic_attachment.key', 'ownership');

            $isOwnable = isset($this->ownableModelAliases[$class]);
            $ownedItemsLoaded = $original->relationLoaded('ownedItems');

            if (is_array($data)) {
                if ($ownedItemsLoaded) {
                    $data['owned_items'] = $this->transformOwnedItems($original->ownedItems->toArray());
                }

                if ($isOwnable) {
                    $ownership = $this->ownershipService->getCurrentOwnership($class, $original->getKey());

                    if ($ownership) {
                        $data[$key] = $this->transformOwnership($ownership);
                    }
                }

                foreach ($data as $k => $v) {
                    if ($k !== 'owned_items' && $k !== $key) {
                        // This part is tricky because we don't easily know the original for nested items if they are not relations
                        // But if they are relations, we might find them in $original.
                        if ($original->relationLoaded($k)) {
                            $data[$k] = $this->attachOwnershipToData($v, $original->getRelation($k));
                        }
                    }
                }

                return $data;
            }
        }

        if ($data instanceof Model) {
            // This part might still be needed for non-JsonResponse or other cases
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
            if (isset($data['owned_items']) && $original instanceof Model && $original->relationLoaded('ownedItems')) {
                $data['owned_items'] = $this->transformOwnedItems($original->ownedItems->toArray());
            }

            foreach ($data as $key => $value) {
                // If we don't have $original as a model, we can't do much for children here
                // unless we find it.
                $childOriginal = null;
                if ($original instanceof Model && $original->relationLoaded($key)) {
                    $childOriginal = $original->getRelation($key);
                } elseif (is_array($original) || $original instanceof Collection) {
                    $childOriginal = $original[$key] ?? null;
                }

                $data[$key] = $this->attachOwnershipToData($value, $childOriginal);
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
