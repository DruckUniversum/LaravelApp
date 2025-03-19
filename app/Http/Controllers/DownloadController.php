<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\Order;
use App\Models\Tag;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    public function download(Request $request)
    {
        if($request->has('order_id')) {
            $order = Order::find($request->order_id);
            if(!$order) return App::abort(404);
            if($order->User_ID != auth()->id()) return App::abort(403);

            return Storage::download("stl/{$order->design->STL_File}.stl", "{$order->design->Name}.stl");

        } elseif($request->has('tender_id')) {
            $tender = Tender::find($request->tender_id);
            if(!$tender) return App::abort(404);
            if($tender->Provider_ID != auth()->id()) return App::abort(403);

            $tender->Status = "PROCESSING";
            if(!$tender->save()) return App::abort(500);

            return Storage::download("stl/{$tender->order->design->STL_File}.stl", "{$tender->order->design->Name}.stl");

        } else return App::abort(404);
    }
}
