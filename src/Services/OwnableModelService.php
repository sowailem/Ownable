<?php

namespace Sowailem\Ownable\Services;

use Sowailem\Ownable\Models\OwnableModel;
use Illuminate\Database\Eloquent\Collection;

class OwnableModelService
{
    /**
     * Get ownable models based on filters.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getModels(array $filters = [])
    {
        $query = OwnableModel::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate();
    }

    /**
     * Store a new ownable model.
     *
     * @param array $data
     * @return \Sowailem\Ownable\Models\OwnableModel
     */
    public function storeModel(array $data): OwnableModel
    {
        return OwnableModel::create($data);
    }

    /**
     * Update an existing ownable model.
     *
     * @param \Sowailem\Ownable\Models\OwnableModel $ownableModel
     * @param array $data
     * @return \Sowailem\Ownable\Models\OwnableModel
     */
    public function updateModel(OwnableModel $ownableModel, array $data): OwnableModel
    {
        $ownableModel->update($data);
        return $ownableModel;
    }

    /**
     * Find an ownable model by ID.
     *
     * @param int $id
     * @return \Sowailem\Ownable\Models\OwnableModel|null
     */
    public function findModel(int $id): ?OwnableModel
    {
        return OwnableModel::find($id);
    }

    /**
     * Delete an ownable model.
     *
     * @param \Sowailem\Ownable\Models\OwnableModel $ownableModel
     * @return bool|null
     */
    public function deleteModel(OwnableModel $ownableModel): ?bool
    {
        return $ownableModel->delete();
    }
    /**
     * Get all active ownable model classes with their names as aliases.
     *
     * @return array
     */
    public function getActiveModelClassesWithNames(): array
    {
        $dynamicModels = OwnableModel::where('is_active', true)
            ->pluck('name', 'model_class')
            ->toArray();

        $configModels = config('ownable.ownable_models', []);
        
        // For config models, we don't have a name in config yet, 
        // but we can try to guess it or just use the FQN.
        // If they want names for config models, they should probably move them to DB.
        // For now, let's just use the class name as the name if not present in DB.
        
        $result = $dynamicModels;
        foreach ($configModels as $class) {
            if (!isset($result[$class])) {
                $result[$class] = class_basename($class);
            }
        }

        return $result;
    }

    /**
     * Get all active ownable model classes.
     *
     * @return array
     */
    public function getActiveModelClasses(): array
    {
        return array_keys($this->getActiveModelClassesWithNames());
    }
}
