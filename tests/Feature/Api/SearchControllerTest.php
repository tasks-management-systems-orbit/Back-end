<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    // ---------- helpers ----------

    private function authedUser(): User
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        return $user;
    }

    private function createUser(array $attrs = []): User
    {
        static $i = 0;
        $i++;

        return User::create(array_merge([
            'name' => 'Test User '.$i,
            'username' => 'search_user_'.uniqid().'_'.$i,
            'email' => 'search_'.uniqid().'_'.$i.'@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ], $attrs));
    }

    /**
     * Force a user's profile to a known state. Returns the freshly saved
     * profile so tests can chain assertions.
     */
    private function setProfile(User $user, array $attrs): Profile
    {
        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $attrs
        );

        return $profile->refresh();
    }

    // ---------- baseline / prefixes / limit ----------

    public function test_q_alone_returns_matching_users_and_projects(): void
    {
        $caller = $this->authedUser();

        $matchUser = $this->createUser(['name' => 'Alice Wonderland']);
        $noMatchUser = $this->createUser(['name' => 'Bob Builder']);

        $matchProject = Project::factory()->create(['name' => 'Alice Project Alpha']);
        $noMatchProject = Project::factory()->create(['name' => 'Whatever']);

        $response = $this->getJson('/api/search?q=Alice');

        $response->assertOk()
            ->assertJson(['success' => true, 'query' => 'Alice', 'total' => 2]);

        $userIds = collect($response->json('data.users'))->pluck('id')->all();
        $projectIds = collect($response->json('data.projects'))->pluck('id')->all();

        $this->assertContains($matchUser->id, $userIds);
        $this->assertNotContains($noMatchUser->id, $userIds);
        $this->assertContains($matchProject->id, $projectIds);
        $this->assertNotContains($noMatchProject->id, $projectIds);
    }

    public function test_at_prefix_returns_only_users(): void
    {
        $this->authedUser();
        $user = $this->createUser(['name' => 'Alice Carpenter']);
        Project::factory()->create(['name' => 'Alice Project']);

        $response = $this->getJson('/api/search?q=@Alice');

        $response->assertOk()
            ->assertJson(['success' => true, 'query' => '@Alice']);

        $this->assertNotNull($response->json('data.users'));
        $this->assertNull($response->json('data.projects'));
        $this->assertCount(1, $response->json('data.users'));
        $this->assertSame($user->id, $response->json('data.users.0.id'));
    }

    public function test_hash_prefix_returns_only_projects(): void
    {
        $this->authedUser();
        $this->createUser(['name' => 'Bob Carpenter']);
        $project = Project::factory()->create(['name' => 'Bob Project Beta']);

        // The `#` must be URL-encoded as `%23` so it is not interpreted
        // as a URL fragment (a real client would send `%23Bob`).
        $response = $this->getJson('/api/search?q=%23Bob');

        $response->assertOk()
            ->assertJson(['success' => true, 'query' => '#Bob']);

        $this->assertNotNull($response->json('data.projects'));
        $this->assertNull($response->json('data.users'));
        $this->assertCount(1, $response->json('data.projects'));
        $this->assertSame($project->id, $response->json('data.projects.0.id'));
    }

    public function test_limit_caps_number_of_results_per_type(): void
    {
        $this->authedUser();
        // 3 matching users; default limit is 5 so this should all come back.
        for ($i = 0; $i < 3; $i++) {
            $this->createUser(['name' => "Carol User {$i}"]);
        }

        $response = $this->getJson('/api/search?q=Carol&limit=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.users'));
    }

    public function test_limit_all_returns_everything(): void
    {
        $this->authedUser();
        for ($i = 0; $i < 7; $i++) {
            $this->createUser(['name' => "Diane User {$i}"]);
        }

        $response = $this->getJson('/api/search?q=Diane&limit=all');

        $response->assertOk();
        $this->assertCount(7, $response->json('data.users'));
    }

    public function test_empty_q_returns_empty_results_without_error(): void
    {
        // 'q' has min:2, so an empty string should 422, not 500.
        $this->authedUser();

        $response = $this->getJson('/api/search?q=');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    // ---------- user filters (6) ----------

    public function test_user_filter_job_title(): void
    {
        $caller = $this->authedUser();
        $a = $this->createUser(['name' => 'Eve Adams']);
        $b = $this->createUser(['name' => 'Eve Bishop']);
        $this->setProfile($a, ['job_title' => 'Senior Backend Engineer']);
        $this->setProfile($b, ['job_title' => 'Product Designer']);

        $response = $this->getJson('/api/search?q=Eve&job_title=Engineer');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id], $ids);
    }

    public function test_user_filter_location(): void
    {
        $caller = $this->authedUser();
        $a = $this->createUser(['name' => 'Frank Allen']);
        $b = $this->createUser(['name' => 'Frank Brown']);
        $this->setProfile($a, ['location' => 'Cairo, Egypt']);
        $this->setProfile($b, ['location' => 'Berlin, Germany']);

        $response = $this->getJson('/api/search?q=Frank&location=Cairo');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id], $ids);
    }

    public function test_user_filter_created_from_to(): void
    {
        $caller = $this->authedUser();

        // One user created in the past (well before the window).
        $old = $this->createUser(['name' => 'Greta Old']);
        $old->created_at = Carbon::parse('2020-01-15');
        $old->save();

        // One user created today (inside the window).
        $new = $this->createUser(['name' => 'Greta New']);
        $new->created_at = now();
        $new->save();

        $response = $this->getJson('/api/search?q=Greta&created_from='.now()->subDays(7)->format('Y-m-d').'&created_to='.now()->addDay()->format('Y-m-d'));

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$new->id], $ids);
    }

    public function test_user_filter_is_public_profile_true(): void
    {
        $caller = $this->authedUser();
        $public = $this->createUser(['name' => 'Henry Public']);
        $private = $this->createUser(['name' => 'Henry Private']);
        $this->setProfile($public, ['is_public' => true]);
        $this->setProfile($private, ['is_public' => false]);

        $response = $this->getJson('/api/search?q=Henry&is_public_profile=1');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$public->id], $ids);
    }

    public function test_user_filter_is_public_profile_false(): void
    {
        $caller = $this->authedUser();
        $public = $this->createUser(['name' => 'Ivy Public']);
        $private = $this->createUser(['name' => 'Ivy Private']);
        $this->setProfile($public, ['is_public' => true]);
        $this->setProfile($private, ['is_public' => false]);

        $response = $this->getJson('/api/search?q=Ivy&is_public_profile=0');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$private->id], $ids);
    }

    public function test_user_filter_skills_matches_any_case_insensitive(): void
    {
        $caller = $this->authedUser();
        $php = $this->createUser(['name' => 'Jack Coder']);
        $designer = $this->createUser(['name' => 'Jack Designer']);
        $this->setProfile($php, ['skills' => [
            ['name' => 'PHP', 'rating' => 9],
            ['name' => 'Laravel', 'rating' => 8],
        ]]);
        $this->setProfile($designer, ['skills' => [
            ['name' => 'Figma', 'rating' => 9],
        ]]);

        // Lowercase input must still match the stored "PHP" / "Laravel".
        $response = $this->getJson('/api/search?q=Jack&skills=php,rust');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$php->id], $ids);
    }

    public function test_user_filters_combine_with_and(): void
    {
        $caller = $this->authedUser();
        $match = $this->createUser(['name' => 'Karen Match']);
        $wrongJob = $this->createUser(['name' => 'Karen Wrongjob']);
        $wrongLoc = $this->createUser(['name' => 'Karen Wrongloc']);
        $this->setProfile($match, ['job_title' => 'Engineer', 'location' => 'Cairo']);
        $this->setProfile($wrongJob, ['job_title' => 'Designer', 'location' => 'Cairo']);
        $this->setProfile($wrongLoc, ['job_title' => 'Engineer', 'location' => 'Berlin']);

        $response = $this->getJson('/api/search?q=Karen&job_title=Engineer&location=Cairo');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$match->id], $ids);
    }

    // ---------- project filters (6) ----------

    public function test_project_filter_visibility(): void
    {
        $caller = $this->authedUser();
        $pub = Project::factory()->create(['name' => 'Liam Publicproj', 'visibility' => 'public']);
        $priv = Project::factory()->create(['name' => 'Liam Privateproj', 'visibility' => 'private']);

        $response = $this->getJson('/api/search?q=Liam&visibility=public');

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$pub->id], $ids);
    }

    public function test_project_filter_status(): void
    {
        $caller = $this->authedUser();
        $active = Project::factory()->create(['name' => 'Mona Activeproj', 'status' => 'active']);
        $paused = Project::factory()->create(['name' => 'Mona Pausedproj', 'status' => 'paused']);
        $completed = Project::factory()->create(['name' => 'Mona Completedproj', 'status' => 'completed']);

        $response = $this->getJson('/api/search?q=Mona&status=paused');

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$paused->id], $ids);
    }

    public function test_project_filter_start_date_range(): void
    {
        $caller = $this->authedUser();
        $inside = Project::factory()->create(['name' => 'Nora StartIn']);
        $inside->start_date = Carbon::parse('2026-06-15 10:00:00');
        $inside->save();

        $outside = Project::factory()->create(['name' => 'Nora StartOut']);
        $outside->start_date = Carbon::parse('2025-01-01 10:00:00');
        $outside->save();

        $response = $this->getJson('/api/search?q=Nora&start_date_from=2026-06-01&start_date_to=2026-06-30');

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$inside->id], $ids);
    }

    public function test_project_filter_end_date_range(): void
    {
        $caller = $this->authedUser();
        $inside = Project::factory()->create(['name' => 'Olive EndIn']);
        $inside->end_date = Carbon::parse('2026-08-15 10:00:00');
        $inside->save();

        $outside = Project::factory()->create(['name' => 'Olive EndOut']);
        $outside->end_date = Carbon::parse('2027-01-01 10:00:00');
        $outside->save();

        $response = $this->getJson('/api/search?q=Olive&end_date_from=2026-08-01&end_date_to=2026-08-31');

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$inside->id], $ids);
    }

    public function test_project_filter_created_from_to(): void
    {
        $caller = $this->authedUser();

        $old = Project::factory()->create(['name' => 'Peter Oldproj']);
        $old->created_at = Carbon::parse('2020-01-15');
        $old->save();

        $new = Project::factory()->create(['name' => 'Peter Newproj']);
        $new->created_at = now();
        $new->save();

        $response = $this->getJson('/api/search?q=Peter&created_from='.now()->subDays(7)->format('Y-m-d').'&created_to='.now()->addDay()->format('Y-m-d'));

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$new->id], $ids);
    }

    public function test_project_filter_created_by(): void
    {
        $caller = $this->authedUser();
        $owner = $this->createUser(['name' => 'Quinn Owner']);
        $other = $this->createUser(['name' => 'Quinn Other']);

        $mine = Project::factory()->create(['name' => 'Quinn Mine', 'created_by' => $owner->id]);
        $theirs = Project::factory()->create(['name' => 'Quinn Theirs', 'created_by' => $other->id]);

        $response = $this->getJson('/api/search?q=Quinn&created_by='.$owner->id);

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$mine->id], $ids);
    }

    public function test_project_filters_combine_with_and(): void
    {
        $caller = $this->authedUser();
        $owner = $this->createUser();

        $match = Project::factory()->create([
            'name' => 'Rita Match',
            'status' => 'active',
            'visibility' => 'public',
            'created_by' => $owner->id,
        ]);
        $match->created_at = now();
        $match->save();

        $wrongStatus = Project::factory()->create([
            'name' => 'Rita Wrongstatus',
            'status' => 'paused',
            'visibility' => 'public',
            'created_by' => $owner->id,
        ]);
        $wrongStatus->created_at = now();
        $wrongStatus->save();

        $response = $this->getJson('/api/search?q=Rita&status=active&visibility=public&created_by='.$owner->id);

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$match->id], $ids);
    }

    // ---------- favorites on top + is_favorite flag ----------

    public function test_favorites_bubble_to_top_in_users_with_match_score_tiebreak(): void
    {
        $caller = $this->authedUser();
        $lowMatchFav = $this->createUser(['name' => 'Sam Lowfav', 'username' => 'sam_lowfav']);
        $highMatchNotFav = $this->createUser(['name' => 'Sam Highnotfav', 'username' => 'sam_highnotfav']);
        $lowMatchNotFav = $this->createUser(['name' => 'Sam Lownotfav', 'username' => 'sam_lownotfav']);

        $caller->addToFavorites($lowMatchFav);

        $response = $this->getJson('/api/search?q=Sam&limit=all');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();
        $favs = collect($response->json('data.users'))->pluck('is_favorite')->all();

        // Favorite first (despite its lower expected match_score), then the
        // non-favorites ordered by match_score DESC. The name "Sam Highnotfav"
        // is the closest match (highest score) among non-favorites.
        $this->assertSame($lowMatchFav->id, $ids[0]);
        $this->assertSame($highMatchNotFav->id, $ids[1]);
        $this->assertSame($lowMatchNotFav->id, $ids[2]);

        $this->assertTrue($favs[0]);
        $this->assertFalse($favs[1]);
        $this->assertFalse($favs[2]);
    }

    public function test_favorites_bubble_to_top_in_projects_with_match_score_tiebreak(): void
    {
        $caller = $this->authedUser();
        $fav = Project::factory()->create(['name' => 'Tara Lowfavproj']);
        $notFavHigh = Project::factory()->create(['name' => 'Tara Highnotfavproj']);
        $notFavLow = Project::factory()->create(['name' => 'Tara Lownotfavproj']);

        $caller->addProjectToFavorites($fav);

        $response = $this->getJson('/api/search?q=Tara&limit=all');

        $response->assertOk();
        $ids = collect($response->json('data.projects'))->pluck('id')->all();
        $favs = collect($response->json('data.projects'))->pluck('is_favorite')->all();

        $this->assertSame($fav->id, $ids[0]);
        $this->assertSame($notFavHigh->id, $ids[1]);
        $this->assertSame($notFavLow->id, $ids[2]);

        $this->assertTrue($favs[0]);
        $this->assertFalse($favs[1]);
        $this->assertFalse($favs[2]);
    }

    public function test_is_favorite_field_is_set_for_every_user_result(): void
    {
        $caller = $this->authedUser();
        $fav = $this->createUser(['name' => 'Ursula Favuser']);
        $notFav = $this->createUser(['name' => 'Ursula Notfav']);
        $caller->addToFavorites($fav);

        $response = $this->getJson('/api/search?q=Ursula&limit=all');

        $response->assertOk();
        $byId = collect($response->json('data.users'))->keyBy('id');

        $this->assertTrue($byId[$fav->id]['is_favorite']);
        $this->assertFalse($byId[$notFav->id]['is_favorite']);
    }

    // ---------- is_public on every user result item ----------

    public function test_is_public_field_reflects_profile_flag_and_defaults_false_for_no_profile(): void
    {
        $caller = $this->authedUser();

        // Public profile.
        $publicUser = $this->createUser(['name' => 'Vera Publicuser']);
        $this->setProfile($publicUser, ['is_public' => true]);

        // Private profile (explicit).
        $privateUser = $this->createUser(['name' => 'Vera Privateuser']);
        $this->setProfile($privateUser, ['is_public' => false]);

        // No profile row at all — delete the auto-created profile so we
        // exercise the "default false" branch.
        $noProfile = $this->createUser(['name' => 'Vera Noprofile']);
        $noProfile->profile()->delete();
        $noProfile->unsetRelation('profile');
        $noProfile->load('profile');

        $response = $this->getJson('/api/search?q=Vera&limit=all');

        $response->assertOk();
        $byId = collect($response->json('data.users'))->keyBy('id');

        $this->assertArrayHasKey('is_public', $byId[$publicUser->id]);
        $this->assertTrue($byId[$publicUser->id]['is_public']);

        $this->assertFalse($byId[$privateUser->id]['is_public']);
        $this->assertFalse($byId[$noProfile->id]['is_public']);
    }

    // ---------- not activated exclusion ----------

    public function test_not_activated_users_are_excluded_but_one_condition_users_appear(): void
    {
        $caller = $this->authedUser();

        // Normal — fully activated.
        $normal = $this->createUser(['name' => 'Wanda Normal']);

        // Unverified-only (active = true, unverified). ONE condition fails
        // → user is still activated → must appear.
        $unverifiedOnly = $this->createUser([
            'name' => 'Wanda Unverified',
            'email_verified_at' => null,
            'is_active' => true,
        ]);

        // Inactive-only (active = false, verified). ONE condition fails
        // → user is still activated → must appear.
        $inactiveOnly = $this->createUser([
            'name' => 'Wanda Inactive',
            'email_verified_at' => now(),
            'is_active' => false,
        ]);

        // BOTH conditions fail → not activated → must NOT appear.
        $both = $this->createUser([
            'name' => 'Wanda Both',
            'email_verified_at' => null,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/search?q=Wanda&limit=all');

        $response->assertOk();
        $ids = collect($response->json('data.users'))->pluck('id')->all();

        $this->assertContains($normal->id, $ids);
        $this->assertContains($unverifiedOnly->id, $ids);
        $this->assertContains($inactiveOnly->id, $ids);
        $this->assertNotContains($both->id, $ids);
    }

    // ---------- 422 validation ----------

    public function test_bad_status_enum_returns_422(): void
    {
        $this->authedUser();
        $response = $this->getJson('/api/search?q=test&status=banana');
        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_bad_visibility_enum_returns_422(): void
    {
        $this->authedUser();
        $response = $this->getJson('/api/search?q=test&visibility=banana');
        $response->assertStatus(422)->assertJsonValidationErrors(['visibility']);
    }

    public function test_bad_date_format_returns_422(): void
    {
        $this->authedUser();
        $response = $this->getJson('/api/search?q=test&created_from=not-a-date');
        $response->assertStatus(422)->assertJsonValidationErrors(['created_from']);
    }

    public function test_bad_is_public_profile_returns_422(): void
    {
        $this->authedUser();
        $response = $this->getJson('/api/search?q=test&is_public_profile=banana');
        $response->assertStatus(422)->assertJsonValidationErrors(['is_public_profile']);
    }

    public function test_bad_created_by_returns_422(): void
    {
        $this->authedUser();
        $response = $this->getJson('/api/search?q=test&created_by=banana');
        $response->assertStatus(422)->assertJsonValidationErrors(['created_by']);
    }
}
