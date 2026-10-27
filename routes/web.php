<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignMapController;
use App\Http\Controllers\MapPinController;
use App\Http\Controllers\SessionNoteController;
use App\Http\Controllers\WikiController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [AuthController::class, 'landing'])->name('landing');

// Guest authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard & Campaigns
    Route::get('/dashboard', function () {
        return redirect()->route('campaigns.index');
    })->name('dashboard');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::get('/campaigns/{campaign}/export', [CampaignController::class, 'export'])->name('campaigns.export');

    // Invites & Joining
    Route::post('/campaigns/join', [CampaignController::class, 'join'])->name('campaigns.join');
    Route::get('/join/{code}', [CampaignController::class, 'joinByCode'])->name('campaigns.joinByCode');

    // GM Campaign Management (Members, Trash, Compact)
    Route::post('/campaigns/{campaign}/members/{userId}/role', [CampaignController::class, 'updateMemberRole'])->name('campaigns.updateMemberRole');
    Route::delete('/campaigns/{campaign}/members/{userId}', [CampaignController::class, 'removeMember'])->name('campaigns.removeMember');
    Route::post('/campaigns/{campaign}/trash/empty', [CampaignController::class, 'emptyTrash'])->name('campaigns.emptyTrash');
    Route::post('/campaigns/{campaign}/revisions/compact', [CampaignController::class, 'compactRevisions'])->name('campaigns.compactRevisions');

    // Campaign Custom Maps
    Route::post('/campaigns/{campaign}/maps', [CampaignMapController::class, 'store'])->name('maps.store');
    Route::post('/campaigns/{campaign}/maps/{map}/default', [CampaignMapController::class, 'setDefault'])->name('maps.setDefault');
    Route::delete('/campaigns/{campaign}/maps/{map}', [CampaignMapController::class, 'destroy'])->name('maps.destroy');

    // Wiki nested routes
    Route::get('/campaigns/{campaign}/wiki/create', [WikiController::class, 'create'])->name('wiki.create');
    Route::post('/campaigns/{campaign}/wiki', [WikiController::class, 'store'])->name('wiki.store');
    Route::get('/campaigns/{campaign}/wiki/{wiki}', [WikiController::class, 'show'])->name('wiki.show');
    Route::get('/campaigns/{campaign}/wiki/{wiki}/edit', [WikiController::class, 'edit'])->name('wiki.edit');
    Route::put('/campaigns/{campaign}/wiki/{wiki}', [WikiController::class, 'update'])->name('wiki.update');
    Route::delete('/campaigns/{campaign}/wiki/{wiki}', [WikiController::class, 'destroy'])->name('wiki.destroy');
    Route::post('/campaigns/{campaign}/wiki/{wiki}/restore', [WikiController::class, 'restore'])->name('wiki.restore');
    Route::get('/campaigns/{campaign}/wiki/{wiki}/revisions', [WikiController::class, 'revisions'])->name('wiki.revisions');
    Route::post('/campaigns/{campaign}/wiki/{wiki}/revisions/{revision}/restore', [WikiController::class, 'restoreRevision'])->name('wiki.restoreRevision');

    // Map Pins
    Route::get('/campaigns/{campaign}/pins', [MapPinController::class, 'index'])->name('pins.index');
    Route::post('/campaigns/{campaign}/pins', [MapPinController::class, 'store'])->name('pins.store');
    Route::put('/campaigns/{campaign}/pins/{pin}', [MapPinController::class, 'update'])->name('pins.update');
    Route::delete('/campaigns/{campaign}/pins/{pin}', [MapPinController::class, 'destroy'])->name('pins.destroy');
    Route::post('/campaigns/{campaign}/pins/{pin}/restore', [MapPinController::class, 'restore'])->name('pins.restore');

    // Session Notes
    Route::post('/campaigns/{campaign}/notes', [SessionNoteController::class, 'store'])->name('notes.store');
    Route::put('/campaigns/{campaign}/notes/{note}', [SessionNoteController::class, 'update'])->name('notes.update');
    Route::post('/campaigns/{campaign}/notes/{note}/pin', [SessionNoteController::class, 'togglePin'])->name('notes.togglePin');
    Route::delete('/campaigns/{campaign}/notes/{note}', [SessionNoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('/campaigns/{campaign}/notes/{note}/restore', [SessionNoteController::class, 'restore'])->name('notes.restore');
});
