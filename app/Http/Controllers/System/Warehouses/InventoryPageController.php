<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Warehouses;

use App\Http\Controllers\{Controller};
use Illuminate\Contracts\View\{View};

final class InventoryPageController extends Controller {

    public function stock(): View {

        return view("System/general/Warehouses/inventory/stock");

    }

    public function transfers(): View {

        return view("System/general/Warehouses/inventory/transfers");

    }

    public function guides(): View {

        return view("System/general/Warehouses/inventory/guides");

    }

    public function kardex(): View {

        return view("System/general/Warehouses/inventory/kardex");

    }

    public function valuedKardex(): View {

        return view("System/general/Warehouses/inventory/valued-kardex");

    }

}
