<?php

use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\JourneeController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\PendingResultController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\SeasonPreseasonController;
use App\Http\Controllers\Admin\SeasonPreseasonResultController;
use App\Http\Controllers\Admin\SeasonScoringRuleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UpcomingMatchController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\InitialSetupController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\PronoController;
use App\Http\Controllers\RankingController;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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
    Route::get('/mon-profil', [PlayerProfileController::class, 'edit'])
        ->name('player-profile.edit');
    Route::put('/mon-profil', [PlayerProfileController::class, 'update'])
        ->name('player-profile.update');

    Route::get('/profile', function () {
        return redirect()->route('player-profile.edit');
    })->name('profile.edit');

    Route::put('/profile', [PlayerProfileController::class, 'update'])
        ->name('profile.update');

    Route::get('/pronos', [PronoController::class, 'index'])
        ->name('pronos.index');
    Route::get('/pronos/{season}/{journee}', [PronoController::class, 'show'])
        ->name('pronos.show');
    Route::post('/pronos/{season}/{journee}', [PronoController::class, 'storeAll'])
        ->name('pronos.store');

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

    Route::get('/resultats', [RankingController::class, 'resultsIndex'])
        ->name('results.index');
    Route::get('/resultats/{season}', [RankingController::class, 'resultsSeason'])
        ->name('results.season');
    Route::get('/resultats/{season}/{journee}', [RankingController::class, 'resultsJournee'])
        ->name('results.journee');

    Route::get('/bilan', [RankingController::class, 'bilanIndex'])
        ->name('bilan.index');
    Route::get('/bilan/{season}', [RankingController::class, 'bilanSeason'])
        ->name('bilan.season');

    Route::get('/saisons/resultats', function () {
        return redirect()->route('results.index');
    })->name('seasons.active.results');

    Route::get('/saisons/{season}/journees/{journee}/resultats', [RankingController::class, 'journeeResults'])
        ->name('journees.results');
    Route::get('/saisons/{season}/resultats', [RankingController::class, 'seasonResults'])
        ->name('seasons.results');

    Route::post('impersonation/stop', [UserController::class, 'stopImpersonating'])
        ->name('impersonation.stop');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', fn () => view('admin.index'))
        ->name('admin.index');

    Route::get('/admin/maintenance', [MaintenanceController::class, 'index'])
        ->name('admin.maintenance.index');

    Route::get('/admin/parametres-application', [AppSettingController::class, 'index'])
        ->name('admin.app-settings.index');
    Route::put('/admin/parametres-application', [AppSettingController::class, 'update'])
        ->name('admin.app-settings.update');

    Route::get('/admin/matchs-a-saisir', [UpcomingMatchController::class, 'index'])
        ->name('admin.upcoming-matches.index');
    Route::get('/admin/resultats-a-saisir', [PendingResultController::class, 'index'])
        ->name('admin.pending-results.index');

    Route::get('/admin/parametres', [SettingController::class, 'index'])
        ->name('admin.settings.index');
    Route::put('/admin/parametres', [SettingController::class, 'update'])
        ->name('admin.settings.update');

    Route::get('/admin/parametres/avant-saison', [SettingController::class, 'preseason'])
        ->name('admin.settings.preseason');
    Route::post('/admin/parametres/avant-saison', [SettingController::class, 'storePreseasonTemplate'])
        ->name('admin.settings.preseason-templates.store');
    Route::delete('/admin/parametres/avant-saison/{template}', [SettingController::class, 'destroyPreseasonTemplate'])
        ->name('admin.settings.preseason-templates.destroy');
    Route::post('/admin/parametres/avant-saison/reorder', [SettingController::class, 'reorderPreseasonTemplates'])
        ->name('admin.settings.preseason-templates.reorder');

    Route::post('/admin/parametres/groupes-correction-avant-saison', [SettingController::class, 'storePreseasonCorrectionGroupTemplate'])
        ->name('admin.settings.preseason-correction-groups.store');
    Route::put('/admin/parametres/groupes-correction-avant-saison/{correctionGroup}', [SettingController::class, 'updatePreseasonCorrectionGroupTemplate'])
        ->name('admin.settings.preseason-correction-groups.update');
    Route::delete('/admin/parametres/groupes-correction-avant-saison/{correctionGroup}', [SettingController::class, 'destroyPreseasonCorrectionGroupTemplate'])
        ->name('admin.settings.preseason-correction-groups.destroy');
    Route::post('/admin/parametres/groupes-correction-avant-saison/reorder', [SettingController::class, 'reorderPreseasonCorrectionGroupTemplates'])
        ->name('admin.settings.preseason-correction-groups.reorder');

    Route::post('/admin/parametres/bonus-avant-saison', [SettingController::class, 'storePreseasonBonusRuleTemplate'])
        ->name('admin.settings.preseason-bonus-rules.store');
    Route::put('/admin/parametres/bonus-avant-saison/{bonusRule}', [SettingController::class, 'updatePreseasonBonusRuleTemplate'])
        ->name('admin.settings.preseason-bonus-rules.update');
    Route::delete('/admin/parametres/bonus-avant-saison/{bonusRule}', [SettingController::class, 'destroyPreseasonBonusRuleTemplate'])
        ->name('admin.settings.preseason-bonus-rules.destroy');
    Route::post('/admin/parametres/bonus-avant-saison/reorder', [SettingController::class, 'reorderPreseasonBonusRuleTemplates'])
        ->name('admin.settings.preseason-bonus-rules.reorder');

    Route::get('/admin/parametres/baremes/create', [SettingController::class, 'createScoringProfile'])
        ->name('admin.settings.scoring-profiles.create');
    Route::post('/admin/parametres/baremes', [SettingController::class, 'storeScoringProfile'])
        ->name('admin.settings.scoring-profiles.store');
    Route::get('/admin/parametres/baremes/{profile}/edit', [SettingController::class, 'editScoringProfile'])
        ->name('admin.settings.scoring-profiles.edit');
    Route::put('/admin/parametres/baremes/{profile}', [SettingController::class, 'updateScoringProfile'])
        ->name('admin.settings.scoring-profiles.update');

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

    Route::get('/admin/saisons', [SeasonController::class, 'index'])
        ->name('admin.seasons.index');
    Route::get('/admin/saisons/create', [SeasonController::class, 'create'])
        ->name('admin.seasons.create');
    Route::post('/admin/saisons', [SeasonController::class, 'store'])
        ->name('admin.seasons.store');

    Route::get('/admin/saisons/edit', [SeasonController::class, 'edit'])
        ->name('admin.seasons.active.edit');
    Route::get('/admin/saisons/clubs', [SeasonController::class, 'clubs'])
        ->name('admin.seasons.active.clubs');
    Route::get('/admin/saisons/joueurs', [SeasonController::class, 'players'])
        ->name('admin.seasons.active.players');
    Route::get('/admin/saisons/bareme', [SeasonScoringRuleController::class, 'edit'])
        ->name('admin.seasons.active.scoring.edit');
    Route::get('/admin/saisons/journees', [JourneeController::class, 'season'])
        ->name('admin.seasons.active.journees');
    Route::get('/admin/saisons/avant-saison', [SeasonPreseasonController::class, 'edit'])
        ->name('admin.seasons.active.preseason.edit');
    Route::get('/admin/saisons/avant-saison/resultats', [SeasonPreseasonResultController::class, 'edit'])
        ->name('admin.seasons.active.preseason-results.edit');

    Route::get('/admin/saisons/{season}/edit', [SeasonController::class, 'edit'])
        ->name('admin.seasons.edit');
    Route::put('/admin/saisons/{season}', [SeasonController::class, 'update'])
        ->name('admin.seasons.update');
    Route::delete('/admin/saisons/{season}', [SeasonController::class, 'destroy'])
        ->name('admin.seasons.destroy');
    Route::post('/admin/saisons/{season}/generate-journees', [SeasonController::class, 'generateJournees'])
        ->name('admin.seasons.generateJournees');

    Route::get('/admin/saisons/{season}/clubs', [SeasonController::class, 'clubs'])
        ->name('admin.seasons.clubs');
    Route::post('/admin/saisons/{season}/clubs', [SeasonController::class, 'syncClubs'])
        ->name('admin.seasons.clubs.sync');

    Route::get('/admin/saisons/{season}/joueurs', [SeasonController::class, 'players'])
        ->name('admin.seasons.players');
    Route::post('/admin/saisons/{season}/joueurs', [SeasonController::class, 'syncPlayers'])
        ->name('admin.seasons.players.sync');
    Route::post('/admin/saisons/{season}/joueurs/reorder', [SeasonController::class, 'reorderPlayers'])
        ->name('admin.seasons.players.reorder');

    Route::get('/admin/saisons/{season}/bareme', [SeasonScoringRuleController::class, 'edit'])
        ->name('admin.seasons.scoring.edit');
    Route::put('/admin/saisons/{season}/bareme', [SeasonScoringRuleController::class, 'update'])
        ->name('admin.seasons.scoring.update');

    Route::get('/admin/saisons/{season}/journees', [JourneeController::class, 'season'])
        ->name('admin.seasons.journees');
    Route::get('/admin/saisons/{season}/journees/{journee}/edit', [JourneeController::class, 'edit'])
        ->name('admin.seasons.journees.edit');
    Route::put('/admin/saisons/{season}/journees/{journee}', [JourneeController::class, 'update'])
        ->name('admin.seasons.journees.update');

    Route::get('/admin/saisons/{season}/avant-saison', [SeasonPreseasonController::class, 'edit'])
        ->name('admin.seasons.preseason.edit');
    Route::put('/admin/saisons/{season}/avant-saison/questions', [SeasonPreseasonController::class, 'updateQuestions'])
        ->name('admin.seasons.preseason.questions.update');
    Route::post('/admin/saisons/{season}/avant-saison/questions', [SeasonPreseasonController::class, 'storeQuestion'])
        ->name('admin.seasons.preseason.questions.store');
    Route::delete('/admin/saisons/{season}/avant-saison/questions/{question}', [SeasonPreseasonController::class, 'destroyQuestion'])
        ->name('admin.seasons.preseason.questions.destroy');

    Route::put('/admin/saisons/{season}/avant-saison/groupes-correction', [SeasonPreseasonController::class, 'updateCorrectionGroups'])
        ->name('admin.seasons.preseason.correction-groups.update');
    Route::post('/admin/saisons/{season}/avant-saison/groupes-correction', [SeasonPreseasonController::class, 'storeCorrectionGroup'])
        ->name('admin.seasons.preseason.correction-groups.store');
    Route::delete('/admin/saisons/{season}/avant-saison/groupes-correction/{correctionGroup}', [SeasonPreseasonController::class, 'destroyCorrectionGroup'])
        ->name('admin.seasons.preseason.correction-groups.destroy');

    Route::put('/admin/saisons/{season}/avant-saison/bonus', [SeasonPreseasonController::class, 'updateBonusRules'])
        ->name('admin.seasons.preseason.bonus.update');
    Route::post('/admin/saisons/{season}/avant-saison/bonus', [SeasonPreseasonController::class, 'storeBonusRule'])
        ->name('admin.seasons.preseason.bonus.store');
    Route::delete('/admin/saisons/{season}/avant-saison/bonus/{bonusRule}', [SeasonPreseasonController::class, 'destroyBonusRule'])
        ->name('admin.seasons.preseason.bonus.destroy');
    Route::post('/admin/saisons/{season}/avant-saison/appliquer-aux-parametres-globaux', [SeasonPreseasonController::class, 'syncToGlobal'])
        ->name('admin.seasons.preseason.sync-to-global');

    Route::get('/admin/saisons/{season}/avant-saison/resultats', [SeasonPreseasonResultController::class, 'edit'])
        ->name('admin.seasons.preseason-results.edit');
    Route::put('/admin/saisons/{season}/avant-saison/resultats', [SeasonPreseasonResultController::class, 'update'])
        ->name('admin.seasons.preseason-results.update');

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
    Route::get('/admin/saisons/{season}/journees/{journee}/results', [MatchController::class, 'results'])
        ->name('admin.seasons.journees.results');
    Route::post('/admin/saisons/{season}/journees/{journee}/results', [MatchController::class, 'storeResults'])
        ->name('admin.seasons.journees.results.store');

    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');
    Route::get('/admin/users/create', [UserController::class, 'create'])
        ->name('admin.users.create');
    Route::post('/admin/users', [UserController::class, 'store'])
        ->name('admin.users.store');
    Route::patch('/admin/users/{user}/role', [UserController::class, 'updateRole'])
        ->name('admin.users.updateRole');
    Route::post('/admin/users/{user}/impersonate', [UserController::class, 'impersonate'])
        ->name('admin.users.impersonate');
});

require __DIR__.'/auth.php';
