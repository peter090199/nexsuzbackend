<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Settings > Resume / CV: a job seeker keeps up to 5 PDF resumes on their account and marks one as the default.
 * Fake private disk (no real files), rolled-back transactions.
 */
class UserResumeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeUser(string $role = 'DEF-USERS'): User
    {
        static $next = 940000;
        $code = $next += random_int(1, 40);

        return User::forceCreate([
            'fname' => 'Rey', 'lname' => 'Tester', 'fullname' => 'Rey Tester', 'role_code' => $role,
            'email' => "rey{$code}@example.test", 'password' => bcrypt('secret-pass'),
            'status' => 'A', 'code' => $code, 'coverphoto' => '',
        ]);
    }

    private function as(User $u): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::find($u->id));
    }

    private function pdf(string $name = 'cv.pdf', int $kb = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function upload(string $name = 'cv.pdf')
    {
        return $this->post('/api/user-resumes', ['resume' => $this->pdf($name)], ['Accept' => 'application/json']);
    }

    public function test_the_first_resume_becomes_the_default_and_a_later_one_does_not(): void
    {
        $this->as($this->makeUser());

        $a = $this->upload('first.pdf')->assertStatus(201)->json('data');
        $this->assertCount(1, $a);
        $this->assertTrue($a[0]['is_default']);

        $b = $this->upload('second.pdf')->assertStatus(201)->json('data');
        $this->assertCount(2, $b);
        $this->assertSame(['first.pdf'], array_column(array_filter($b, fn ($r) => $r['is_default']), 'name'));
    }

    public function test_the_file_is_stored_on_the_private_disk(): void
    {
        $u = $this->makeUser();
        $this->as($u);

        $this->upload()->assertStatus(201);

        $path = DB::table('user_resumes')->where('code', $u->code)->value('path');
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_setting_another_default_moves_the_flag(): void
    {
        $this->as($this->makeUser());
        $this->upload('first.pdf');
        $second = $this->upload('second.pdf')->json('id');

        $list = $this->patchJson("/api/user-resumes/{$second}/default")->assertOk()->json('data');

        $this->assertSame(['second.pdf'], array_column(array_filter($list, fn ($r) => $r['is_default']), 'name'));
    }

    public function test_deleting_the_default_promotes_another_and_removes_the_file(): void
    {
        $u = $this->makeUser();
        $this->as($u);
        $first = $this->upload('first.pdf')->json('id');
        $this->upload('second.pdf');
        $path = DB::table('user_resumes')->where('id', $first)->value('path');

        $list = $this->deleteJson("/api/user-resumes/{$first}")->assertOk()->json('data');

        $this->assertCount(1, $list);
        $this->assertTrue($list[0]['is_default'], 'the one left becomes the default');
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_the_last_resume_leaves_none(): void
    {
        $this->as($this->makeUser());
        $id = $this->upload()->json('id');

        $this->deleteJson("/api/user-resumes/{$id}")->assertOk();

        $this->assertSame([], $this->getJson('/api/user-resumes')->assertOk()->json('data'));
    }

    public function test_at_most_five_resumes(): void
    {
        $this->as($this->makeUser());
        for ($i = 1; $i <= 5; $i++) {
            $this->upload("r{$i}.pdf")->assertStatus(201);
        }

        $this->upload('r6.pdf')->assertStatus(422);

        $this->assertCount(5, $this->getJson('/api/user-resumes')->json('data'));
    }

    public function test_only_pdf_doc_and_docx_files_up_to_5mb_are_accepted(): void
    {
        $this->as($this->makeUser());

        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('photo.png', 5, 'image/png')], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('cv.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], ['Accept' => 'application/json'])->assertStatus(201);
        $this->post('/api/user-resumes', ['resume' => UploadedFile::fake()->create('old.doc', 50, 'application/msword')], ['Accept' => 'application/json'])->assertStatus(201);
        $this->post('/api/user-resumes', ['resume' => $this->pdf('big.pdf', 6000)], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/user-resumes', [], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_nobody_can_see_open_change_or_delete_someone_elses_resume(): void
    {
        $owner = $this->makeUser();
        $this->as($owner);
        $id = $this->upload('mine.pdf')->json('id');

        $other = $this->makeUser();
        $this->as($other);

        $this->assertSame([], $this->getJson('/api/user-resumes')->json('data'));
        $this->getJson("/api/user-resumes/{$id}/file")->assertStatus(404);
        $this->patchJson("/api/user-resumes/{$id}/default")->assertStatus(404);
        $this->deleteJson("/api/user-resumes/{$id}")->assertStatus(404);
        $this->assertSame(1, DB::table('user_resumes')->where('id', $id)->count());

        $this->as($owner);
        $this->get("/api/user-resumes/{$id}/file")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    #[DataProvider('otherRoles')]
    public function test_only_job_seekers_have_resumes(string $role): void
    {
        $this->as($this->makeUser($role));

        $this->getJson('/api/user-resumes')->assertStatus(403);
        $this->upload()->assertStatus(403);
    }

    public static function otherRoles(): array
    {
        return [['DEF-CLIENT'], ['DEF-ADMIN'], ['DEF-MASTERADMIN']];
    }

    public function test_it_needs_a_signed_in_user(): void
    {
        $this->getJson('/api/user-resumes')->assertStatus(401);
    }
}
