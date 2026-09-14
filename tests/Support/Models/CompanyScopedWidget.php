<?php

namespace Tests\Support\Models;

use App\Core\Concerns\BelongsToCompany;
use App\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Minimal Eloquent model used only by Core kernel tests to exercise
 * the HasUuid / BelongsToCompany traits without depending on a real
 * business module.
 */
class CompanyScopedWidget extends Model
{
    use BelongsToCompany;
    use HasUuid;

    protected $table = 'company_scoped_widgets';

    protected $guarded = [];

    public $timestamps = false;
}
