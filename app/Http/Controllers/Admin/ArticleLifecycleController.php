<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleLifecycleController extends Controller
{
    public function submit(Article $article): RedirectResponse
    {
        Gate::authorize('submit', $article);

        // Validate current state
        if (!in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Rejected])) {
            return back()->with('error', 'Artikel hanya bisa diajukan dari status Draft atau Rejected.');
        }

        $wasRejected = $article->status === ArticleStatus::Rejected;

        $article->update(['status' => ArticleStatus::Submitted]);
        
        $logMessage = $wasRejected 
            ? 'Artikel diajukan ulang untuk review setelah revisi'
            : 'Artikel diajukan untuk review';
            
        activity()
            ->performedOn($article)
            ->causedBy(auth()->user())
            ->log($logMessage);

        $successMessage = $wasRejected
            ? 'Artikel berhasil diajukan ulang untuk review.'
            : 'Artikel berhasil diajukan untuk review.';

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', $successMessage);
    }


    public function approve(Article $article): RedirectResponse
    {
        Gate::authorize('approve', $article);

        // Validate current state
        if ($article->status !== ArticleStatus::Submitted) {
            return back()->with('error', 'Hanya artikel dengan status Submitted yang bisa disetujui.');
        }

        $article->update(['status' => ArticleStatus::Approved]);
        activity()
            ->performedOn($article)
            ->causedBy(auth()->user())
            ->log('Artikel disetujui untuk publikasi');

        return back()->with('success', 'Artikel berhasil disetujui.');
    }


    public function reject(Article $article, Request $request): RedirectResponse
    {
        Gate::authorize('reject', $article);

        // Validate current state
        if (!in_array($article->status, [ArticleStatus::Submitted, ArticleStatus::Approved])) {
            return back()->with('error', 'Hanya artikel dengan status Submitted atau Approved yang bisa ditolak.');
        }

        $reason = $request->input('reason', '');

        if (empty($reason)) {
            return back()->with('error', 'Alasan penolakan wajib diisi.');
        }

        $article->update(['status' => ArticleStatus::Rejected]);
        
        $article->revisions()->create([
            'editor_id' => auth()->id(),
            'content' => $reason,
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(auth()->user())
            ->log('Artikel ditolak: ' . $reason);

        return back()->with('success', 'Artikel ditolak dengan catatan revisi.');
    }


    public function publish(Article $article): RedirectResponse
    {
        Gate::authorize('publish', $article);

        // Validate current state
        if ($article->status !== ArticleStatus::Approved) {
            return back()->with('error', 'Hanya artikel dengan status Approved yang bisa dipublikasikan.');
        }

        $article->update([
            'status' => ArticleStatus::Published,
            'published_at' => now(),
            'scheduled_at' => null, // Clear schedule when manually published
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(auth()->user())
            ->log('Artikel dipublikasikan');

        return back()->with('success', 'Artikel berhasil dipublikasikan.');
    }


    public function schedule(Article $article, Request $request): RedirectResponse
    {
        Gate::authorize('schedule', $article);

        // Validate current state
        if ($article->status !== ArticleStatus::Approved) {
            return back()->with('error', 'Hanya artikel dengan status Approved yang bisa dijadwalkan.');
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        // Parse datetime-local format (Y-m-d\TH:i or Y-m-d H:i)
        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_at']);

        $article->update([
            'scheduled_at' => $scheduledAt,
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(auth()->user())
            ->log('Artikel dijadwalkan untuk publikasi: ' . $scheduledAt->format('d M Y H:i'));

        return back()->with('success', 'Artikel dijadwalkan untuk publikasi pada ' . $scheduledAt->format('d M Y, H:i') . ' WIB');
    }


    public function archive(Article $article): RedirectResponse
    {
        Gate::authorize('archive', $article);

        // Validate current state
        if ($article->status !== ArticleStatus::Published) {
            return back()->with('error', 'Hanya artikel dengan status Published yang bisa diarsipkan.');
        }

        $article->update([
            'status' => ArticleStatus::Archived,
            'archived_at' => now(),
        ]);

        activity()
            ->performedOn($article)
            ->causedBy(auth()->user())
            ->log('Artikel diarsipkan');

        return back()->with('success', 'Artikel berhasil diarsipkan.');
    }
}
