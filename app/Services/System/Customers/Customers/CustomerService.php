<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Customers;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Customers\{Customer};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing module operations
 * Handles business logic for creating and updating records
 */
class CustomerService {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.customer";

    /**
     * Allowed fields for record creation and update
     */
    private const ALLOWED_FIELDS = [
        "identity_document_type_id",
        "document_number",
        "name",
        "email",
        "phone_number",
        "emergency_contact_name",
        "emergency_contact_phone",
        "medical_notes",
        "gender",
        "birthdate",
        "status",
    ];

    /**
     * Searchable fields for filtering
     */
    private const SEARCHABLE_FIELDS = [
        "document_number",
        "name",
        "email",
        "phone_number",
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
     * Prepare data for creation
     *
     * @param  array  $data Input data
     * @param  int  $companyId Company
     * @param  int  $userId User
     */
    private static function prepareCustomerDataForCreate(array $data, int $userId): array {

        $customerData = [
            "status" => $data["status"] ?? "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field])) {

                $customerData[$field] = $data[$field];

            }

        }

        return $customerData;

    }

    /**
     * Prepare data for update (only changed fields)
     *
     * @param  Customer  $customer Record instance
     * @param  array  $data Input data
     */
    private static function prepareCustomerDataForUpdate(Customer $customer, array $data): array {

        $updateData = [];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field]) && $data[$field] !== $customer->$field) {

                $updateData[$field] = $data[$field];

            }

        }

        return $updateData;

    }

    /**
     * Create a new record
     *
     * @param  array  $data Input data
     * @param  int|null  $userId User creating the record
     * @return Customer|null Created record instance or null on failure
     *
     * @throws Exception
     */
    public static function create(array $data, int $userId): ?Customer {

        $customer = null;

        DB::transaction(function() use ($data, $userId, &$customer) {

            // Prepare data with only allowed fields
            $customerData = self::prepareCustomerDataForCreate($data, $userId);

            // Create the record

            $customer = Customer::create($customerData);

        });

        return $customer;

    }

    /**
     * Update an existing record
     *
     * @param  Customer  $customer Record instance to update
     * @param  array  $data Input data
     * @param  int|null  $userId User updating the record
     * @return Customer Updated record instance
     */
    public static function update(Customer $customer, array $data, int $userId): Customer {

        DB::transaction(function() use ($customer, $data, $userId) {

            // Prepare update data with only changed fields
            $updateData = self::prepareCustomerDataForUpdate($customer, $data);

            // Only update if there are changes

            if(!empty($updateData)) {

                $updateData["updated_at"] = now();
                $updateData["updated_by"] = $userId;
                $customer->update($updateData);

            }

        });

        return $customer->fresh(["identityDocumentType"]);

    }

    /**
     * Find record by ID and company ID
     *
     * @param  int  $id Record
     * @param  int  $companyId Company
     * @param  array|null  $statuses Filter by statuses (e.g. ["active"], ["active", "inactive"])
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, ?array $statuses = ["active"], array $relations = ["identityDocumentType"]): ?Customer {

        $query = Customer::where("id", $id);

        if($statuses !== null && !empty($statuses)) {

            $query->whereIn("status", $statuses);

        }

        if(!empty($relations)) {

            $query->with($relations);

        }

        return $query->first();

    }

    /**
     * Get paginated list of records with filters
     *
     * @param  int  $companyId Company
     * @param  array  $filters Filter parameters (filter_by, word)
     * @param  int  $perPage Items per page
     */
    public static function getPaginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator {

        $query = Customer::query()
            ->with(["identityDocumentType"]);

        // Apply filters

        $filterBy = $filters["filter_by"] ?? null;

        $word = $filters["word"] ?? null;

        if(Utilities::isDefined($word) && Utilities::isDefined($filterBy)) {

            $searchTerm = Utilities::getWordSearch($word);

            if($filterBy === "all") {

                // Search across all searchable fields
                $query->where(function(Builder $q) use ($searchTerm) {

                    $searchableFields = self::SEARCHABLE_FIELDS;
                    $firstField = array_shift($searchableFields);

                    if($firstField) {

                        $q->where($firstField, "like", $searchTerm);

                    }

                    foreach($searchableFields as $field) {

                        $q->orWhere($field, "like", $searchTerm);

                    }

                });

            }elseif(in_array($filterBy, self::SEARCHABLE_FIELDS, true)) {

                // Search in specific field
                $query->where($filterBy, "like", $searchTerm);

            }

        }

        return $query->orderBy("name", "ASC")
            ->paginate($perPage);

    }
}
