<?php

namespace App\Models\System\Organizations;

use App\Helpers\System\{Utilities};
use Illuminate\Database\Eloquent\{Model};
use Illuminate\Support\Facades\{DB};
use stdClass;

class UserPreference extends Model {
    protected $table = "user_preferences";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "user_id",
        "slug",
        "value",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    // Appends
    public function getFormattedStatusAttribute() {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    // Functions
    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    public static function updateItems($userId, $slug = "", $data = null, $extras = []) {

        return DB::transaction(function() use ($userId, $slug, $data, $extras) {

            $activePreferences = UserPreference::where("user_id", $userId)
                ->where("slug", $slug)
                ->where("status", "active")
                ->orderByDesc("id")
                ->lockForUpdate()
                ->get();

            $userPreference = $activePreferences->first();

            if(!Utilities::isDefined($userPreference)) {

                $userPreference = new UserPreference();
                $userPreference->user_id = $userId;
                $userPreference->slug = $slug;
                $userPreference->value = null;
                $userPreference->status = "active";
                $userPreference->created_at = now();
                $userPreference->created_by = $userId;

            }elseif($activePreferences->count() > 1) {

                UserPreference::whereIn("id", $activePreferences->skip(1)->pluck("id"))
                    ->update([
                        "status" => "inactive",
                        "updated_at" => now(),
                        "updated_by" => $userId,
                    ]);

            }

            if(in_array($slug, ["config_companies_sub_sections"])) {

                $value = Utilities::isDefined($userPreference->value) ? (json_decode($userPreference->value) ?: new stdClass()) : new stdClass();

                $subSectionsValue = collect($value->sub_sections ?? [])
                    ->filter(fn($item) => intval($item->sub_section_id ?? 0) > 0)
                    ->mapWithKeys(function($item) {

                        $subSectionId = intval($item->sub_section_id);

                        return [$subSectionId => (object) [
                            "sub_section_id" => $subSectionId,
                            "visible_in_menu" => (bool) ($item->visible_in_menu ?? true),
                            "is_favorite" => (bool) ($item->is_favorite ?? false),
                        ]];

                    });

                foreach($data["records"] ?? [] as $record) {

                    $subSectionId = intval($record["sub_section_id"] ?? 0);
                    $actionType = $extras["type"] ?? "store_update";

                    if($subSectionId <= 0 || !in_array($actionType, ["store_update"])) {

                        continue;

                    }

                    $preferenceValue = $subSectionsValue->get($subSectionId, (object) [
                        "sub_section_id" => $subSectionId,
                        "visible_in_menu" => true,
                        "is_favorite" => false,
                    ]);

                    foreach(["visible_in_menu", "is_favorite"] as $field) {

                        if(array_key_exists($field, $record) && is_bool($record[$field])) {

                            $preferenceValue->{$field} = $record[$field];

                        }

                    }

                    $subSectionsValue->put($subSectionId, $preferenceValue);

                }

                $userPreference->value = json_encode([
                    "show_actions" => (bool) ($data["show_actions"] ?? false),
                    "show_only_favorites" => (bool) ($data["show_only_favorites"] ?? false),
                    "show_descriptions" => (bool) ($data["show_descriptions"] ?? true),
                    "sub_sections" => $subSectionsValue->values(),
                ]);

            }

            $userPreference->status = "active";

            $userPreference->updated_at = now();
            $userPreference->updated_by = $userId;
            $userPreference->save();

            return ["bool" => true];

        });

    }

    // Relationships
    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }
}
