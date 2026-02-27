<?php

namespace App\Http\Controllers;


use App\Models\Colocation;
use App\Services\ColocationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class ColocationController extends Controller
{
    public function create(){
        return view('colocation.create');
    }
    public function store(Request $request, ColocationService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $coloc = $service->createForOwner($request->user(), $data['name']);

        return redirect()->route('colcoations.show', $coloc);
    }

    public function show(Colcation $colocation)
    {
        $colocation->load(['users']);

        $isMember = $colocation->users()
        ->where('users.id',auth()->id())
        ->whereNull('colocation_user.left_at')
        ->exists();

        if(!$isMember){
            abort(403);
        }

        $myPivot = $colocation-users()->where('users.id',auth()->id())->first()->pivot;

        return view('colocation.show', compact('colocation'))
    }
}
