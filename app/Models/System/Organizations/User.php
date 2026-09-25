<?php

namespace App\Models\System\Organizations;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{IdentityDocumentType};
use App\Models\System\Sales\{SaleHeader};
use Illuminate\Foundation\Auth\{User as Authenticatable};
use Illuminate\Notifications\{Notifiable};
use Laravel\Sanctum\{HasApiTokens};

class User extends Authenticatable {
    use HasApiTokens, Notifiable;

    protected $table = "users";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_gender",
        "formatted_status",
        "formatted_preferences",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "role_id",
        "branch_scope_mode",
        "cash_register_scope_mode",
        "warehouse_scope_mode",
        "identity_document_type_id",
        "document_number",
        "name",
        "email",
        "password",
        "session_version",
        "phone_number",
        "gender",
        "birthdate",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        "password",
        "remember_token",
        "session_version",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
        "session_version" => "integer",
    ];

    // Appends
    public function getFormattedGenderAttribute() {

        $gender = $this->attributes["gender"] ?? null;

        return $gender ? (self::getGenders("first", $gender)["label"] ?? "") : "";

    }

    public function getFormattedStatusAttribute() {

        $status = $this->attributes["status"] ?? null;

        return $status ? (self::getStatuses("first", $status)["label"] ?? "") : "";

    }

    public function getFormattedPreferencesAttribute() {

        if($this->relationLoaded("preferences")) {

            $preferences = $this->getRelation("preferences");

        }else {

            $preferences = $this->preferences()->get();

            $this->setRelation("preferences", $preferences);

        }

        return $preferences->mapWithKeys(function($e) {

            return [$e->slug => json_decode($e->value)];

        });

    }

    // Functions
    public static function getGenders($type = "all", $code = "") {

        $statuses = [
            ["code" => "male", "label" => "Masculino"],
            ["code" => "female", "label" => "Femenino"],
            ["code" => "other", "label" => "Otro"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
            ["code" => "blocked", "label" => "Bloqueado"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships

    public function role() {

        return $this->belongsTo(Role::class, "role_id", "id");

    }

    public function branches() {

        return $this->belongsToMany(Branch::class, "user_branches", "user_id", "branch_id")
            ->withPivot(["status", "created_by", "updated_by"])
            ->wherePivot("status", "active");

    }

    public function cashRegisters() {

        return $this->belongsToMany(
            \App\Models\System\Finance\CashRegister::class,
            "user_cash_registers",
            "user_id",
            "cash_register_id"
        )->wherePivot("status", "active");

    }

    public function warehouses() {

        return $this->belongsToMany(
            \App\Models\System\Warehouses\Warehouse::class,
            "user_warehouses",
            "user_id",
            "warehouse_id"
        )->wherePivot("status", "active");

    }

    public function identityDocumentType() {

        return $this->belongsTo(IdentityDocumentType::class, "identity_document_type_id", "id");

    }

    public function preferences() {

        return $this->hasMany(UserPreference::class, "user_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function navigationMetrics() {

        return $this->hasMany(UserNavigationMetric::class, "user_id", "id");

    }

    public function salesHeader() {

        return $this->hasMany(SaleHeader::class, "seller_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function attendances() {

        return $this->hasMany(UserAttendance::class, "user_id", "id");

    }

    public function biometricFingerprints() {

        return $this->hasMany(
            \App\Models\System\Devices\UserBiometricFingerprint::class,
            "user_id",
            "id"
        )->where("status", "active");

    }
}
