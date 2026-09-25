<?php

declare(strict_types=1);

namespace App\Services\System\Operations;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Customer};
use App\Models\System\Operations\{ServiceFloor, ServiceSession, ServiceSessionItem, ServiceStation};
use App\Models\System\Organizations\{Branch, User};
use App\Services\System\Base\{CompanyReferenceDataService};
use App\Services\System\Organizations\{AccessScopeService};
use Carbon\{Carbon};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Collection};

final class ServiceOperationService {
    public const STATUS_PENDING = "pending";

    public const STATUS_IN_PROGRESS = "in_progress";

    public const STATUS_COMPLETED = "completed";

    public const STATUS_CANCELED = "canceled";

    public static function stationTypes(): array {

        return [
            ["code" => "table", "label" => "Mesa"],
            ["code" => "chair", "label" => "Sillón"],
            ["code" => "booth", "label" => "Cabina"],
            ["code" => "room", "label" => "Habitación"],
            ["code" => "court", "label" => "Cancha"],
            ["code" => "bay", "label" => "Bahía de atención"],
            ["code" => "other", "label" => "Otro"],
        ];

    }

    public static function sessionTypes(): array {

        return [
            ["code" => "restaurant", "label" => "Atención en mesa"],
            ["code" => "catalog_service", "label" => "Servicio del catálogo"],
            ["code" => "appointment", "label" => "Cita"],
            ["code" => "rental", "label" => "Alquiler"],
            ["code" => "other", "label" => "Otra operación"],
        ];

    }

    public static function sessionStatuses(): array {

        return [
            ["code" => self::STATUS_PENDING, "label" => "Pendiente"],
            ["code" => self::STATUS_IN_PROGRESS, "label" => "En curso"],
            ["code" => self::STATUS_COMPLETED, "label" => "Finalizada"],
            ["code" => self::STATUS_CANCELED, "label" => "Cancelada"],
        ];

    }

    public static function stationColors(): array {

        return ["#2899e5", "#1a1a35", "#10b981", "#d97706", "#dc2626", "#7c3aed"];

    }

    public static function stationShapes(): array {

        return [
            ["code" => "round", "label" => "Redonda"],
            ["code" => "square", "label" => "Cuadrada"],
            ["code" => "rectangle", "label" => "Rectangular"],
        ];

    }

    private static function calculateDetailCommission(ServiceSessionItem $detail): float {

        $item = $detail->item;
        $quantity = (float) $detail->quantity;
        $unitPrice = (float) $detail->unit_price;
        $type = $item?->commission_type ?? "none";
        $value = (float) ($item?->commission_value ?? 0);
        $legacyRate = (float) ($item?->commission_rate ?? 0);

        if($type === "none" && $legacyRate > 0) {

            $type = "percentage";
            $value = $legacyRate;

        }

        if($type === "percentage") {

            return Utilities::round(($quantity * $unitPrice) * (min($value, 100) / 100));

        }

        if($type === "fixed") {

            return Utilities::round($quantity * max($value, 0));

        }

        return 0.0;

    }

    public static function createFloor(int $companyId, int $actorId, array $data): ServiceFloor {

        self::requireBranch($companyId, (int) $data["branch_id"], $actorId);

        $duplicate = ServiceFloor::query()
            ->where("branch_id", (int) $data["branch_id"])
            ->where("code", trim((string) $data["code"]))
            ->exists();

        if($duplicate) {

            throw new DomainException("Ya existe un piso con ese código en la sucursal.");

        }

        return ServiceFloor::create([
            "branch_id" => (int) $data["branch_id"],
            "code" => trim((string) $data["code"]),
            "name" => trim((string) $data["name"]),
            "level_number" => (int) ($data["level_number"] ?? 1),
            "sort_order" => (int) ($data["sort_order"] ?? 1),
            "background_color" => (string) ($data["background_color"] ?? "#f7f8fa"),
            "description" => $data["description"] ?? null,
            "status" => (string) ($data["status"] ?? "active"),
            "created_at" => now(),
            "created_by" => $actorId,
        ]);

    }

    public static function floors(int $companyId, int $actorId, int $branchId) {

        self::requireBranch($companyId, $branchId, $actorId);

        return ServiceFloor::query()
            ->where("branch_id", $branchId)
            ->where("status", "active")
            ->withCount(["stations" => fn($query) => $query->where("status", "active")])
            ->orderBy("sort_order")
            ->orderBy("level_number")
            ->orderBy("name")
            ->get();

    }

    public static function updateFloor(int $companyId, int $actorId, int $floorId, array $data): ServiceFloor {

        return DB::transaction(function() use ($companyId, $actorId, $floorId, $data) {

            $branchId = (int) $data["branch_id"];
            self::requireBranch($companyId, $branchId, $actorId);

            $floor = ServiceFloor::query()
                ->lockForUpdate()
                ->find($floorId);

            if(!$floor) {

                throw new DomainException("El piso no está disponible.");

            }

            $duplicate = ServiceFloor::query()
                ->where("branch_id", $branchId)
                ->where("code", trim((string) $data["code"]))
                ->where("id", "!=", $floorId)
                ->exists();

            if($duplicate) {

                throw new DomainException("Ya existe un piso con ese código en la sucursal.");

            }

            $floor->fill([
                "branch_id" => $branchId,
                "code" => trim((string) $data["code"]),
                "name" => trim((string) $data["name"]),
                "level_number" => (int) ($data["level_number"] ?? $floor->level_number),
                "sort_order" => (int) ($data["sort_order"] ?? $floor->sort_order),
                "background_color" => (string) ($data["background_color"] ?? $floor->background_color),
                "description" => $data["description"] ?? $floor->description,
                "status" => (string) ($data["status"] ?? $floor->status),
                "updated_at" => now(),
                "updated_by" => $actorId,
            ]);

            $floor->save();

            return $floor->fresh();

        });

    }

    public static function createStation(int $companyId, int $actorId, array $data): ServiceStation {

        self::requireBranch($companyId, (int) $data["branch_id"], $actorId);
        $floor = self::requireOptionalFloor(
            $companyId,
            (int) $data["branch_id"],
            $data["service_floor_id"] ?? null
        );

        $duplicate = ServiceStation::query()
            ->where("branch_id", (int) $data["branch_id"])
            ->where("code", trim((string) $data["code"]))
            ->exists();

        if($duplicate) {

            throw new DomainException("Ya existe una mesa o estación con ese código en la sucursal.");

        }

        $position = self::nextStationPosition(
            $companyId,
            (int) $data["branch_id"],
            $floor?->id
        );

        return ServiceStation::create([
            "branch_id" => (int) $data["branch_id"],
            "service_floor_id" => $floor?->id,
            "code" => trim((string) $data["code"]),
            "name" => trim((string) $data["name"]),
            "station_type" => (string) ($data["station_type"] ?? "table"),
            "capacity" => (int) ($data["capacity"] ?? 1),
            "position_x" => (float) ($data["position_x"] ?? $position["x"]),
            "position_y" => (float) ($data["position_y"] ?? $position["y"]),
            "color" => (string) ($data["color"] ?? "#2899e5"),
            "shape" => (string) ($data["shape"] ?? "round"),
            "description" => $data["description"] ?? null,
            "status" => (string) ($data["status"] ?? "active"),
            "created_at" => now(),
            "created_by" => $actorId,
        ]);

    }

    public static function stations(int $companyId, int $actorId, int $branchId, ?int $floorId = null) {

        self::requireBranch($companyId, $branchId, $actorId);

        return self::stationQuery($companyId, $branchId, $floorId)->get();

    }

    public static function board(
        int $companyId,
        int $actorId,
        int $branchId,
        ?int $floorId = null
    ): array {

        self::requireBranch($companyId, $branchId, $actorId);

        $floors = ServiceFloor::query()
            ->where("branch_id", $branchId)
            ->where("status", "active")
            ->withCount(["stations" => fn($query) => $query->where("status", "active")])
            ->orderBy("sort_order")
            ->orderBy("level_number")
            ->orderBy("name")
            ->get();

        $selectedFloor = $floorId
            ? $floors->firstWhere("id", $floorId)
            : $floors->first();

        return [
            "floors" => $floors,
            "selected_floor_id" => $selectedFloor?->id,
            "stations" => $selectedFloor
                ? self::stationQuery($companyId, $branchId, (int) $selectedFloor->id)->get()
                : collect(),
        ];

    }

    public static function options(
        int $companyId,
        string $resource,
        string $search = "",
        ?string $itemType = null,
        int $limit = 30
    ): Collection {

        $limit = min(50, max(1, $limit));

        if($resource === "customers") {

            return DB::table("customers")
                ->where("status", "active")
                ->when($search !== "", function($query) use ($search) {

                    $query->where(function($query) use ($search) {

                        $query->where("name", "like", "%{$search}%")
                            ->orWhere("document_number", "like", "%{$search}%");

                    });

                })
                ->orderBy("name")
                ->limit($limit)
                ->get(["id", "name", "document_number"]);

        }

        if($resource !== "items") {

            throw new DomainException("El recurso de búsqueda no está disponible.");

        }

        return DB::table("items")
            ->where("status", "active")
            ->where(function($query) {

                $query->whereNull("expires_at")
                    ->orWhere("expires_at", ">", now());

            })
            ->where(function($query) {

                $query->where("capacity_control_enabled", false)
                    ->orWhereColumn("capacity_used", "<", "capacity_limit");

            })
            ->when($itemType, fn($query) => $query->where("type", $itemType))
            ->when($search !== "", function($query) use ($search) {

                $query->where(function($query) use ($search) {

                    $query->where("name", "like", "%{$search}%")
                        ->orWhere("internal_code", "like", "%{$search}%")
                        ->orWhere("barcode", "like", "%{$search}%");

                });

            })
            ->orderBy("name")
            ->limit($limit)
            ->get(["id", "name", "type", "price"]);

    }

    private static function stationQuery(int $companyId, int $branchId, ?int $floorId = null): Builder {

        return ServiceStation::query()
            ->where("branch_id", $branchId)
            ->where("status", "active")
            ->when($floorId, fn($query) => $query->where("service_floor_id", $floorId))
            ->with([
                "floor",
                "activeSession.customer",
                "activeSession.assignedUser",
                "activeSession.items.assignedUser",
            ])
            ->orderBy("name");

    }

    public static function updateStation(int $companyId, int $actorId, int $stationId, array $data): ServiceStation {

        return DB::transaction(function() use ($companyId, $actorId, $stationId, $data) {

            $branchId = (int) $data["branch_id"];
            self::requireBranch($companyId, $branchId, $actorId);

            $station = ServiceStation::query()
                ->lockForUpdate()
                ->find($stationId);

            if(!$station) {

                throw new DomainException("La mesa o estación no está disponible.");

            }

            $floor = self::requireOptionalFloor($companyId, $branchId, $data["service_floor_id"] ?? null);

            $duplicate = ServiceStation::query()
                ->where("branch_id", $branchId)
                ->where("code", trim((string) $data["code"]))
                ->where("id", "!=", $stationId)
                ->exists();

            if($duplicate) {

                throw new DomainException("Ya existe una mesa o estación con ese código en la sucursal.");

            }

            $station->fill([
                "branch_id" => $branchId,
                "service_floor_id" => $floor?->id,
                "code" => trim((string) $data["code"]),
                "name" => trim((string) $data["name"]),
                "station_type" => (string) ($data["station_type"] ?? $station->station_type),
                "capacity" => (int) ($data["capacity"] ?? $station->capacity),
                "position_x" => self::percentage($data["position_x"] ?? $station->position_x),
                "position_y" => self::percentage($data["position_y"] ?? $station->position_y),
                "color" => (string) ($data["color"] ?? $station->color),
                "shape" => (string) ($data["shape"] ?? $station->shape),
                "description" => $data["description"] ?? $station->description,
                "status" => (string) ($data["status"] ?? $station->status),
                "updated_at" => now(),
                "updated_by" => $actorId,
            ]);

            $station->save();

            return $station->fresh(["floor", "activeSession.customer", "activeSession.items"]);

        });

    }

    public static function updateStationLayout(
        int $companyId,
        int $actorId,
        int $stationId,
        array $data
    ): ServiceStation {

        return DB::transaction(function() use ($companyId, $actorId, $stationId, $data) {

            $station = ServiceStation::query()
                ->lockForUpdate()
                ->find($stationId);

            if(!$station) {

                throw new DomainException("La mesa o estación no está disponible.");

            }

            self::requireBranch($companyId, (int) $station->branch_id, $actorId);

            $floor = self::requireOptionalFloor(
                $companyId,
                (int) $station->branch_id,
                $data["service_floor_id"] ?? $station->service_floor_id
            );

            $station->service_floor_id = $floor?->id;
            $station->position_x = self::percentage($data["position_x"] ?? $station->position_x);
            $station->position_y = self::percentage($data["position_y"] ?? $station->position_y);
            $station->color = (string) ($data["color"] ?? $station->color);
            $station->shape = (string) ($data["shape"] ?? $station->shape);
            $station->updated_at = now();
            $station->updated_by = $actorId;
            $station->save();

            return $station->fresh(["floor", "activeSession.customer", "activeSession.items"]);

        });

    }

    public static function sessions(
        int $companyId,
        int $actorId,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        $query = ServiceSession::query()
            ->with(["branch", "station", "customer", "assignedUser", "items.assignedUser", "sale"]);

        $branchIds = CompanyReferenceDataService::for($companyId, $actorId)->allowedBranchIds();

        if($branchIds !== null) {

            $query->whereIn("branch_id", $branchIds);

        }

        foreach(["branch_id", "service_station_id", "assigned_user_id", "session_type"] as $field) {

            if(!empty($filters[$field])) {

                $query->where($field, $filters[$field]);

            }

        }

        if(($filters["status"] ?? null) === "open") {

            $query->whereIn("status", [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);

        }elseif(!empty($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

        if(!empty($filters["date_from"])) {

            $query->where("created_at", ">=", Utilities::startOfDay($filters["date_from"]));

        }

        if(!empty($filters["date_to"])) {

            $query->where("created_at", "<=", Utilities::endOfDay($filters["date_to"]));

        }

        return $query->orderByDesc("id")->paginate($perPage);

    }

    public static function find(int $companyId, int $sessionId, ?int $actorId = null): ServiceSession {

        $session = ServiceSession::query()
            ->with([
                "branch",
                "station",
                "customer",
                "assignedUser",
                "items.item",
                "items.assignedUser",
                "sale",
                "events.user",
            ])
            ->find($sessionId);

        if(!$session) {

            throw new DomainException("La sesión de servicio no está disponible.");

        }

        if($actorId) {

            self::requireBranch($companyId, (int) $session->branch_id, $actorId);

        }

        return $session;

    }

    public static function open(int $companyId, int $actorId, array $data): ServiceSession {

        return DB::transaction(function() use ($companyId, $actorId, $data) {

            $branchId = (int) $data["branch_id"];
            $stationId = !empty($data["service_station_id"])
                ? (int) $data["service_station_id"]
                : null;

            self::requireBranch($companyId, $branchId, $actorId);
            self::requireOptionalUser($companyId, $data["assigned_user_id"] ?? null);
            self::requireOptionalCustomer($companyId, $data["customer_id"] ?? null);

            if($stationId) {

                $station = ServiceStation::query()
                    ->where("branch_id", $branchId)
                    ->where("status", "active")
                    ->lockForUpdate()
                    ->find($stationId);

                if(!$station) {

                    throw new DomainException("La estación no está activa o no pertenece a la sucursal.");

                }

                $occupied = ServiceSession::query()
                    ->where("service_station_id", $stationId)
                    ->whereIn("status", [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
                    ->exists();

                if($occupied) {

                    throw new DomainException("La estación ya tiene una atención en curso.");

                }

            }

            $startedAt = !empty($data["start_immediately"])
                ? Carbon::parse($data["started_at"] ?? now())
                : null;

            $session = ServiceSession::create([
                "branch_id" => $branchId,
                "service_station_id" => $stationId,
                "customer_id" => $data["customer_id"] ?? null,
                "assigned_user_id" => $data["assigned_user_id"] ?? null,
                "opened_by" => $actorId,
                "reference" => self::nextReference(),
                "session_type" => (string) ($data["session_type"] ?? "catalog_service"),
                "status" => $startedAt ? self::STATUS_IN_PROGRESS : self::STATUS_PENDING,
                "started_at" => $startedAt,
                "scheduled_at" => $data["scheduled_at"] ?? null,
                "expected_end_at" => $data["expected_end_at"] ?? null,
                "tolerance_minutes" => (int) ($data["tolerance_minutes"] ?? 0),
                "queue_code" => $data["queue_code"] ?? null,
                "observation" => $data["observation"] ?? null,
                "created_at" => now(),
                "created_by" => $actorId,
            ]);

            self::recordEvent($session, $actorId, "opened", null, $session->status);

            if(!empty($data["item_id"])) {

                self::addItem($companyId, $actorId, $session->id, [
                    "item_id" => (int) $data["item_id"],
                    "assigned_user_id" => $data["assigned_user_id"] ?? null,
                    "quantity" => $data["quantity"] ?? 1,
                    "start_immediately" => (bool) $startedAt,
                ]);

            }

            return self::find($companyId, (int) $session->id, $actorId);

        });

    }

    public static function addItem(int $companyId, int $actorId, int $sessionId, array $data): ServiceSessionItem {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId, $data) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);
            $item = Item::query()
                ->where("status", "active")
                ->find((int) $data["item_id"]);

            if(!$item) {

                throw new DomainException("El producto o servicio no está disponible.");

            }

            self::requireOptionalUser($companyId, $data["assigned_user_id"] ?? null);

            $startedAt = !empty($data["start_immediately"]) ? now() : null;

            $detail = ServiceSessionItem::create([
                "service_session_id" => $session->id,
                "item_id" => $item->id,
                "assigned_user_id" => $data["assigned_user_id"] ?? $session->assigned_user_id,
                "name" => $item->name,
                "item_type" => $item->type,
                "quantity" => (float) ($data["quantity"] ?? 1),
                "unit_price" => (float) $item->price,
                "status" => $startedAt ? self::STATUS_IN_PROGRESS : self::STATUS_PENDING,
                "started_at" => $startedAt,
                "observation" => $data["observation"] ?? null,
                "created_at" => now(),
                "created_by" => $actorId,
            ]);

            self::recordEvent(
                $session,
                $actorId,
                "item_added",
                null,
                $detail->status,
                null,
                ["service_session_item_id" => $detail->id, "item_name" => $detail->name]
            );

            return $detail;

        });

    }

    public static function start(int $companyId, int $actorId, int $sessionId): ServiceSession {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);

            if($session->status === self::STATUS_IN_PROGRESS) {

                return self::find($companyId, $sessionId, $actorId);

            }

            $session->status = self::STATUS_IN_PROGRESS;

            $session->started_at = now();
            $session->updated_at = now();
            $session->updated_by = $actorId;
            $session->save();
            self::recordEvent($session, $actorId, "started", self::STATUS_PENDING, self::STATUS_IN_PROGRESS);

            return self::find($companyId, $sessionId, $actorId);

        });

    }

    public static function startItem(int $companyId, int $actorId, int $itemId): ServiceSessionItem {

        return self::changeItemTiming($companyId, $actorId, $itemId, false);

    }

    public static function completeItem(int $companyId, int $actorId, int $itemId): ServiceSessionItem {

        return self::changeItemTiming($companyId, $actorId, $itemId, true);

    }

    public static function updatePreparationStatus(
        int $companyId,
        int $actorId,
        int $itemId,
        string $status
    ): ServiceSessionItem {

        return DB::transaction(function() use ($companyId, $actorId, $itemId, $status) {

            $item = ServiceSessionItem::query()
                ->with("session")
                ->lockForUpdate()
                ->findOrFail($itemId);

            $session = $item->session;

            self::requireBranch($companyId, (int) $session->branch_id, $actorId);

            if(in_array($session->status, [self::STATUS_COMPLETED, self::STATUS_CANCELED], true)) {

                throw new DomainException("La atención ya no admite cambios de preparación.");

            }

            $previousStatus = (string) ($item->preparation_status ?: "pending");

            if($previousStatus === $status) {

                return $item->fresh();

            }

            $allowedTransitions = [
                "pending" => "preparing",
                "preparing" => "ready",
                "ready" => "delivered",
            ];

            if(($allowedTransitions[$previousStatus] ?? null) !== $status) {

                throw new DomainException("El cambio de estado de preparación no es válido.");

            }

            $item->preparation_status = $status;

            $item->preparation_started_at = $status === "preparing"
                ? now()
                : $item->preparation_started_at;

            $item->ready_at = $status === "ready" ? now() : $item->ready_at;
            $item->delivered_at = $status === "delivered" ? now() : $item->delivered_at;
            $item->updated_by = $actorId;
            $item->updated_at = now();
            $item->save();

            self::recordEvent(
                $session,
                $actorId,
                "preparation_status_changed",
                $previousStatus,
                $status,
                null,
                ["service_session_item_id" => $item->id]
            );

            return $item->fresh();

        });

    }

    public static function complete(
        int $companyId,
        int $actorId,
        int $sessionId,
        ?int $saleHeaderId = null
    ): ServiceSession {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId, $saleHeaderId) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);

            if(DB::table("service_session_pauses")
                ->where("service_session_id", $sessionId)
                ->where("status", "active")
                ->exists()) {

                throw new DomainException("Reanuda la atención antes de finalizarla.");

            }
            $previousStatus = $session->status;

            $endedAt = now();
            $startedAt = $session->started_at ? Carbon::parse($session->started_at) : Carbon::parse($session->created_at);

            ServiceSessionItem::query()
                ->where("service_session_id", $session->id)
                ->whereIn("status", [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
                ->get()
                ->each(function(ServiceSessionItem $item) use ($endedAt, $actorId) {

                    $itemStart = $item->started_at ? Carbon::parse($item->started_at) : Carbon::parse($item->created_at);
                    $item->status = self::STATUS_COMPLETED;
                    $item->started_at = $item->started_at ?? $itemStart;
                    $item->ended_at = $endedAt;
                    $item->duration_minutes = $itemStart->diffInMinutes($endedAt);
                    $item->updated_at = now();
                    $item->updated_by = $actorId;
                    $item->save();

                });

            $session->sale_header_id = $saleHeaderId ?? $session->sale_header_id;
            $session->status = self::STATUS_COMPLETED;
            $session->started_at = $session->started_at ?? $startedAt;
            $session->ended_at = $endedAt;
            $session->duration_minutes = $startedAt->diffInMinutes($endedAt);
            $session->closed_by = $actorId;
            $session->updated_at = now();
            $session->updated_by = $actorId;
            $session->save();
            self::recordEvent($session, $actorId, "completed", $previousStatus, self::STATUS_COMPLETED);

            return self::find($companyId, $sessionId, $actorId);

        });

    }

    public static function attachSale(int $companyId, int $actorId, int $sessionId, int $saleHeaderId): ServiceSession {

        return self::complete($companyId, $actorId, $sessionId, $saleHeaderId);

    }

    public static function reassign(int $companyId, int $actorId, int $sessionId, int $assignedUserId, ?string $note = null): ServiceSession {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId, $assignedUserId, $note) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);
            self::requireOptionalUser($companyId, $assignedUserId);
            $previousUserId = $session->assigned_user_id;
            $session->assigned_user_id = $assignedUserId;
            $session->updated_by = $actorId;
            $session->updated_at = now();
            $session->save();

            self::recordEvent($session, $actorId, "reassigned", null, null, $note, [
                "previous_user_id" => $previousUserId,
                "assigned_user_id" => $assignedUserId,
            ]);

            return self::find($companyId, $sessionId, $actorId);

        });

    }

    public static function pause(int $companyId, int $actorId, int $sessionId, ?int $itemId, ?string $reason): array {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId, $itemId, $reason) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);

            if(DB::table("service_session_pauses")
                ->where("service_session_id", $sessionId)
                ->where("status", "active")
                ->exists()) {

                throw new DomainException("La atención ya tiene una pausa en curso.");

            }

            if($itemId && !ServiceSessionItem::query()
                ->where("service_session_id", $sessionId)
                ->where("id", $itemId)
                ->exists()) {

                throw new DomainException("El detalle seleccionado no pertenece a la atención.");

            }

            $id = DB::table("service_session_pauses")->insertGetId([
                "service_session_id" => $sessionId,
                "service_session_item_id" => $itemId,
                "paused_by" => $actorId,
                "paused_at" => now(),
                "duration_minutes" => 0,
                "reason" => $reason,
                "status" => "active",
                "created_at" => now(),
            ]);

            self::recordEvent($session, $actorId, "paused", null, null, $reason, ["item_id" => $itemId]);

            return (array) DB::table("service_session_pauses")
                ->where("service_session_id", $sessionId)
                ->where("id", $id)
                ->first();

        });

    }

    public static function resume(int $companyId, int $actorId, int $sessionId): array {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);
            $pause = DB::table("service_session_pauses")
                ->where("service_session_id", $sessionId)
                ->where("status", "active")
                ->lockForUpdate()
                ->first();

            if(!$pause) {

                throw new DomainException("La atención no tiene una pausa en curso.");

            }

            $resumedAt = now();

            $minutes = Carbon::parse($pause->paused_at)->diffInMinutes($resumedAt);
            DB::table("service_session_pauses")
                ->where("service_session_id", $sessionId)
                ->where("id", $pause->id)
                ->update([
                    "resumed_by" => $actorId,
                    "resumed_at" => $resumedAt,
                    "duration_minutes" => $minutes,
                    "status" => "finalized",
                    "updated_at" => now(),
                ]);

            if($pause->service_session_item_id) {

                ServiceSessionItem::query()
                    ->where("service_session_id", $sessionId)
                    ->where("id", $pause->service_session_item_id)
                    ->increment("paused_minutes", $minutes);

            }

            self::recordEvent($session, $actorId, "resumed", null, null, null, ["pause_id" => $pause->id]);

            return (array) DB::table("service_session_pauses")
                ->where("service_session_id", $sessionId)
                ->where("id", $pause->id)
                ->first();

        });

    }

    public static function cancel(int $companyId, int $actorId, int $sessionId, string $reason): ServiceSession {

        return DB::transaction(function() use ($companyId, $actorId, $sessionId, $reason) {

            $session = self::lockOpenSession($companyId, $sessionId);
            self::requireBranch($companyId, (int) $session->branch_id, $actorId);
            $previous = $session->status;
            $session->status = self::STATUS_CANCELED;
            $session->cancellation_reason = $reason;
            $session->canceled_at = now();
            $session->canceled_by = $actorId;
            $session->updated_at = now();
            $session->updated_by = $actorId;
            $session->save();

            self::recordEvent($session, $actorId, "canceled", $previous, self::STATUS_CANCELED, $reason);

            return self::find($companyId, $sessionId, $actorId);

        });

    }

    public static function reports(int $companyId, int $actorId, array $filters = []): array {

        $dateFrom = !empty($filters["date_from"])
            ? Carbon::parse($filters["date_from"])->startOfDay()
            : now()->copy()->startOfMonth();

        $dateTo = !empty($filters["date_to"])
            ? Carbon::parse($filters["date_to"])->endOfDay()
            : now()->copy()->endOfDay();

        $query = ServiceSession::query()
            ->whereBetween("created_at", [$dateFrom, $dateTo])
            ->with(["branch", "station", "assignedUser", "items.item", "items.assignedUser"]);

        $branchIds = CompanyReferenceDataService::for($companyId, $actorId)->allowedBranchIds();

        if($branchIds !== null) {

            $query->whereIn("branch_id", $branchIds);

        }

        foreach(["branch_id", "service_station_id", "assigned_user_id", "session_type"] as $field) {

            if(!empty($filters[$field])) {

                $query->where($field, $filters[$field]);

            }

        }

        $sessions = $query->get();

        $completed = $sessions->where("status", self::STATUS_COMPLETED);
        $withSla = $completed->filter(function(ServiceSession $session) {

            return !empty($session->expected_end_at);

        });

        $late = $withSla->filter(function(ServiceSession $session) {

            $limit = Carbon::parse($session->expected_end_at)
                ->addMinutes((int) ($session->tolerance_minutes ?? 0));

            return $session->ended_at && Carbon::parse($session->ended_at)->greaterThan($limit);

        });

        $commissionTotal = 0.0;

        $sessions->each(function(ServiceSession $session) use (&$commissionTotal) {

            $session->items->each(function(ServiceSessionItem $detail) use (&$commissionTotal) {

                $commissionTotal += self::calculateDetailCommission($detail);

            });

        });

        return [
            "period" => [
                "date_from" => $dateFrom->toDateString(),
                "date_to" => $dateTo->toDateString(),
            ],
            "summary" => [
                "total_sessions" => $sessions->count(),
                "open_sessions" => $sessions->whereIn("status", [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])->count(),
                "completed_sessions" => $completed->count(),
                "canceled_sessions" => $sessions->where("status", self::STATUS_CANCELED)->count(),
                "average_duration_minutes" => Utilities::round((float) $completed->avg("duration_minutes"), null, $companyId),
                "sla_late_sessions" => $late->count(),
                "sla_compliance_rate" => $withSla->count()
                    ? Utilities::round((($withSla->count() - $late->count()) / $withSla->count()) * 100, null, $companyId)
                    : null,
                "commission_total" => Utilities::round($commissionTotal, null, $companyId),
            ],
            "by_branch" => self::reportGroup($sessions, "branch", "Sucursal"),
            "by_station" => self::reportGroup($sessions, "station", "Estación"),
            "by_responsible" => self::reportGroup($sessions, "assignedUser", "Responsable"),
            "by_service" => self::reportItemsGroup($sessions),
        ];

    }

    private static function changeItemTiming(
        int $companyId,
        int $actorId,
        int $itemId,
        bool $complete
    ): ServiceSessionItem {

        return DB::transaction(function() use ($companyId, $actorId, $itemId, $complete) {

            $item = ServiceSessionItem::query()
                ->with("session")
                ->lockForUpdate()
                ->find($itemId);

            if(!$item || in_array($item->status, [self::STATUS_COMPLETED, self::STATUS_CANCELED], true)) {

                throw new DomainException("El detalle de servicio no está disponible para esta acción.");

            }

            if(!$item->session || !in_array($item->session->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true)) {

                throw new DomainException("La atención ya terminó o no está disponible.");

            }

            self::requireBranch($companyId, (int) $item->session->branch_id, $actorId);

            $previousStatus = (string) $item->status;

            if($complete) {

                $endedAt = now();
                $startedAt = $item->started_at ? Carbon::parse($item->started_at) : Carbon::parse($item->created_at);
                $item->status = self::STATUS_COMPLETED;
                $item->started_at = $item->started_at ?? $startedAt;
                $item->ended_at = $endedAt;
                $item->duration_minutes = $startedAt->diffInMinutes($endedAt);

            }else {

                $item->status = self::STATUS_IN_PROGRESS;
                $item->started_at = now();

                if($item->session->status === self::STATUS_PENDING) {

                    $item->session->status = self::STATUS_IN_PROGRESS;
                    $item->session->started_at = now();
                    $item->session->updated_at = now();
                    $item->session->updated_by = $actorId;
                    $item->session->save();

                }

            }

            $item->updated_at = now();

            $item->updated_by = $actorId;
            $item->save();

            self::recordEvent(
                $item->session,
                $actorId,
                $complete ? "item_completed" : "item_started",
                $previousStatus,
                $item->status,
                null,
                ["service_session_item_id" => $item->id, "item_name" => $item->name]
            );

            return $item->fresh(["item", "assignedUser"]);

        });

    }

    private static function lockOpenSession(int $companyId, int $sessionId): ServiceSession {

        $session = ServiceSession::query()
            ->whereIn("status", [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->lockForUpdate()
            ->find($sessionId);

        if(!$session) {

            throw new DomainException("La sesión ya terminó o no está disponible.");

        }

        return $session;

    }

    private static function requireBranch(int $companyId, int $branchId, ?int $actorId = null): Branch {

        $branch = Branch::query()
            ->where("status", "active")
            ->find($branchId);

        if(!$branch) {

            throw new DomainException("La sucursal no está activa o no pertenece a la empresa.");

        }

        if($actorId) {

            $actor = User::query()
                ->where("status", "active")
                ->find($actorId);

            if(!$actor || !AccessScopeService::canAccess($actor, AccessScopeService::BRANCH, $branchId)) {

                throw new DomainException("No tienes acceso operativo a esta sucursal.");

            }

        }

        return $branch;

    }

    private static function requireOptionalUser(int $companyId, mixed $userId): ?User {

        if(empty($userId)) {

            return null;

        }

        $user = User::query()
            ->where("status", "active")
            ->find((int) $userId);

        if(!$user) {

            throw new DomainException("El responsable no está activo o no pertenece a la empresa.");

        }

        return $user;

    }

    private static function requireOptionalCustomer(int $companyId, mixed $customerId): ?Customer {

        if(empty($customerId)) {

            return null;

        }

        $customer = Customer::query()
            ->where("status", "active")
            ->find((int) $customerId);

        if(!$customer) {

            throw new DomainException("El cliente no está activo o no pertenece a la empresa.");

        }

        return $customer;

    }

    private static function requireOptionalFloor(int $companyId, int $branchId, mixed $floorId): ?ServiceFloor {

        if(empty($floorId)) {

            return null;

        }

        $floor = ServiceFloor::query()
            ->where("branch_id", $branchId)
            ->where("status", "active")
            ->find((int) $floorId);

        if(!$floor) {

            throw new DomainException("El piso no está activo o no pertenece a la sucursal.");

        }

        return $floor;

    }

    private static function nextStationPosition(int $companyId, int $branchId, ?int $floorId): array {

        $position = ServiceStation::query()
            ->where("branch_id", $branchId)
            ->where("service_floor_id", $floorId)
            ->count();

        return [
            "x" => 8 + (($position % 6) * 16.8),
            "y" => min(11 + (intdiv($position, 6) * 19.5), 89),
        ];

    }

    private static function percentage(mixed $value): float {

        return min(95, max(5, Utilities::round((float) $value)));

    }

    private static function nextReference(): string {

        return "SRV-".Utilities::generateCode(10);

    }

    private static function reportGroup($sessions, string $relation, string $fallback): array {

        return $sessions
            ->groupBy(fn(ServiceSession $session) => optional($session->{$relation})->id ?? 0)
            ->map(function($records) use ($relation, $fallback) {

                $first = $records->first();
                $related = $first ? $first->{$relation} : null;
                $completed = $records->where("status", self::STATUS_COMPLETED);

                return [
                    "id" => $related?->id,
                    "name" => $related?->name ?? "Sin {$fallback}",
                    "total_sessions" => $records->count(),
                    "completed_sessions" => $completed->count(),
                    "average_duration_minutes" => Utilities::round((float) $completed->avg("duration_minutes")),
                ];

            })
            ->values()
            ->all();

    }

    private static function reportItemsGroup($sessions): array {

        $details = $sessions->flatMap(fn(ServiceSession $session) => $session->items);

        return $details
            ->groupBy("item_id")
            ->map(function($records) {

                $first = $records->first();
                $commission = $records->sum(function(ServiceSessionItem $detail) {

                    return self::calculateDetailCommission($detail);

                });

                return [
                    "id" => $first?->item_id,
                    "name" => $first?->name ?? "Detalle sin nombre",
                    "quantity" => Utilities::round((float) $records->sum("quantity")),
                    "total" => Utilities::round((float) $records->sum(fn(ServiceSessionItem $detail) => (float) $detail->quantity * (float) $detail->unit_price)),
                    "commission_total" => Utilities::round($commission),
                    "average_duration_minutes" => Utilities::round((float) $records->avg("duration_minutes")),
                ];

            })
            ->sortByDesc("quantity")
            ->values()
            ->all();

    }

    private static function recordEvent(
        ServiceSession $session,
        ?int $actorId,
        string $eventType,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?string $note = null,
        array $metadata = []
    ): void {

        DB::table("service_session_events")->insert([
            "service_session_id" => $session->id,
            "service_session_item_id" => $metadata["service_session_item_id"] ?? $metadata["item_id"] ?? null,
            "user_id" => $actorId,
            "event_type" => $eventType,
            "previous_status" => $previousStatus,
            "new_status" => $newStatus,
            "note" => $note,
            "metadata" => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            "occurred_at" => now(),
        ]);

    }
}
