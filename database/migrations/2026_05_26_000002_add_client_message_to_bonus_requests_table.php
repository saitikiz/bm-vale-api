<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientMessageToBonusRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('bonus_requests', function (Blueprint $table) {
            $table->text('client_message')->nullable()->after('status_reason');
        });
    }

    public function down()
    {
        Schema::table('bonus_requests', function (Blueprint $table) {
            $table->dropColumn('client_message');
        });
    }
}
