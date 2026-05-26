<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMessageTemplatesToBonusTable extends Migration
{
    public function up()
    {
        Schema::table('bonus', function (Blueprint $table) {
            $table->text('success_message')->nullable()->after('description');
            $table->text('rejection_message')->nullable()->after('success_message');
            $table->text('error_message')->nullable()->after('rejection_message');
        });
    }

    public function down()
    {
        Schema::table('bonus', function (Blueprint $table) {
            $table->dropColumn(['success_message', 'rejection_message', 'error_message']);
        });
    }
}
