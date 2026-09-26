<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Organizations\{Company, CompanySocialMedia};
use App\Services\System\Tenancy\{TenantCompanyContext, TenantStoragePath};
use Exception;
use Illuminate\Http\{UploadedFile};
use Illuminate\Support\Facades\{DB, Storage};

/**
 * Service class for managing module operations
 * Handles business logic for updating records
 */
class CompanyService {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.company";

    /**
     * Allowed fields for record update
     */
    private const ALLOWED_FIELDS = [
        "identity_document_type_id",
        "document_number",
        "legal_name",
        "commercial_name",
        "tagline",
        "description",
        "address",
        "telephone",
        "email",
    ];

    /**
     * Image fields that can be uploaded
     */
    private const IMAGE_FIELDS = [
        "logotype",
        "combinationmark",
        "logomark",
        "login_image",
    ];

    /**
     * Social media types
     */
    private const SOCIAL_MEDIA_TYPES = [
        "facebook",
        "instagram",
        "whatsapp",
    ];

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(self::TRANSLATION_NAMESPACE, $key, $replace);

    }

    /**
     * Handle file upload for images
     *
     * @param  Company  $company Record instance
     * @param  string  $fieldName Field name (logotype, combinationmark, etc.)
     * @param  UploadedFile|null  $file Uploaded file
     * @return string|null File path or null
     */
    private static function handleImageUpload(Company $company, string $fieldName, ?UploadedFile $file): ?string {

        if(!$file || !$file->isValid()) {

            return null;

        }

        $extension = $file->getClientOriginalExtension();

        $fileName = "{$fieldName}.{$extension}";
        $filePath = $file->storeAs(
            TenantStoragePath::branding(),
            $fileName,
            "public"
        );

        return $filePath;

    }

    /**
     * Update or create social media link
     *
     * @param  Company  $company Record instance
     * @param  string  $type Social media type (facebook, instagram, whatsapp)
     * @param  string|null  $link Social media link
     * @param  int|null  $userId User
     */
    private static function updateOrCreateSocialMedia(Company $company, string $type, ?string $link, ?int $userId): CompanySocialMedia {

        $socialMedia = CompanySocialMedia::query()
            ->where("company_id", $company->id)
            ->where("type", $type)
            ->first();

        if(Utilities::isDefined($socialMedia)) {

            $socialMedia->link = $link ?? "";
            $socialMedia->status = "active";
            $socialMedia->updated_at = now();
            $socialMedia->updated_by = $userId;
            $socialMedia->save();

        }else {

            $socialMedia = new CompanySocialMedia();
            $socialMedia->company_id = $company->id;
            $socialMedia->type = $type;
            $socialMedia->link = $link ?? "";
            $socialMedia->status = "active";
            $socialMedia->created_at = now();
            $socialMedia->created_by = $userId;
            $socialMedia->save();

        }

        return $socialMedia;

    }

    /**
     * Prepare data for update (only changed fields)
     *
     * @param  Company  $company Record instance
     * @param  array  $data Input data
     */
    private static function prepareCompanyDataForUpdate(Company $company, array $data): array {

        $updateData = [];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field])) {

                if($data[$field] !== $company->$field) {

                    $updateData[$field] = $data[$field];

                }

            }

        }

        return $updateData;

    }

    /**
     * Find the root company by ID in the current tenant.
     *
     * @param  int  $id Record
     * @param  array|null  $statuses Filter by statuses (e.g. ["active"], ["active", "inactive"])
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, ?array $statuses = ["active"], array $relations = ["identityDocumentType"]): ?Company {

        if($id !== app(TenantCompanyContext::class)->id()) {

            return null;

        }

        $query = Company::where("id", $id);

        if($statuses !== null && !empty($statuses)) {

            $query->whereIn("status", $statuses);

        }

        if($relations !== null && !empty($relations)) {

            $query->with($relations);

        }

        return $query->first();

    }

    /**
     * Update an existing record
     *
     * @param  Company  $company Record instance to update
     * @param  array  $data Input data
     * @param  array  $files Uploaded files array
     * @param  int|null  $userId User updating the record
     * @return Company Updated record instance
     *
     * @throws Exception
     */
    public static function update(Company $company, array $data, array $files, int $userId): Company {

        $obsoleteFiles = [];

        DB::transaction(function() use ($company, $data, $files, $userId, &$obsoleteFiles) {

            // Prepare update data with only changed fields
            $updateData = self::prepareCompanyDataForUpdate($company, $data);

            // Handle image uploads

            foreach(self::IMAGE_FIELDS as $field) {

                if(isset($files[$field]) && $files[$field] instanceof \Illuminate\Http\UploadedFile) {

                    $filePath = self::handleImageUpload($company, $field, $files[$field]);

                    if($filePath) {

                        $previousPath = $company->{$field};

                        if($previousPath && $previousPath !== $filePath) {

                            $obsoleteFiles[] = $previousPath;

                        }

                        $updateData[$field] = $filePath;

                    }

                }

            }

            // Update record

            if(!empty($updateData)) {

                $updateData["updated_at"] = now();
                $updateData["updated_by"] = $userId;
                $company->update($updateData);

            }

            // Update or create social media links

            foreach(self::SOCIAL_MEDIA_TYPES as $type) {

                if(isset($data[$type])) {

                    self::updateOrCreateSocialMedia($company, $type, $data[$type], $userId);

                }

            }

        });

        foreach(array_unique($obsoleteFiles) as $obsoleteFile) {

            Storage::disk("public")->delete($obsoleteFile);

        }

        return $company->fresh(["identityDocumentType"]);

    }
}
