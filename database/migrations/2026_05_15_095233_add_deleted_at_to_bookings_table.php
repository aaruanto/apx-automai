<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // deleted_at already exists in create_bookings_table migration
    }

    public function down()
    {
        // nothing to do
    }
};