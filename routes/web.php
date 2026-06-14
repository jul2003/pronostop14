<?php

use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\JourneeController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\SeasonScoringRuleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\InitialSetupController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\PronoController;
use App\Http\Controllers\RankingController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Season;
use App\Http\Controllers\Admin\SettingController;

Route::get('/', function () {

    if (User::count() === 0) {
        return view('site-not-initialized');
    }

    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $season = Season::where('is_active', true)->first();

    return view('home', [
        'season' => $season,
    ]);
})->name('home');


Route::get('/initialisation', [InitialSetupController::class, 'create'])
    ->name('initial-setup.create');

Route::post('/initialisation', [InitialSetupController::class, 'store'])
    ->name('initial-setup.store');

Route::middleware('auth')->group(function () {
    //Profil utilisateur
    Route::get('/mon-profil', [PlayerProfileController::class, 'edit'])
        ->name('player-profile.edit');

    Route::put('/mon-profil', [PlayerProfileController::class, 'update'])
        ->name('player-profile.update');

    //Pronostic
    Route::get('/pronos', [PronoController::class, 'index'])
        ->name('pronos.index');

    Route::get('/pronos/{season}/{journee}', [PronoController::class, 'show'])
        ->name('pronos.show');

    Route::post('/pronos/{season}/{journee}', [PronoController::class, 'storeAll'])
        ->name('pronos.store');

    //Classement
    Route::get('/classements', function () {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            return redirect()
                ->route('home')
                ->with('error', 'Aucune saison active pour le moment.');
        }

        return redirect()->route('rankings.general', $season);
    })->name('rankings.index');

    Route::get('/classements/{season}', [RankingController::class, 'general'])
        ->name('rankings.general');

    Route::get('/classements/{season}/{journee}', [RankingController::class, 'journee'])
        ->name('rankings.journee');

    //Resulats
    Route::get('/saisons/{season}/journees/{journee}/resultats', [RankingController::class, 'journeeResults'])
        ->name('journees.results');

    Route::get('/saisons/{season}/resultats', [RankingController::class, 'seasonResults'])
        ->name('seasons.results');

    //Pour stopper la reprise historique des pronos
    Route::post('impersonation/stop', [UserController::class, 'stopImpersonating'])
        ->name('impersonation.stop');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', fn () => view('admin.index'))
        ->name('admin.index');

    //Parametres
    Route::get('/admin/parametres', [SettingController::class, 'index'])
        ->name('admin.settings.index');

    Route::put('/admin/parametres', [SettingController::class, 'update'])
        ->name('admin.settings.update');

    Route::get('/admin/parametres/baremes/create', [SettingController::class, 'createScoringProfile'])
        ->name('admin.settings.scoring-profiles.create');

    Route::post('/admin/parametres/baremes', [SettingController::class, 'storeScoringProfile'])
        ->name('admin.settings.scoring-profiles.store');

    Route::get('/admin/parametres/baremes/{profile}/edit', [SettingController::class, 'editScoringProfile'])
        ->name('admin.settings.scoring-profiles.edit');

    Route::put('/admin/parametres/baremes/{profile}', [SettingController::class, 'updateScoringProfile'])
        ->name('admin.settings.scoring-profiles.update');

    // Clubs
    Route::get('/admin/clubs', [ClubController::class, 'index'])
        ->name('admin.clubs.index');

    Route::get('/admin/clubs/create', [ClubController::class, 'create'])
        ->name('admin.clubs.create');

    Route::post('/admin/clubs', [ClubController::class, 'store'])
        ->name('admin.clubs.store');

    Route::get('/admin/clubs/{club}/edit', [ClubController::class, 'edit'])
        ->name('admin.clubs.edit');

    Route::put('/admin/clubs/{club}', [ClubController::class, 'update'])
        ->name('admin.clubs.update');

    Route::delete('/admin/clubs/{club}', [ClubController::class, 'destroy'])
        ->name('admin.clubs.destroy');

    // Saisons
    Route::get('/admin/saisons', [SeasonController::class, 'index'])
        ->name('admin.seasons.index');

    Route::get('/admin/saisons/create', [SeasonController::class, 'create'])
        ->name('admin.seasons.create');

    Route::post('/admin/saisons', [SeasonController::class, 'store'])
        ->name('admin.seasons.store');

    Route::get('/admin/saisons/{season}/edit', [SeasonController::class, 'edit'])
        ->name('admin.seasons.edit');

    Route::put('/admin/saisons/{season}', [SeasonController::class, 'update'])
        ->name('admin.seasons.update');

    Route::delete('/admin/saisons/{season}', [SeasonController::class, 'destroy'])
        ->name('admin.seasons.destroy');

    Route::post('/admin/saisons/{season}/generate-journees', [SeasonController::class, 'generateJournees'])
        ->name('admin.seasons.generateJournees');

    // Clubs d'une saison
    Route::get('/admin/saisons/{season}/clubs', [SeasonController::class, 'clubs'])
        ->name('admin.seasons.clubs');

    Route::post('/admin/saisons/{season}/clubs', [SeasonController::class, 'syncClubs'])
        ->name('admin.seasons.clubs.sync');

    // Joueurs d'une saison
    Route::get('/admin/saisons/{season}/joueurs', [SeasonController::class, 'players'])
        ->name('admin.seasons.players');

    Route::post('/admin/saisons/{season}/joueurs', [SeasonController::class, 'syncPlayers'])
        ->name('admin.seasons.players.sync');

    // Barème d'une saison
    Route::get('/admin/saisons/{season}/bareme', [SeasonScoringRuleController::class, 'edit'])
        ->name('admin.seasons.scoring.edit');

    Route::put('/admin/saisons/{season}/bareme', [SeasonScoringRuleController::class, 'update'])
        ->name('admin.seasons.scoring.update');

    // Journées d'une saison
    Route::get('/admin/saisons/{season}/journees', [JourneeController::class, 'season'])
        ->name('admin.seasons.journees');

    Route::get('/admin/saisons/{season}/journees/{journee}/edit', [JourneeController::class, 'edit'])
        ->name('admin.seasons.journees.edit');

    Route::put('/admin/saisons/{season}/journees/{journee}', [JourneeController::class, 'update'])
        ->name('admin.seasons.journees.update');

    // Matchs d'une journée
    Route::get('/admin/saisons/{season}/journees/{journee}/matches', [MatchController::class, 'manage'])
        ->name('admin.seasons.journees.matches');

    Route::post('/admin/saisons/{season}/journees/{journee}/matches', [MatchController::class, 'store'])
        ->name('admin.seasons.journees.matches.store');

    Route::post('/admin/saisons/{season}/journees/{journee}/matches/reorder', [MatchController::class, 'reorder'])
        ->name('admin.seasons.journees.matches.reorder');

    Route::delete('/admin/matches/{match}', [MatchController::class, 'destroy'])
        ->name('admin.matches.destroy');

    Route::post('/admin/saisons/{season}/journees/{journee}/matches/bulk', [MatchController::class, 'storeBulk'])
        ->name('admin.seasons.journees.matches.storeBulk');

    // Résultats
    Route::get('/admin/saisons/{season}/journees/{journee}/results', [MatchController::class, 'results'])
        ->name('admin.seasons.journees.results');

    Route::post('/admin/saisons/{season}/journees/{journee}/results', [MatchController::class, 'storeResults'])
        ->name('admin.seasons.journees.results.store');

    // Utilisateurs
    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');

    Route::get('/admin/users/create', [UserController::class, 'create'])
        ->name('admin.users.create');

    Route::post('/admin/users', [UserController::class, 'store'])
        ->name('admin.users.store');

    Route::patch('/admin/users/{user}/role', [UserController::class, 'updateRole'])
        ->name('admin.users.updateRole');

    //Pour reprise historique pronos
    Route::post('/admin/users/{user}/impersonate', [UserController::class, 'impersonate'])
        ->name('admin.users.impersonate');

});

require __DIR__.'/auth.php';
