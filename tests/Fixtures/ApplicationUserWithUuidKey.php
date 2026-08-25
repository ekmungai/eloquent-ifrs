<?php

namespace IFRS\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Application User model with a uuid primary key.
 */
class ApplicationUserWithUuidKey extends Model
{
    protected $table = 'application_users';

    protected $primaryKey = 'uuid';
}
