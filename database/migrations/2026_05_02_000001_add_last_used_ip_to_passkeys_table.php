<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastUsedIpToPasskeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('passkeys', 'last_used_ip')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->string('last_used_ip', 45)->nullable()->after('last_used_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('passkeys', 'last_used_ip')) {
            Schema::table('passkeys', function (Blueprint $table) {
                $table->dropColumn('last_used_ip');
            });
        }
    }
}
