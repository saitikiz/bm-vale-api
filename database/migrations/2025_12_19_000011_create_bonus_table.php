<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBonusTable extends Migration
{
    public function up()
    {
        Schema::create('bonus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->boolean('active')->default(0)->nullable();
            $table->string('name')->nullable();
            $table->string('category');
            $table->integer('priority');
            $table->integer('ordering');
            $table->longText('description')->nullable();
            $table->integer('delay')->nullable();
            $table->boolean('auto_assign')->default(0)->nullable();
            $table->datetime('start_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->string('timezone');
            $table->string('function_name')->nullable();
            $table->string('sourceid')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
