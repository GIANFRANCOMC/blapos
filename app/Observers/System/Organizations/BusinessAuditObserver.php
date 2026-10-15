<?php

declare(strict_types=1);

namespace App\Observers\System\Organizations;

use App\Services\System\Organizations\{BusinessAuditService};
use Illuminate\Database\Eloquent\{Model};

final class BusinessAuditObserver {

    public function created(Model $model): void {

        BusinessAuditService::recordModelChange($model, "created");

    }

    public function updated(Model $model): void {

        if($model->wasChanged()) {

            BusinessAuditService::recordModelChange($model, "updated");

        }

    }

    public function deleted(Model $model): void {

        BusinessAuditService::recordModelChange($model, "deleted");

    }

}
