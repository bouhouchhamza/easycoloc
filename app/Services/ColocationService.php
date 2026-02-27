<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ColocationService
{
    /**
     * Create a new class instance.
     */
    public function userHasActiveColocation(User $user): bool 
    {
        return DB::table('colocation_user')
        ->join('colocations','colocations.id', '=', 'colocation_user.colocation_id')
        ->where('colocation_user.user_id',$user->id)
        ->whereNull('colocation_user.left_at')
        ->where('colocations.status','active')
        ->exists();
    }

    public function createForOwner(User $owner, string $name): Colocation
    {
        return DB::transaction(function () use ($owner, $name) {
          if($this->userHasActiveColocation($owner)){
            throw ValidationException::withMessages([
                'name' => 'Vous avez déja une colocation active.',
            ]);
          }
        return DB::transaction(function() use($owner, $name){
            $coloc = Colocation::create([
                'name'=>$name,
                'owner_id' => $owner->id,
                'invite_token' =>(string) Str::uuid(),
                'status' => 'active',
            ]);
            $coloc->users()->syncWithoutDetaching([
                $owner->id =>['role' => 'owner', 'left_at' => null],
            ]);
            return $coloc;
        });
        });
    }
    public function joinByToken(User $user, string $token): ?Colocation
    {
        if($this->userHasActiveColocation($user)){
            throw ValidationException::withMessages([
                'token' => 'vous avez déja une colocation active',
            ]);
        }
        $coloc = Colocation::where('invite_token', $token)->where('status','active')->first();
        if (!$coloc) {
            return null;
        }

        $coloc->users()->syncWithoutDetaching([
            $user->id => ['role' => 'member', 'left_at'=>null],
        ]);
        return $coloc;
    }
    public function leave(user $user, Colocation $coloc):void
    {
        $pivot = DB::table('colocation_user')
        ->where('user_id',$user->id)
        ->where('colocation_id',$coloc->id)
        ->whereNull('left_at')
        ->first();

        if(!$pivot){
            throw ValidationException::withMessages([
                'colocation' => "Vous n'estes pas membre actif de cette colocation.",
            ]);
        }

        DB::table('colocation_user')
        ->where('user_id',$user->id)
        ->where('colocation_id', $coloc->id)
        ->update(['left_at'=> now()]);
    }
    public function cancel(User $user, Colocation $coloc): void
    {
        if((int)$coloc->owner_id !== (int)$user->id){
            throw ValidationException::withMessages([
                'colocation' => 'Seul le owner peut annuller la colocation.',
            ]);
        }
        $coloc->update(['status' => 'cancelled']);
    }
}
