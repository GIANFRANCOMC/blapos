<?php

namespace App\Models\System\Assets;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Branch};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model};

class AssetAssignmentLog extends Model {
    protected $table = "asset_assignment_logs";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_action_type",
    ];

    protected $fillable = [
        "action_by",
        "user_id",
        "branch_id",
        "asset_id",
        "from_user_id",
        "to_user_id",
        "from_branch_id",
        "to_branch_id",
        "action_type",
        "quantity",
        "note",
        "action_at",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    // Appends
    public function getFormattedActionTypeAttribute() {

        return self::getActionTypes("first", $this->attributes["action_type"] ?? "")["label"] ?? "";

    }

    // Functions
    public static function getActionTypes($type = "all", $code = "") {

        $actionTypes = [
            ["code" => "assigned", "label" => "Asignado"],
            ["code" => "transferred", "label" => "Transferencia"],
            ["code" => "returned", "label" => "Devuelto"],
            ["code" => "retired", "label" => "Retirado"],
        ];

        return Utilities::getValues($actionTypes, $type, $code);

    }

    // Relationships
    public function actionUser() {

        return $this->belongsTo(User::class, "action_by", "id");

    }

    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function asset() {

        return $this->belongsTo(Asset::class, "asset_id", "id");

    }

    public function fromUser() {

        return $this->belongsTo(User::class, "from_user_id", "id");

    }

    public function toUser() {

        return $this->belongsTo(User::class, "to_user_id", "id");

    }

    public function fromBranch() {

        return $this->belongsTo(Branch::class, "from_branch_id", "id");

    }

    public function toBranch() {

        return $this->belongsTo(Branch::class, "to_branch_id", "id");

    }
}
