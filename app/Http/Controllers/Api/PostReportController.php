<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostReport;
use App\Http\Resources\User;
use Illuminate\Http\Request;

class PostReportController extends Controller
{

    public function delete($id)
    {
        $report = PostReport::findOrFail($id);
        $user = auth()->user();

        if ($user->can('delete', $report)) {
            $report->delete();
            return response()->state(204);
        }

        return response()->json([
            'code' => 'NOT_AUTHORIZED',
        ], 403);
    }
}
