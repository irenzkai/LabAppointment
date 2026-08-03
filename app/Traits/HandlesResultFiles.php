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
            
            // Delete old file from storage if it exists to save space
            if ($res->$fieldName) {
                Storage::disk('public')->delete($res->$fieldName);
            }

            // Stores directly to 'results/' directory in your Supabase Bucket
            $path = $request->file($fieldName)->store('results', 'public');
            $res->update([$fieldName => $path]);
        }
    }
}