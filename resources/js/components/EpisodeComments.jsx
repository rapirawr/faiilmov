import React, { useState, useEffect, useRef } from 'react';
import { Spoiler } from 'spoiled';
import { MorphIcon } from 'morphicons/react';
import { 
  Reply as ReplyIconData, 
  X as XIconData, 
  Eye as EyeIconData, 
  EyeOff as EyeOffIconData 
} from 'lucide';
import { 
  MessageSquare, 
  Send, 
  Timer, 
  AlertTriangle, 
  Heart, 
  Reply, 
  Trash2, 
  Flag, 
  LogIn, 
  X, 
  CheckCircle2, 
  Loader2 
} from 'lucide-react'; 
/**
 * Component to render comment content with interactive timestamp buttons.
 * When the comment is a spoiler, the entire box is covered in dust.
 * Clicking on the spoiler (even on the timestamp) only reveals the spoiler.
 * Once revealed, the timestamp buttons become active for video seeking.
 */
function CommentContentRenderer({ text, isSpoiler, onSeek }) {
  const [isRevealed, setIsRevealed] = useState(false);

  if (!text) return null;

  // Regex to match timestamp tokens (HH:MM:SS or MM:SS)
  const regex = /\b(?:(\d{1,2}):)?([0-5]?\d):([0-5]\d)\b/g;
  const parts = [];
  let lastIndex = 0;
  let match;

  while ((match = regex.exec(text)) !== null) {
    if (match.index > lastIndex) {
      parts.push(text.substring(lastIndex, match.index));
    }

    const rawMatch = match[0];
    const h = match[1];
    const m = match[2];
    const s = match[3];
    const seconds = h ? parseInt(h, 10) * 3600 + parseInt(m, 10) * 60 + parseInt(s, 10) : parseInt(m, 10) * 60 + parseInt(s, 10);

    parts.push(
      <button
        key={`ts-${match.index}`}
        type="button"
        onClick={(e) => {
          // If this comment is a hidden spoiler, ignore seek and let the spoiler reveal itself!
          if (isSpoiler && !isRevealed) {
            return;
          }
          e.stopPropagation();
          onSeek(seconds);
        }}
        className={`inline-flex items-center gap-1.5 px-2 py-0.5 mx-1 rounded-lg bg-amber-500/20 text-amber-300 hover:bg-amber-400 hover:text-zinc-950 text-[11px] font-mono font-bold border border-amber-500/30 transition-all cursor-pointer shadow-sm align-middle group ${
          isSpoiler && !isRevealed ? 'pointer-events-none select-none' : ''
        }`}
      >
        <Timer className="w-3 h-3 text-amber-400 group-hover:text-zinc-950 transition-colors" />
        <span>{rawMatch}</span>
      </button>
    );

    lastIndex = regex.lastIndex;
  }

  if (lastIndex < text.length) {
    parts.push(text.substring(lastIndex));
  }

  if (isSpoiler) {
    return (
      <div className="inline-block max-w-full">
        <Spoiler
          revealOn="click"
          theme="dark"
          density={0.16}
          fps={24}
          onHiddenChange={(hidden) => setIsRevealed(!hidden)}
          className={`cursor-pointer text-xs text-zinc-200 leading-relaxed inline-block max-w-full rounded-md select-none break-words whitespace-pre-wrap px-1 py-0.5 ${
            !isRevealed ? '[&_*]:pointer-events-none' : ''
          }`}
        >
          {parts}
        </Spoiler>
      </div>
    );
  }

  return (
    <p className="text-xs text-zinc-200 leading-relaxed break-words whitespace-pre-wrap">
      {parts}
    </p>
  );
}

export default function EpisodeComments({
  filmId,
  initialSeason = 1,
  initialEpisode = 1,
  isLoggedIn = false,
  userName = '',
  loginUrl = '/login',
  csrfToken = '',
}) {
  const [season, setSeason] = useState(initialSeason);
  const [episode, setEpisode] = useState(initialEpisode);
  const [comments, setComments] = useState([]);
  const [totalCount, setTotalCount] = useState(0);
  const [isLoading, setIsLoading] = useState(true);

  // New comment input form
  const [newCommentText, setNewCommentText] = useState('');
  const [isSpoiler, setIsSpoiler] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Reply form
  const [replyingTo, setReplyingTo] = useState(null);
  const [replyText, setReplyText] = useState('');
  const [replyIsSpoiler, setReplyIsSpoiler] = useState(false);
  const [isSubmittingReply, setIsSubmittingReply] = useState(false);

  // Modals
  const [reportModalOpen, setReportModalOpen] = useState(false);
  const [reportingCommentId, setReportingCommentId] = useState(null);
  const [reportReason, setReportReason] = useState('');
  const [isSubmittingReport, setIsSubmittingReport] = useState(false);

  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [deletingCommentId, setDeletingCommentId] = useState(null);
  const [isDeleting, setIsDeleting] = useState(false);

  // Toast
  const [toastMessage, setToastMessage] = useState('');
  const [showToast, setShowToast] = useState(false);
  const toastTimeoutRef = useRef(null);

  const triggerToast = (msg) => {
    setToastMessage(msg);
    setShowToast(true);
    if (toastTimeoutRef.current) clearTimeout(toastTimeoutRef.current);
    toastTimeoutRef.current = setTimeout(() => {
      setShowToast(false);
    }, 3000);
  };

  // Fetch comments
  const fetchComments = async (s, ep) => {
    setIsLoading(true);
    try {
      const res = await fetch(`/api/series/comments?film_id=${filmId}&season=${s}&episode=${ep}`, {
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const data = await res.json();
        setComments(data.comments || []);
        setTotalCount(data.total || 0);
      }
    } catch (e) {
      console.error('Fetch comments error:', e);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchComments(season, episode);

    const handleEpisodeChanged = (e) => {
      if (e.detail && e.detail.season && e.detail.episode) {
        setSeason(e.detail.season);
        setEpisode(e.detail.episode);
        setReplyingTo(null);
        fetchComments(e.detail.season, e.detail.episode);
      }
    };

    window.addEventListener('episode-changed', handleEpisodeChanged);
    return () => {
      window.removeEventListener('episode-changed', handleEpisodeChanged);
    };
  }, [filmId]);

  // Insert current video timestamp into input
  const insertTimestamp = (isReply = false) => {
    const video = document.querySelector('video');
    if (!video) return;
    const currentSec = Math.floor(video.currentTime || 0);
    const hrs = Math.floor(currentSec / 3600);
    const mins = Math.floor((currentSec % 3600) / 60);
    const secs = currentSec % 60;

    let timeStr = '';
    if (hrs > 0) {
      timeStr = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    } else {
      timeStr = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    if (isReply) {
      setReplyText((prev) => (prev ? prev + ' ' : '') + timeStr + ' ');
    } else {
      setNewCommentText((prev) => (prev ? prev + ' ' : '') + timeStr + ' ');
    }
  };

  // Seek video to timestamp
  const handleSeek = (timeInSeconds) => {
    window.dispatchEvent(new CustomEvent('seek-video', { detail: { time: timeInSeconds } }));
  };

  // Submit top-level comment
  const handleSubmitComment = async () => {
    if (!newCommentText.trim() || isSubmitting) return;
    setIsSubmitting(true);
    try {
      const res = await fetch('/api/series/comments', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          film_id: filmId,
          season_number: season,
          episode_number: episode,
          comment: newCommentText.trim(),
          is_spoiler: isSpoiler,
        }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setComments((prev) => [data.comment, ...prev]);
        setTotalCount((prev) => prev + 1);
        setNewCommentText('');
        setIsSpoiler(false);
        triggerToast('Komentar berhasil dikirim!');
      } else {
        triggerToast(data.message || 'Gagal mengirim komentar.');
      }
    } catch (e) {
      console.error('Submit comment error:', e);
      triggerToast('Terjadi kesalahan jaringan.');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Submit reply
  const handleSubmitReply = async (parentId) => {
    if (!replyText.trim() || isSubmittingReply) return;
    setIsSubmittingReply(true);
    try {
      const res = await fetch('/api/series/comments', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          film_id: filmId,
          season_number: season,
          episode_number: episode,
          parent_id: parentId,
          comment: replyText.trim(),
          is_spoiler: replyIsSpoiler,
        }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setComments((prev) =>
          prev.map((c) => {
            if (c.id === parentId) {
              return {
                ...c,
                replies: [...(c.replies || []), data.comment],
              };
            }
            return c;
          })
        );
        setTotalCount((prev) => prev + 1);
        setReplyText('');
        setReplyIsSpoiler(false);
        setReplyingTo(null);
        triggerToast('Balasan berhasil dikirim!');
      } else {
        triggerToast(data.message || 'Gagal mengirim balasan.');
      }
    } catch (e) {
      console.error('Submit reply error:', e);
      triggerToast('Terjadi kesalahan jaringan.');
    } finally {
      setIsSubmittingReply(false);
    }
  };

  // Toggle like
  const handleToggleLike = async (comment) => {
    if (!isLoggedIn) {
      window.location.href = loginUrl;
      return;
    }

    const prevLiked = comment.is_liked;
    const prevCount = comment.likes_count;

    // Optimistic update
    const updateLikesInState = (liked, count) => {
      setComments((prev) =>
        prev.map((c) => {
          if (c.id === comment.id) {
            return { ...c, is_liked: liked, likes_count: count };
          }
          if (c.replies) {
            return {
              ...c,
              replies: c.replies.map((r) => (r.id === comment.id ? { ...r, is_liked: liked, likes_count: count } : r)),
            };
          }
          return c;
        })
      );
    };

    updateLikesInState(!prevLiked, prevLiked ? Math.max(0, prevCount - 1) : prevCount + 1);

    try {
      const res = await fetch(`/api/series/comments/${comment.id}/like`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
      });
      if (res.ok) {
        const data = await res.json();
        updateLikesInState(data.liked, data.likes_count);
      } else {
        updateLikesInState(prevLiked, prevCount);
      }
    } catch (e) {
      updateLikesInState(prevLiked, prevCount);
    }
  };

  // Submit report
  const handleSubmitReport = async () => {
    if (!reportReason.trim() || isSubmittingReport) return;
    setIsSubmittingReport(true);
    try {
      const res = await fetch(`/api/series/comments/${reportingCommentId}/report`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ reason: reportReason.trim() }),
      });
      const data = await res.json();
      triggerToast(data.message || 'Laporan terkirim.');
      setReportModalOpen(false);
    } catch (e) {
      triggerToast('Gagal mengirim laporan.');
    } finally {
      setIsSubmittingReport(false);
    }
  };

  // Confirm delete
  const handleConfirmDelete = async () => {
    if (!deletingCommentId || isDeleting) return;
    setIsDeleting(true);
    try {
      const res = await fetch(`/api/series/comments/${deletingCommentId}`, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
      });
      const data = await res.json();
      if (res.ok && data.success) {
        let removedCount = 1;
        setComments((prev) => {
          const topComment = prev.find((c) => c.id === deletingCommentId);
          if (topComment) {
            removedCount += (topComment.replies || []).length;
            return prev.filter((c) => c.id !== deletingCommentId);
          }
          return prev.map((c) => {
            if (c.replies) {
              return {
                ...c,
                replies: c.replies.filter((r) => r.id !== deletingCommentId),
              };
            }
            return c;
          });
        });
        setTotalCount((prev) => Math.max(0, prev - removedCount));
        triggerToast('Komentar berhasil dihapus.');
        setDeleteModalOpen(false);
      } else {
        triggerToast(data.message || 'Gagal menghapus komentar.');
      }
    } catch (e) {
      triggerToast('Terjadi kesalahan.');
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="glass-panel p-6 sm:p-8 rounded-3xl border border-white/10 shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden">
      {/* Toast Notification */}
      {showToast && (
        <div className="fixed bottom-8 left-1/2 -translate-x-1/2 z-[100] px-4 py-2.5 rounded-2xl bg-zinc-900/95 border border-amber-500/40 text-amber-300 text-xs font-bold shadow-2xl backdrop-blur-xl flex items-center gap-2 pointer-events-none animate-in fade-in slide-in-from-bottom-2 duration-200">
          <CheckCircle2 className="w-4 h-4 text-amber-400" />
          <span>{toastMessage}</span>
        </div>
      )}

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/10 pb-5">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500/20 to-rose-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-md">
            <MessageSquare className="w-5 h-5" />
          </div>
          <div>
            <h3 className="font-serif font-bold text-xl text-white">Diskusi & Komentar</h3>
            <p className="text-xs text-zinc-400 mt-0.5">
              Membahas <strong className="text-amber-300">Season {season} Episode {episode}</strong>
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <span className="px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-xs font-bold text-zinc-300 flex items-center gap-1.5">
            <span>{totalCount} Komentar</span>
          </span>
        </div>
      </div>

      {/* Input Comment Form */}
      {isLoggedIn ? (
        <div className="glass-card p-5 rounded-2xl border border-white/10 space-y-4">
          <div className="flex items-start gap-3">
            <div className="w-8 h-8 rounded-xl bg-white text-zinc-950 flex items-center justify-center text-xs font-extrabold shrink-0 shadow">
              {(userName || 'U').substring(0, 2).toUpperCase()}
            </div>
            <div className="flex-1 min-w-0">
              <textarea
                value={newCommentText}
                onChange={(e) => setNewCommentText(e.target.value)}
                rows={3}
                placeholder={`Tulis tanggapan atau teori kamu untuk Season ${season} Episode ${episode}... (Gunakan format 02:45 untuk timestamp)`}
                className="w-full bg-dark-950/70 text-xs text-white p-3.5 rounded-2xl border border-white/10 focus:outline-none focus:border-amber-400/50 transition-colors placeholder-zinc-500 leading-relaxed"
              />
            </div>
          </div>

          <div className="flex flex-wrap items-center justify-between gap-3 pt-1 border-t border-white/5">
            <div className="flex items-center gap-2 flex-wrap">
              <button
                type="button"
                onClick={() => insertTimestamp(false)}
                className="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/15 text-zinc-300 hover:text-white border border-white/10 text-[11px] font-semibold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm"
                title="Sisipkan posisi menit video saat ini ke dalam komentar"
              >
                <Timer className="w-3.5 h-3.5 text-amber-400" />
                <span>Sisipkan Menit Video</span>
              </button>

              <label
                className={`px-3 py-1.5 rounded-xl text-[11px] font-semibold border transition-all flex items-center gap-1.5 cursor-pointer shadow-sm select-none ${
                  isSpoiler ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-bold' : 'bg-white/5 text-zinc-400 border-white/10 hover:text-white'
                }`}
              >
                <input
                  type="checkbox"
                  checked={isSpoiler}
                  onChange={(e) => setIsSpoiler(e.target.checked)}
                  className="hidden"
                />
                <MorphIcon icon={isSpoiler ? EyeOffIconData : EyeIconData} size={14} strokeWidth={2} className={isSpoiler ? 'text-rose-400' : 'text-zinc-500'} />
                <span>Mengandung Spoiler</span>
              </label>
            </div>

            <button
              type="button"
              onClick={handleSubmitComment}
              disabled={!newCommentText.trim() || isSubmitting}
              className="px-5 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-all flex items-center gap-2 shadow-md cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
            >
              {isSubmitting ? (
                <Loader2 className="w-3.5 h-3.5 animate-spin" />
              ) : (
                <Send className="w-3.5 h-3.5" />
              )}
              <span>Kirim Komentar</span>
            </button>
          </div>
        </div>
      ) : (
        <div className="glass-card p-5 rounded-2xl border border-white/10 text-center space-y-2">
          <p className="text-xs text-zinc-300">Ingin ikut berdiskusi dan berbagi komentar untuk episode ini?</p>
          <a
            href={loginUrl}
            className="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-white hover:bg-zinc-200 text-zinc-950 text-xs font-bold transition-colors shadow"
          >
            <LogIn className="w-3.5 h-3.5" />
            <span>Masuk ke Akun Anda</span>
          </a>
        </div>
      )}

      {/* Loading State */}
      {isLoading && (
        <div className="py-12 flex flex-col items-center justify-center gap-3">
          <div className="w-8 h-8 rounded-full border-2 border-amber-400 border-t-transparent animate-spin" />
          <span className="text-xs text-zinc-400">Memuat komentar episode...</span>
        </div>
      )}

      {/* Empty State */}
      {!isLoading && comments.length === 0 && (
        <div className="py-10 text-center space-y-2">
          <div className="w-12 h-12 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-zinc-500 mx-auto">
            <MessageSquare className="w-6 h-6" />
          </div>
          <h4 className="text-sm font-bold text-white">Belum Ada Komentar di Episode Ini</h4>
          <p className="text-xs text-zinc-400 max-w-sm mx-auto">
            Jadilah orang pertama yang menulis ulasan, reaksi, atau teori tentang episode ini!
          </p>
        </div>
      )}

      {/* Comments List */}
      {!isLoading && comments.length > 0 && (
        <div className="space-y-4">
          {comments.map((comment) => (
            <div key={comment.id} className="glass-card p-4 sm:p-5 rounded-2xl border border-white/10 space-y-3 transition-all hover:border-white/20">
              {/* Comment Header */}
              <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-2.5">
                  <div className="w-7 h-7 rounded-lg bg-zinc-800 border border-white/10 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                    <span>{comment.user?.initial || 'U'}</span>
                  </div>
                  <div>
                    <span className="text-xs font-bold text-white block">{comment.user?.name}</span>
                    <span className="text-[10px] text-zinc-500 block">{comment.created_at}</span>
                  </div>
                </div>

                <div className="flex items-center gap-1.5">
                  {comment.is_spoiler && (
                    <span className="px-2 py-0.5 rounded-md bg-zinc-800/80 text-zinc-300 border border-white/10 text-[9px] font-extrabold uppercase tracking-wider">
                      Spoiler
                    </span>
                  )}
                </div>
              </div>

              {/* Comment Body with Spoiled Component */}
              <div>
                <CommentContentRenderer
                  key={`c-${comment.id}-${comment.is_spoiler}`}
                  text={comment.comment}
                  isSpoiler={comment.is_spoiler}
                  onSeek={handleSeek}
                />
              </div>

              {/* Actions Bar */}
              <div className="flex items-center justify-between pt-2 border-t border-white/5 text-xs text-zinc-400">
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    onClick={() => handleToggleLike(comment)}
                    className={`flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer ${
                      comment.is_liked ? 'text-rose-400 font-bold' : 'text-zinc-400'
                    }`}
                  >
                    <Heart className={`w-3.5 h-3.5 ${comment.is_liked ? 'fill-rose-400 text-rose-400' : ''}`} />
                    <span>{comment.likes_count > 0 ? comment.likes_count : 'Suka'}</span>
                  </button>

                  {isLoggedIn && (
                    <button
                      type="button"
                      onClick={() => {
                        setReplyingTo(replyingTo === comment.id ? null : comment.id);
                        setReplyText('');
                        setReplyIsSpoiler(false);
                      }}
                      className={`flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer ${
                        replyingTo === comment.id ? 'text-amber-300 font-bold' : ''
                      }`}
                    >
                      <MorphIcon icon={replyingTo === comment.id ? XIconData : ReplyIconData} size={14} strokeWidth={2} />
                      <span>{replyingTo === comment.id ? 'Batal' : 'Balas'}</span>
                    </button>
                  )}
                </div>

                <div className="flex items-center gap-2">
                  {comment.can_delete && (
                    <button
                      type="button"
                      onClick={() => {
                        setDeletingCommentId(comment.id);
                        setDeleteModalOpen(true);
                      }}
                      className="p-1 rounded-lg hover:bg-rose-500/20 text-zinc-500 hover:text-rose-400 transition-colors cursor-pointer"
                      title="Hapus Komentar"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  )}

                  {isLoggedIn && (
                    <button
                      type="button"
                      onClick={() => {
                        setReportingCommentId(comment.id);
                        setReportReason('');
                        setReportModalOpen(true);
                      }}
                      className="p-1 rounded-lg hover:bg-white/10 text-zinc-500 hover:text-zinc-300 transition-colors cursor-pointer"
                      title="Laporkan Komentar"
                    >
                      <Flag className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              </div>

              {/* Inline Reply Form */}
              {replyingTo === comment.id && (
                <div className="mt-3 p-3.5 rounded-xl bg-zinc-950/60 border border-amber-500/30 space-y-3 animate-in fade-in duration-200">
                  <div className="flex items-center justify-between text-[11px] text-zinc-400">
                    <span>
                      Membalas <strong className="text-white">{comment.user?.name}</strong>
                    </span>
                    <button type="button" onClick={() => setReplyingTo(null)} className="text-zinc-500 hover:text-white cursor-pointer">
                      <X className="w-3.5 h-3.5" />
                    </button>
                  </div>

                  <textarea
                    value={replyText}
                    onChange={(e) => setReplyText(e.target.value)}
                    rows={2}
                    placeholder="Tulis balasan kamu..."
                    className="w-full bg-dark-950 text-xs text-white p-2.5 rounded-xl border border-white/10 focus:outline-none focus:border-amber-400/50 leading-relaxed"
                  />

                  <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => insertTimestamp(true)}
                        className="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-[10px] text-zinc-300 flex items-center gap-1 border border-white/10 cursor-pointer"
                      >
                        <Timer className="w-3 h-3 text-amber-400" />
                        <span>Menit Video</span>
                      </button>

                      <label
                        className={`px-2.5 py-1 rounded-lg text-[10px] border flex items-center gap-1 cursor-pointer transition-all ${
                          replyIsSpoiler ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-bold' : 'bg-white/5 text-zinc-400 border-white/10'
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={replyIsSpoiler}
                          onChange={(e) => setReplyIsSpoiler(e.target.checked)}
                          className="hidden"
                        />
                        <MorphIcon icon={replyIsSpoiler ? EyeOffIconData : EyeIconData} size={12} strokeWidth={2} className={replyIsSpoiler ? 'text-rose-400' : 'text-zinc-500'} />
                        <span>Spoiler</span>
                      </label>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => setReplyingTo(null)}
                        className="px-3 py-1 rounded-lg bg-white/5 hover:bg-white/10 text-zinc-400 text-xs cursor-pointer"
                      >
                        Batal
                      </button>
                      <button
                        type="button"
                        onClick={() => handleSubmitReply(comment.id)}
                        disabled={!replyText.trim() || isSubmittingReply}
                        className="px-3.5 py-1 rounded-lg bg-white hover:bg-zinc-200 text-zinc-950 font-bold text-xs transition-colors cursor-pointer disabled:opacity-40"
                      >
                        {isSubmittingReply ? 'Mengirim...' : 'Kirim'}
                      </button>
                    </div>
                  </div>
                </div>
              )}

              {/* Nested Replies */}
              {comment.replies && comment.replies.length > 0 && (
                <div className="pl-4 sm:pl-6 border-l-2 border-amber-500/30 space-y-3 pt-2">
                  {comment.replies.map((reply) => (
                    <div key={reply.id} className="p-3 rounded-xl bg-white/5 border border-white/5 space-y-2">
                      <div className="flex items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                          <div className="w-5 h-5 rounded-md bg-zinc-800 text-white flex items-center justify-center text-[9px] font-bold">
                            <span>{reply.user?.initial || 'U'}</span>
                          </div>
                          <span className="text-xs font-bold text-white">{reply.user?.name}</span>
                          <span className="text-[10px] text-zinc-500">{reply.created_at}</span>
                        </div>

                        {reply.is_spoiler && (
                          <span className="px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-300 text-[8px] font-extrabold uppercase border border-white/10">
                            Spoiler
                          </span>
                        )}
                      </div>

                      <div>
                        <CommentContentRenderer
                          key={`r-${reply.id}-${reply.is_spoiler}`}
                          text={reply.comment}
                          isSpoiler={reply.is_spoiler}
                          onSeek={handleSeek}
                        />
                      </div>

                      <div className="flex items-center justify-between text-[11px] text-zinc-400 pt-1">
                        <button
                          type="button"
                          onClick={() => handleToggleLike(reply)}
                          className={`flex items-center gap-1 hover:text-white cursor-pointer ${
                            reply.is_liked ? 'text-rose-400 font-bold' : ''
                          }`}
                        >
                          <Heart className={`w-3 h-3 ${reply.is_liked ? 'fill-rose-400 text-rose-400' : ''}`} />
                          <span>{reply.likes_count > 0 ? reply.likes_count : ''}</span>
                        </button>

                        <div className="flex items-center gap-1.5">
                          {reply.can_delete && (
                            <button
                              type="button"
                              onClick={() => {
                                setDeletingCommentId(reply.id);
                                setDeleteModalOpen(true);
                              }}
                              className="hover:text-rose-400 cursor-pointer"
                              title="Hapus"
                            >
                              <Trash2 className="w-3 h-3" />
                            </button>
                          )}
                          {isLoggedIn && (
                            <button
                              type="button"
                              onClick={() => {
                                setReportingCommentId(reply.id);
                                setReportReason('');
                                setReportModalOpen(true);
                              }}
                              className="hover:text-zinc-300 cursor-pointer"
                              title="Lapor"
                            >
                              <Flag className="w-3 h-3" />
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Report Modal */}
      {reportModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="w-full max-w-md p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
            <div className="flex items-center gap-3 text-amber-400 border-b border-zinc-800 pb-3">
              <div className="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                <Flag className="w-4 h-4" />
              </div>
              <h4 className="font-bold text-white text-sm">Laporkan Komentar Ini</h4>
            </div>
            <p className="text-xs text-zinc-300">
              Mohon jelaskan alasan mengapa komentar ini perlu ditinjau (misal: spoiler tanpa tanda, spam, ujaran kasar):
            </p>
            <textarea
              value={reportReason}
              onChange={(e) => setReportReason(e.target.value)}
              rows={3}
              placeholder="Tulis alasan laporan Anda di sini..."
              className="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-amber-400"
            />
            <div className="flex justify-end gap-2.5 pt-2 border-t border-zinc-800">
              <button
                type="button"
                onClick={() => setReportModalOpen(false)}
                className="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300 cursor-pointer"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleSubmitReport}
                disabled={!reportReason.trim() || isSubmittingReport}
                className="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 text-xs font-bold cursor-pointer disabled:opacity-40"
              >
                {isSubmittingReport ? 'Mengirim...' : 'Kirim Laporan'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Modal */}
      {deleteModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="w-full max-w-sm p-6 rounded-3xl bg-zinc-900 border border-zinc-800 text-left space-y-4 shadow-2xl">
            <div className="flex items-center gap-3 text-rose-400 border-b border-zinc-800 pb-3">
              <div className="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                <Trash2 className="w-4 h-4" />
              </div>
              <h4 className="font-bold text-white text-sm">Hapus Komentar?</h4>
            </div>
            <p className="text-xs text-zinc-300">Komentar ini akan dihapus secara permanen.</p>
            <div className="flex justify-end gap-2.5 pt-2 border-t border-zinc-800">
              <button
                type="button"
                onClick={() => setDeleteModalOpen(false)}
                className="px-4 py-2 rounded-xl bg-zinc-800 text-xs font-bold text-zinc-300 cursor-pointer"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleConfirmDelete}
                disabled={isDeleting}
                className="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-white text-xs font-bold cursor-pointer disabled:opacity-40"
              >
                {isDeleting ? 'Menghapus...' : 'Hapus'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
