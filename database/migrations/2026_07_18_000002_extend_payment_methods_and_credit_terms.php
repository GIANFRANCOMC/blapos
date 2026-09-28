<?php

use Illuminate\Database\Migrations\{Migration};
use Illuminate\Database\Schema\{Blueprint};
use Illuminate\Support\Facades\{Schema};

return new class extends Migration {
    public function up(): void {

        $this->createAccountsReceivable();
        $this->createAccountsPayable();

    }

    public function down(): void {

        Schema::dropIfExists("purchase_payable_payments");
        Schema::dropIfExists("purchase_payable_installments");
        Schema::dropIfExists("purchase_accounts_payable");
        Schema::dropIfExists("sale_receivable_payments");
        Schema::dropIfExists("sale_receivable_installments");
        Schema::dropIfExists("sale_accounts_receivable");

    }

    private function createAccountsReceivable(): void {

        if(!Schema::hasTable("sale_accounts_receivable")) {

            Schema::create("sale_accounts_receivable", function(Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger("sale_header_id");
                $table->unsignedBigInteger("customer_id");
                $table->unsignedBigInteger("currency_id");
                $table->date("issue_date");
                $table->date("due_date")->nullable();
                $table->enum("payment_modality", ["cash_on_delivery", "installments"]);
                $table->decimal("original_amount", 15, 3)->default(0);
                $table->decimal("extra_percentage", 15, 3)->default(0);
                $table->decimal("extra_amount", 15, 3)->default(0);
                $table->decimal("total_amount", 15, 3)->default(0);
                $table->decimal("paid_amount", 15, 3)->default(0);
                $table->decimal("pending_amount", 15, 3)->default(0);
                $table->enum("status", ["pending", "partial", "paid", "overdue", "canceled"])->default("pending");
                $table->string("observation", 500)->nullable();
                $table->timestamp("created_at")->useCurrent()->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamp("updated_at")->nullable();
                $table->integer("updated_by")->nullable();
                $table->timestamp("canceled_at")->nullable();
                $table->integer("canceled_by")->nullable();

                $table->foreign("sale_header_id")->references("id")->on("sales_header")->onDelete("cascade");
                $table->foreign("customer_id")->references("id")->on("customers")->restrictOnDelete();
                $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
                $table->unique(["sale_header_id"], "sale_receivables_sale_uq");
                $table->index(["customer_id", "status", "issue_date", "id"], "sale_receivables_customer_status_date_idx");
                $table->index(["status", "due_date", "id"], "sale_receivables_status_due_date_idx");

            });

        }

        if(!Schema::hasTable("sale_receivable_installments")) {

            Schema::create("sale_receivable_installments", function(Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger("sale_account_receivable_id");
                $table->unsignedInteger("installment_number");
                $table->date("due_date")->nullable();
                $table->decimal("amount", 15, 3)->default(0);
                $table->decimal("paid_amount", 15, 3)->default(0);
                $table->decimal("pending_amount", 15, 3)->default(0);
                $table->enum("status", ["pending", "partial", "paid", "overdue", "canceled"])->default("pending");
                $table->timestamp("created_at")->useCurrent()->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamp("updated_at")->nullable();
                $table->integer("updated_by")->nullable();

                $table->foreign("sale_account_receivable_id", "fk_sale_recv_inst_account")->references("id")->on("sale_accounts_receivable")->onDelete("cascade");
                $table->unique(["sale_account_receivable_id", "installment_number"], "sale_recv_installments_account_number_uq");
                $table->index(["status", "due_date", "id"], "sale_recv_installments_status_due_idx");

            });

        }

        if(!Schema::hasTable("sale_receivable_payments")) {

            Schema::create("sale_receivable_payments", function(Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger("sale_account_receivable_id");
                $table->unsignedBigInteger("payment_method_id")->nullable();
                $table->unsignedBigInteger("payment_method_variant_id")->nullable();
                $table->dateTime("paid_at");
                $table->decimal("amount", 15, 3)->default(0);
                $table->string("reference", 100)->nullable();
                $table->string("observation", 500)->nullable();
                $table->enum("status", ["active", "canceled"])->default("active");
                $table->timestamp("created_at")->useCurrent()->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamp("updated_at")->nullable();
                $table->integer("updated_by")->nullable();

                $table->foreign("sale_account_receivable_id", "fk_sale_recv_pay_account")->references("id")->on("sale_accounts_receivable")->onDelete("cascade");
                $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();
                $table->foreign("payment_method_variant_id")->references("id")->on("payment_method_variants")->nullOnDelete();
                $table->index(["sale_account_receivable_id", "status", "paid_at", "id"], "sale_recv_payments_account_status_idx");

            });

        }

    }

    private function createAccountsPayable(): void {

        if(!Schema::hasTable("purchase_accounts_payable")) {

            Schema::create("purchase_accounts_payable", function(Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger("purchase_header_id");
                $table->unsignedBigInteger("supplier_id");
                $table->unsignedBigInteger("currency_id");
                $table->date("issue_date");
                $table->date("due_date")->nullable();
                $table->enum("payment_modality", ["cash_on_delivery", "installments"]);
                $table->decimal("original_amount", 15, 3)->default(0);
                $table->decimal("extra_percentage", 15, 3)->default(0);
                $table->decimal("extra_amount", 15, 3)->default(0);
                $table->decimal("total_amount", 15, 3)->default(0);
                $table->decimal("paid_amount", 15, 3)->default(0);
                $table->decimal("pending_amount", 15, 3)->default(0);
                $table->enum("status", ["pending", "partial", "paid", "overdue", "canceled"])->default("pending");
                $table->string("observation", 500)->nullable();
                $table->timestamp("created_at")->useCurrent()->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamp("updated_at")->nullable();
                $table->integer("updated_by")->nullable();
                $table->timestamp("canceled_at")->nullable();
                $table->integer("canceled_by")->nullable();

                $table->foreign("purchase_header_id")->references("id")->on("purchase_headers")->onDelete("cascade");
                $table->foreign("supplier_id")->references("id")->on("suppliers")->restrictOnDelete();
                $table->foreign("currency_id")->references("id")->on("currencies")->restrictOnDelete();
                $table->unique(["purchase_header_id"], "purchase_payables_purchase_uq");
                $table->index(["supplier_id", "status", "issue_date", "id"], "purchase_payables_supplier_status_date_idx");
                $table->index(["status", "due_date", "id"], "purchase_payables_status_due_date_idx");

            });

        }

        if(!Schema::hasTable("purchase_payable_installments")) {

            Schema::create("purchase_payable_installments", function(Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger("purchase_account_payable_id");
                $table->unsignedInteger("installment_number");
                $table->date("due_date")->nullable();
                $table->decimal("amount", 15, 3)->default(0);
                $table->decimal("paid_amount", 15, 3)->default(0);
                $table->decimal("pending_amount", 15, 3)->default(0);
                $table->enum("status", ["pending", "partial", "paid", "overdue", "canceled"])->default("pending");
                $table->timestamp("created_at")->useCurrent()->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamp("updated_at")->nullable();
                $table->integer("updated_by")->nullable();

                $table->foreign("purchase_account_payable_id", "fk_purchase_pay_inst_account")->references("id")->on("purchase_accounts_payable")->onDelete("cascade");
                $table->unique(["purchase_account_payable_id", "installment_number"], "purchase_pay_installments_account_number_uq");
                $table->index(["status", "due_date", "id"], "purchase_pay_installments_status_due_idx");

            });

        }

        if(!Schema::hasTable("purchase_payable_payments")) {

            Schema::create("purchase_payable_payments", function(Blueprint $table) {

                $table->id();
                $table->unsignedBigInteger("purchase_account_payable_id");
                $table->unsignedBigInteger("payment_method_id")->nullable();
                $table->unsignedBigInteger("payment_method_variant_id")->nullable();
                $table->dateTime("paid_at");
                $table->decimal("amount", 15, 3)->default(0);
                $table->string("reference", 100)->nullable();
                $table->string("observation", 500)->nullable();
                $table->enum("status", ["active", "canceled"])->default("active");
                $table->timestamp("created_at")->useCurrent()->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamp("updated_at")->nullable();
                $table->integer("updated_by")->nullable();

                $table->foreign("purchase_account_payable_id", "fk_purchase_pay_pay_account")->references("id")->on("purchase_accounts_payable")->onDelete("cascade");
                $table->foreign("payment_method_id")->references("id")->on("payment_methods")->nullOnDelete();
                $table->foreign("payment_method_variant_id")->references("id")->on("payment_method_variants")->nullOnDelete();
                $table->index(["purchase_account_payable_id", "status", "paid_at", "id"], "purchase_pay_payments_account_status_idx");

            });

        }

    }
};
