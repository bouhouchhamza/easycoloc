<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Models\Colocation;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function store(
        StoreCategoryRequest $request,
        Colocation $colocation,
        CategoryService $service
    ): RedirectResponse {
        $this->authorize('view', $colocation);

        $service->create($colocation, $request->user(), $request->validated('name'));

        return back()->with('success', 'Category created successfully.');
    }

    public function destroy(
        Colocation $colocation,
        Category $category,
        CategoryService $service
    ): RedirectResponse {
        $this->authorize('view', $colocation);

        $service->delete($colocation, $category, request()->user());

        return back()->with('success', 'Category deleted successfully.');
    }
}
