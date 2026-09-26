<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\General;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\General\{SaveMasterDataRequest};
use App\Services\System\General\{MasterDataService};
use Illuminate\Http\{JsonResponse};

final class MasterDataController extends BaseController {
    public function index() {

        return view("System/general/General/master_data/main");

    }

    public function list(string $resource): JsonResponse {

        try {

            return response()->json([
                "bool" => true,
                "data" => MasterDataService::list($resource),
            ]);

        }catch(\Throwable $e) {

            return $this->handleException($e, "retrieve");

        }

    }

    public function store(SaveMasterDataRequest $request, string $resource): JsonResponse {

        return $this->save($request, $resource);

    }

    public function update(SaveMasterDataRequest $request, string $resource, int $id): JsonResponse {

        return $this->save($request, $resource, $id);

    }

    protected function getTranslationNamespace(): string {

        return "System.General.master_data";

    }

    private function save(SaveMasterDataRequest $request, string $resource, ?int $id = null): JsonResponse {

        try {

            $record = MasterDataService::save(
                $this->getUserId(),
                $resource,
                $request->validated(),
                $id
            );

            return $id
                ? $this->updatedResponse($record, "updated", "masterData")
                : $this->createdResponse($record, "created", "masterData");

        }catch(\Throwable $e) {

            return $this->handleException($e, $id ? "update" : "create");

        }

    }
}
