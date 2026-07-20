<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property int $element_id
 * @property bool $is_yang
 */
class MasterStem extends Model
{
    protected $table = 'master_stems';

    protected $fillable = ['name', 'element_id', 'is_yang'];

    protected function casts(): array
    {
        return [
            'is_yang' => 'boolean',
        ];
    }
}
