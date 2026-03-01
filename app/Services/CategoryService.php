<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function create(Colocation $colocation, User $actor, string $name): Category
    {
        $this->assertOwner($colocation, $actor);

        return Category::query()->create([
            'colocation_id' => $colocation->id,
            'name' => $name,
        ]);
    }

    public function delete(Colocation $colocation, Category $category, User $actor): void
    {
        $this->assertOwner($colocation, $actor);

        if ((int) $category->colocation_id !== (int) $colocation->id) {
            throw ValidationException::withMessages([
                'category' => 'Invalid category for this colocation.',
            ]);
        }

        $category->delete();
    }

    private function assertOwner(Colocation $colocation, User $actor): void
    {
        if ((int) $colocation->owner_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'colocation' => 'Only owner can manage categories.',
            ]);
        }
    }
}
