<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Services\ArticleNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ArticleLifecycleController extends Controller
{
    public function submit(Article $article): RedirectResponse
    {
        Gate::authorize('submit', $article);

        if (!in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Rejected])) {
            return back()->with('error', 'Artikel hanya bisa diajukan dari status Draft atau Rejected.');
        }

        $wasRejected = $article->status === ArticleStatus::Rejected;

        $article->update(['status' => ArticleStatus::Submitted]);

        activity()
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->log($wasRejected
                ? 'Artikel diajukan ulang untuk review setelah revisi'
                : 'Artikel diajukan untuk review');

        ArticleNotifier::notify($article, $wasRejected ? 'resubmitted' : 'submitted', Auth::user());

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', $wasRejected
                ? 'Artikel berhasil diajukan ulang untuk review.'
                : 'Artikel berhasil diajukan untuk review.');
    }

    public function approve(Article $article): RedirectResponse
    {
        Gate::authorize('approve', $article);

        if ($article->status !== ArticleStatus::Submitted) {
            return back()->with('error', 'Hanya artikel dengan status Submitted yang bisa disetujui.');
        }

        $article->update(['status' => ArticleStatus::Approved]);

        activity()
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->log('Artikel disetujui untuk publikasi');

        ArticleNotifier::notify($article, 'approved', Auth::user());

        return back()->with('success', 'Artikel berhasil disetujui.');
    }

    public function reject(Article $article, Request $request): RedirectResponse
    {
        Gate::authorize('reject', $article);

        if (!in_array($article->status, [ArticleStatus::Submitted, ArticleStatus::Approved])) {
            return back()->with('error', 'Hanya artikel dengan status Submitted atau Approved yang bisa ditolak.');
        }

        $reason = trim((string) $request->input('reason', ''));

        if ($reason === '') {
            return back()->with('error', 'Alasan penolakan wajib diisi.');
        }

        $article->update(['status' => ArticleStatus::Rejected, 'scheduled_at' => null]);

        $article->revisions()->create([
            'editor_id' => Auth::id(),
            'content' => $reason,
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->log('Artikel ditolak: ' . $reason);

        ArticleNotifier::notify($article, 'rejected', Auth::user(), $reason);

        return back()->with('success', 'Artikel ditolak dengan catatan revisi.');
    }

    public function publish(Article $article): RedirectResponse
    {
        Gate::authorize('publish', $article);

        if ($article->status !== ArticleStatus::Approved) {
            return back()->with('error', 'Hanya artikel dengan status Approved yang bisa dipublikasikan.');
        }

        $article->update([
            'status' => ArticleStatus::Published,
            'published_at' => now(),
            'scheduled_at' => null,
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->log('Artikel dipublikasikan');

        ArticleNotifier::notify($article, 'published', Auth::user());

        return back()->with('success', 'Artikel berhasil dipublikasikan.');
    }

    public function schedule(Article $article, Request $request): RedirectResponse
    {
        Gate::authorize('schedule', $article);

        if ($article->status !== ArticleStatus::Approved) {
            return back()->with('error', 'Hanya artikel dengan status Approved yang bisa dijadwalkan.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_at']);

        $article->update(['scheduled_at' => $scheduledAt]);

        activity()
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->log('Artikel dijadwalkan untuk publikasi: ' . $scheduledAt->format('d M Y H:i'));

        ArticleNotifier::notify($article, 'scheduled', Auth::user(), $scheduledAt->format('d M Y, H:i'));

        return back()->with('success', 'Artikel dijadwalkan untuk publikasi pada ' . $scheduledAt->format('d M Y, H:i') . ' WIB');
    }

    public function archive(Article $article): RedirectResponse
    {
        Gate::authorize('archive', $article);

        if ($article->status !== ArticleStatus::Published) {
            return back()->with('error', 'Hanya artikel dengan status Published yang bisa diarsipkan.');
        }

        $article->update([
            'status' => ArticleStatus::Archived,
            'archived_at' => now(),
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(Auth::user())
            ->log('Artikel diarsipkan');

        ArticleNotifier::notify($article, 'archived', Auth::user());

        return back()->with('success', 'Artikel berhasil diarsipkan.');
    }
}