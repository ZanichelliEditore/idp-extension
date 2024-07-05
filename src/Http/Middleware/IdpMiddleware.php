<?php

namespace Zanichelli\IdpExtension\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Zanichelli\IdpExtension\Models\ZTrait\ZUserBuilder;

class IdpMiddleware extends IdpUserMiddleware
{
    use ZUserBuilder;

    public function handle(Request $request, Closure $next)
    {
        parent::handle($request, $next);

        $user = Auth::user();

        $user->permissions = $this->retrievePermissions($user->id, $user->roles);

        return $next($request);
    }

    /**
     * Returns the array with permissions
     *
     * @param $userId
     * @param array $roles
     * @return array
     */
    protected function retrievePermissions($userId, array $roles)
    {
        $builder = DB::table('grants');

        foreach ($roles as $role) {
            $builder->orWhere(function ($query) use ($role) {
                $query
                    ->where('role_name', $role->roleName)
                    ->where(function ($query) use ($role) {
                        $query->where('department_name', $role->departmentName)->orWhere('department_name', null);
                    });
            });
        }

        return $builder->pluck('grant');
    }
}
