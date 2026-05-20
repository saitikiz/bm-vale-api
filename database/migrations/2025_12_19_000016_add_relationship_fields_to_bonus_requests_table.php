<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipFieldsToBonusRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('bonus_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->foreign('worker_id', 'worker_fk_10779975')->references('id')->on('workers');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->foreign('site_id', 'site_fk_10779988')->references('id')->on('sites');
            $table->unsignedBigInteger('bonus_id')->nullable();
            $table->foreign('bonus_id', 'bonus_fk_10780041')->references('id')->on('bonus');
        });
    }
}
