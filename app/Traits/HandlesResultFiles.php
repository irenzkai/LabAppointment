<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HandlesResultFiles
{
    /**
     * Handle clinical scan uploads with automatic cleanup of old files.
     */
    public function uploadResultFile($request, $appointment, $fieldName)
    {
        if ($request->hasFile($fieldName)) {
            $res = $appointment->result;
            
            // Delete old file if it exists to save storage
            if ($res->$fieldName) {
                Storage::disk('public')->delete($res->$fieldName);
            }

            $path = $request->file($fieldName)->store('results', 'public');
            $res->update([$fieldName => $path]);
        }
    }
}