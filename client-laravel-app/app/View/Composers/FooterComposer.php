<?php

namespace App\View\Composers;

use App\Models\ResourceType;
use Illuminate\View\View;

class FooterComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Get active resource types for footer links
        $resourceTypes = ResourceType::where('status', 'active')
            ->orderBy('name')
            ->get();

        $view->with('resourceTypes', $resourceTypes);
    }
}
