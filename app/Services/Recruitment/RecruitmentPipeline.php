<?php

namespace App\Services\Recruitment;

use App\Models\Jobs\JobPosting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The rules of the recruitment pipeline, enforced here (the Angular screens only mirror them):
 *
 *   who may act      the owner of the job, or a master administrator
 *   which moves      config('recruitment.transitions')
 *   rejections       need a written reason
 *   history          every change is recorded with who, when and why
 *   AI               is advisory only. Nothing in this class (or in the evaluation engine) changes a status by itself:
 *                    every approval or rejection is a person's explicit action.
 *   notifications    the applicant is told by chat and email; the recruiter can preview and edit the text first
 */
class RecruitmentPipeline
{
    public static function statuses(): array { return array_keys(config('recruitment.statuses')); }

    public static function label(string $status): string
    {
        return config('recruitment.statuses')[$status] ?? $status;
    }

    public static function canMove(string $from, string $to): bool
    {
        return in_array($to, config('recruitment.transitions')[$from] ?? [], true);
    }

    /** the application row + its job, or null */
    public static function load(int $appliedId): ?object
    {
        $app = DB::table('applied_jobs')->where('applied_id', $appliedId)->first();
        if (!$app) { return null; }
        $app->job = JobPosting::where('transNo', $app->transNo)->first();

        return $app;
    }

    /** only the job's owner or a master administrator may see or decide on its candidates */
    public static function mayManage($user, ?object $app): bool
    {
        if (!$user || !$app || !$app->job) { return false; }

        return (string) $app->job->code === (string) $user->code || $user->role_code === 'DEF-MASTERADMIN';
    }

    public static function mayManageJob($user, ?JobPosting $job): bool
    {
        return $user && $job && ((string) $job->code === (string) $user->code || $user->role_code === 'DEF-MASTERADMIN');
    }

    /**
     * Move an application to another status.
     * Returns [true, null] or [false, 'why not'].
     */
    public static function changeStatus(object $app, string $to, ?string $reason, $user, string $source = 'recruiter',
                                        bool $notify = false, ?string $message = null, ?array $interview = null): array
    {
        if (!in_array($to, self::statuses(), true)) {
            return [false, 'Unknown status.'];
        }
        $from = $app->applied_status ?: 'applied_active';
        if ($from === $to) {
            return [false, 'The candidate is already ' . strtolower(self::label($to)) . '.'];
        }
        if (!self::canMove($from, $to)) {
            return [false, 'A candidate can\'t move from ' . self::label($from) . ' to ' . self::label($to) . '.'];
        }

        $reason = $reason !== null ? trim($reason) : null;
        if ($to === 'reject' && ($reason === null || mb_strlen($reason) < 5)) {
            return [false, 'Please give a reason for rejecting this candidate (at least 5 characters).'];
        }

        // the AI recommendation at the time of the decision is kept for the audit trail (it never decides anything)
        $latest = DB::table('candidate_evaluations')->where('applied_id', $app->applied_id)->orderByDesc('id')->first();
        $snapshot = $latest ? json_encode([
            'evaluation_id' => $latest->id,
            'score' => $latest->final_score ?? $latest->overall_score,
            'recommendation' => $latest->recommendation,
            'provider' => $latest->provider,
        ]) : null;

        DB::transaction(function () use ($app, $from, $to, $reason, $user, $source, $snapshot, $notify) {
            DB::table('applied_jobs')->where('applied_id', $app->applied_id)->update(['applied_status' => $to, 'updated_at' => now()]);
            DB::table('application_status_history')->insert([
                'applied_id' => $app->applied_id, 'transNo' => $app->transNo, 'from_status' => $from, 'to_status' => $to,
                'reason' => $reason, 'source' => $source, 'changed_by' => $user->code ?? null,
                'changed_by_name' => $user->fullname ?? null, 'ai_snapshot' => $snapshot, 'notified' => $notify,
                'created_at' => now(),
            ]);
        });
        $app->applied_status = $to;

        if ($notify) {
            $event = ['review' => 'review', 'interview' => 'interview', 'approved' => 'approved', 'reject' => 'rejected', 'applied_active' => 'review'][$to];
            self::notify($app, $event, $user, $message, $interview ?? []);
        }

        return [true, null];
    }

    // ===================================================================== notifications

    public static function events(): array { return ['received', 'review', 'interview', 'approved', 'rejected']; }

    /** the default wording of a notification (the recruiter can edit it before it is sent) */
    public static function defaultMessage(object $app, string $event, array $extra = []): array
    {
        $name = trim(explode(' ', (string) $app->fullname)[0]) ?: 'there';
        $job = $app->job_name;
        $company = $app->job->company ?? null;
        $at = $company ? " at {$company}" : '';

        switch ($event) {
            case 'received':
                return ['subject' => "We received your application for {$job}",
                    'body' => "Hi {$name},\n\nThank you for applying for {$job}{$at}. We have received your application and will let you know as soon as it has been reviewed.\n\nGood luck!"];
            case 'review':
                return ['subject' => "Your application for {$job} is under review",
                    'body' => "Hi {$name},\n\nYour application for {$job}{$at} is now being reviewed. We will be in touch with the next steps."];
            case 'interview':
                $when = $extra['when'] ?? null;
                $how = ($extra['type'] ?? 'online') === 'online'
                    ? 'Online' . (!empty($extra['meeting_link']) ? ' (' . $extra['meeting_link'] . ')' : '')
                    : 'On-site' . (!empty($extra['location']) ? ' at ' . $extra['location'] : '');
                $detail = $when ? "\n\nDate and time: {$when}\nFormat: {$how}\nInterviewer: " . ($extra['interviewer'] ?? 'our hiring team') : '';
                return ['subject' => "Interview invitation: {$job}",
                    'body' => "Hi {$name},\n\nWe would like to invite you to an interview for {$job}{$at}.{$detail}\n\nPlease reply to this message if you need to change the time."];
            case 'approved':
                return ['subject' => "Good news about your application for {$job}",
                    'body' => "Hi {$name},\n\nCongratulations! Your application for {$job}{$at} has been approved. We will contact you shortly with the next steps."];
            case 'rejected':
                // the internal rejection reason is never included: it is a note for the hiring team
                return ['subject' => "Update on your application for {$job}",
                    'body' => "Hi {$name},\n\nThank you for your interest in {$job}{$at} and for the time you put into applying. After careful consideration we have decided not to move forward with your application. We wish you every success in your search."];
        }

        return ['subject' => "Update on your application for {$job}", 'body' => "Hi {$name},\n\nYour application status for {$job} was updated to " . self::label($app->applied_status) . '.'];
    }

    /** chat message + email to the applicant. Best effort: a mail server problem never undoes the decision. */
    public static function notify(object $app, string $event, $sender, ?string $custom = null, array $extra = []): bool
    {
        $text = self::defaultMessage($app, $event, $extra);
        if ($custom !== null && trim($custom) !== '') { $text['body'] = trim($custom); }
        $sent = false;

        try {
            $receiver = DB::table('users')->where('code', $app->code)->value('id');
            $from = $sender->id ?? ($app->job ? DB::table('users')->where('code', $app->job->code)->value('id') : null);
            if ($receiver && $from && (int) $receiver !== (int) $from) {
                \App\Models\Message::create([
                    'sender_id' => $from, 'receiver_id' => $receiver,
                    'message' => $text['subject'] . "\n\n" . $text['body'],
                    'created_at' => now(), 'code' => $sender->code ?? ($app->job->code ?? null), 'is_read' => false,
                ]);
                $sent = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Recruitment chat notification failed: ' . $e->getMessage());
        }

        try {
            if (!empty($app->email) && \App\Helpers\NotificationPrefs::allowsEmailForCode($app->code ?? null, 'email_application_updates')) {
                Mail::raw($text['body'], function ($m) use ($app, $text) {
                    $m->to($app->email)->subject($text['subject']);
                });
                $sent = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Recruitment email notification failed: ' . $e->getMessage());
        }

        return $sent;
    }
}
