<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorClientMessageOnBonusRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('bonus_requests', function (Blueprint $table) {
            $table->dropColumn('client_message');
            $table->unsignedBigInteger('message_id')->nullable()->after('status_reason');
            $table->text('message_vars')->nullable()->after('message_id');
        });
    }

    public function down()
    {
        Schema::table('bonus_requests', function (Blueprint $table) {
            $table->dropColumn(['message_id', 'message_vars']);
            $table->text('client_message')->nullable()->after('status_reason');
        });
    }
}
