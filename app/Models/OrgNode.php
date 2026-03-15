<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgNode extends Model
{
    protected $fillable = ['customer_id', 'parent_id', 'relation_label', 'name', 'code', 'description', 'color', 'sort_order'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgNode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgNode::class, 'parent_id')->orderBy('sort_order');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_org_node')->withPivot('customer_number', 'contract_number', 'start_date', 'end_date');
    }
}
