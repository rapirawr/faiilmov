<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\FilmRequest;
use App\Services\FilmRequestService;
use Illuminate\Http\Request;

class AdminFilmRequestController extends Controller
{
    public function __construct(
        private FilmRequestService $requestService
    ) {}

    /**
     * Display list of film requests with filters & sorting.
     */
    public function index(Request $request)
    {
        $query = FilmRequest::with(['users', 'matchedFilm']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default filter to pending/searching requests first
            if (!$request->has('all')) {
                $query->whereIn('status', ['pending', 'searching']);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $sort = $request->get('sort', 'popularity');
        if ($sort === 'latest') {
            $query->orderByDesc('created_at');
        } else {
            $query->sortedByPopularity();
        }

        $filmRequests = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => FilmRequest::count(),
            'pending' => FilmRequest::where('status', 'pending')->count(),
            'searching' => FilmRequest::where('status', 'searching')->count(),
            'added' => FilmRequest::where('status', 'added')->count(),
            'rejected' => FilmRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.film-requests.index', compact('filmRequests', 'stats'));
    }

    /**
     * Manual trigger search & resolve via API.
     */
    public function resolve(FilmRequest $filmRequest)
    {
        $success = $this->requestService->tryAutoResolve($filmRequest);

        if ($success) {
            $this->requestService->notifyRequesters($filmRequest);
            AdminActivityLog::log('resolved_film_request', "Berhasil mengimpor film untuk request '{$filmRequest->title}'", 'FilmRequest', $filmRequest->id);
            return back()->with('success', "Film untuk request '{$filmRequest->title}' berhasil ditemukan & diimpor! Pemohon telah dinotifikasi.");
        }

        AdminActivityLog::log('failed_resolve_film_request', "Gagal menemukan film otomatis untuk request '{$filmRequest->title}'", 'FilmRequest', $filmRequest->id);
        return back()->with('error', "Tidak dapat menemukan film '{$filmRequest->title}' di API MovieBox / Anichin secara otomatis. Anda dapat menambahkannya secara manual.");
    }

    /**
     * Reject a film request with reason.
     */
    public function reject(Request $request, FilmRequest $filmRequest)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $filmRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason ?: 'Maaf, film yang kamu minta belum dapat tersedia di platform.',
        ]);

        $this->requestService->notifyRequesters($filmRequest);

        AdminActivityLog::log('rejected_film_request', "Menolak request film '{$filmRequest->title}'", 'FilmRequest', $filmRequest->id);

        return back()->with('success', "Request film '{$filmRequest->title}' telah ditolak dan pemohon telah dinotifikasi.");
    }

    /**
     * Update request status directly (pending, searching, added, rejected).
     */
    public function updateStatus(Request $request, FilmRequest $filmRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,searching,added,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $oldStatus = $filmRequest->status;
        $newStatus = $request->status;

        $updateData = [
            'status' => $newStatus,
        ];

        if ($newStatus === 'rejected') {
            $updateData['rejection_reason'] = $request->rejection_reason ?: 'Maaf, film yang kamu minta belum dapat tersedia di platform.';
        } elseif ($oldStatus === 'rejected' && $newStatus !== 'rejected') {
            $updateData['rejection_reason'] = null;
        }

        $filmRequest->update($updateData);

        // Notify requesters if status changed to added or rejected
        if ($newStatus !== $oldStatus && in_array($newStatus, ['added', 'rejected'])) {
            $this->requestService->notifyRequesters($filmRequest);
        }

        $statusLabels = [
            'pending' => 'Pending',
            'searching' => 'Sedang Dicari',
            'added' => 'Ditemukan / Selesai',
            'rejected' => 'Ditolak',
        ];
        $label = $statusLabels[$newStatus] ?? ucfirst($newStatus);

        AdminActivityLog::log(
            'updated_film_request_status',
            "Mengubah status request film '{$filmRequest->title}' dari {$oldStatus} menjadi {$newStatus}",
            'FilmRequest',
            $filmRequest->id
        );

        return back()->with('success', "Status request '{$filmRequest->title}' berhasil diubah menjadi: {$label}.");
    }

    /**
     * Bulk reject selected requests.
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:film_requests,id',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $reason = $request->rejection_reason ?: 'Maaf, film yang kamu minta belum dapat tersedia di platform.';
        $requests = FilmRequest::whereIn('id', $request->ids)->get();

        $count = 0;
        foreach ($requests as $filmReq) {
            $filmReq->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);
            $this->requestService->notifyRequesters($filmReq);
            $count++;
        }

        AdminActivityLog::log('bulk_rejected_film_requests', "Menolak massal {$count} request film.", 'FilmRequest');

        return back()->with('success', "Berhasil menolak {$count} request film.");
    }
}
