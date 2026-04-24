<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterStem extends Model
{
    protected $table = 'master_stems';
    protected $fillable = ['name', 'element', 'yin_yang'];

    // Property Hooks for type safety
    public string $name {
        get => $this->attributes['name'] ?? '';
        set (string $value) => $this->attributes['name'] = $value;
    }

    public string $element {
        get => $this->attributes['element'] ?? '';
        set (string $value) => $this->attributes['element'] = $value;
    }

    public bool $yin_yang {
        get => (bool) ($this->attributes['yin_yang'] ?? false);
        set (bool $value) => $this->attributes['yin_yang'] = $value;
    }
}
