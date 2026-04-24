    <?php
    /**
     * Eloquent IFRS Accounting
     *
     * @author Edward Mungai
     * @copyright Edward Mungai, 2020, Germany
     * @license MIT
     */
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\App;
    use Illuminate\Support\Facades\Schema;

    class IfrsCreateOrUpdateUsersTable extends Migration
    {
        /**
         * Resolve the users table name from the configured User model.
         *
         * Handles both the string format ('App\Models\User') and the legacy
         * array format ([7 => App\User::class, 8 => App\Models\User::class]).
         *
         * @return string
         */
        private function getUsersTable()
        {
            $userModel = config('ifrs.user_model');

            if (is_array($userModel)) {
                $major = (int) App::version();
                $userModel = $userModel[$major] ?? end($userModel);
            }

            if (is_string($userModel) && class_exists($userModel)) {
                return (new $userModel())->getTable();
            }

            return 'users';
        }

        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            $usersTable = $this->getUsersTable();

            if (Schema::hasTable($usersTable)) {
                Schema::table(
                    $usersTable,
                    function (Blueprint $table) {
                        //entity
                        $table->unsignedBigInteger('entity_id')->nullable();
                        // *permanent* deletion
                        $table->dateTime('destroyed_at')->nullable();
                });
            }else{
                Schema::create(
                    $usersTable,
                    function (Blueprint $table) {
                        $table->bigIncrements('id');

                        //entity
                        $table->unsignedBigInteger('entity_id')->nullable();

                        // attributes
                        $table->string('name');
                        $table->string('email')->unique();
                        $table->timestamp('email_verified_at')->nullable();
                        $table->string('password');
                        $table->rememberToken();

                        // *permanent* deletion
                        $table->dateTime('destroyed_at')->nullable();

                        //soft deletion
                        $table->softDeletes();

                        $table->timestamps();

                        // flag for created table
                        $table->boolean('created')->nullable();
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
            $usersTable = $this->getUsersTable();

            if (Schema::hasColumn($usersTable, 'created'))
            {
                Schema::dropIfExists($usersTable);
            }else{
                Schema::table(
                    $usersTable,
                    function (Blueprint $table) {
                        $table->dropColumn('entity_id');
                        $table->dropColumn('destroyed_at');
                    }
                );
            }
        }
    }
