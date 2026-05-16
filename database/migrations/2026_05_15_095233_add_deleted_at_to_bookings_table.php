public function up()
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->softDeletes();
    });
}

public function down()
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}