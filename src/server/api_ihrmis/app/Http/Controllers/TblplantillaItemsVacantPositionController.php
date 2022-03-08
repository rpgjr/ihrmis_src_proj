<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommonResource;
use App\Models\TblplantillaItems;
use App\Http\Resources\Plantilla\TblplantillaItemsResource;


/**
 * Description of TblplantillaItemsVacantPositionController
 *
 * @author Administrator
 */
class TblplantillaItemsVacantPositionController extends Controller {

    public function getVacantPositions( $type) {

        $item_query = TblplantillaItems::with('tbloffices', 'tblpositions')->where('is_vacant', $type)->get();
        return TblplantillaItemsResource::collection($item_query);
    }
}
