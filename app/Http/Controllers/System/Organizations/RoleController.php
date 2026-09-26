<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Organizations\Roles\{DuplicateRoleRequest, StoreRoleRequest};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Organizations\Roles\{RoleConfigService, RolePermissionService, RoleService};
use Illuminate\Http\{JsonResponse, Request};

class RoleController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Organizations.role";

    public function index() {

        return view("System/general/Organizations/roles/main");

    }

    public function initParams(Request $request) {

        return RoleConfigService::getInitParams(
            $this->getPage($request),
            $this->getUserId()
        );

    }

    public function list(Request $request) {

        return RoleService::query(
            (string) $request->input("word", "")
        )->paginate($this->getPerPage($request, Utilities::$per_page_default));

    }

    public function show(int $id): JsonResponse {

        return response()->json(RoleService::find($id));

    }

    public function store(StoreRoleRequest $request): JsonResponse {

        $role = RoleService::create(
            $this->getUserId(),
            $request->validated()
        );

        $this->invalidate();

        return response()->json([
            "bool" => true,
            "msg" => "Perfil agregado correctamente.",
            "data" => $role,
        ], 201);

    }

    public function update(StoreRoleRequest $request, int $id): JsonResponse {

        $role = RoleService::update(
            $id,
            $this->getUserId(),
            $request->validated()
        );

        $this->invalidate();

        return response()->json([
            "bool" => true,
            "msg" => "Perfil actualizado correctamente.",
            "data" => $role,
        ]);

    }

    public function duplicate(DuplicateRoleRequest $request, int $id): JsonResponse {

        $data = $request->validated();
        $role = RoleService::duplicate(
            $id,
            $this->getUserId(),
            $data["name"]
        );

        $this->invalidate();

        return response()->json([
            "bool" => true,
            "msg" => "Perfil duplicado correctamente.",
            "data" => $role,
        ], 201);

    }

    private function invalidate(): void {

        RolePermissionService::clearCompanyCache($this->getCompanyId());
        \App\Services\System\Organizations\Companies\CompanySectionService::clearCompanyCache($this->getCompanyId());
        RoleConfigService::clearAllCache();
        InitParamsCacheInvalidationService::invalidate(
            InitParamsCacheInvalidationService::ROLES
        );

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
