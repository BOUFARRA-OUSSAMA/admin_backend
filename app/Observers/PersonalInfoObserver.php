<?php
// filepath: c:\Users\Microsoft\Documents\portail\admin_backend\app\Observers\PersonalInfoObserver.php

namespace App\Observers;

use App\Models\PersonalInfo;

class PersonalInfoObserver
{
    /**
     * Handle the PersonalInfo "created" event.
     */
    public function created(PersonalInfo $personalInfo): void
    {
        $this->syncUserName($personalInfo);
    }

    /**
     * Handle the PersonalInfo "updated" event.
     */
    public function updated(PersonalInfo $personalInfo): void
    {
        // Vérifier si name ou surname ont changé
        if ($personalInfo->wasChanged('name') || $personalInfo->wasChanged('surname')) {
            $this->syncUserName($personalInfo);
        }
    }

    /**
     * Synchroniser le nom complet dans la table User
     */
    private function syncUserName(PersonalInfo $personalInfo): void
    {
        $fullName = trim($personalInfo->name . ' ' . $personalInfo->surname);
        
        if (!empty($fullName) && $personalInfo->patient && $personalInfo->patient->user) {
            $personalInfo->patient->user->update(['name' => $fullName]);
        }
    }
}