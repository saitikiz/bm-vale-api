<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBonusRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('bonus_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->string('customer_username')->nullable();
            $table->string('customer_code')->nullable();
            $table->string('customerid')->nullable();
            $table->string('source');
            $table->string('ip')->nullable();
            $table->string('status')->nullable();
            $table->string('status_reason')->nullable();
            $table->string('note')->nullable();
            $table->datetime('locked_at')->nullable();
            $table->integer('retry_count');
            $table->longText('last_error')->nullable();
            $table->longText('site_summary')->nullable();
            $table->longText('bonus_history')->nullable();
            $table->longText('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
