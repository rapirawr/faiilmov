import React, { useState, useEffect } from 'react';
import Modal, { DynamicIcon } from './common/Modal';
import * as LucideIcons from 'lucide-react';

export default function GlobalModal() {
  const [modalType, setModalType] = useState(null); // 'confirm' | 'role' | 'alert' | 'prompt' | 'custom' | null
  
  // States for specific modal variants
  const [confirmConfig, setConfirmConfig] = useState(null);
  const [roleConfig, setRoleConfig] = useState(null);
  const [selectedRole, setSelectedRole] = useState('user');
  const [alertConfig, setAlertConfig] = useState(null);
  const [promptConfig, setPromptConfig] = useState(null);
  const [promptValue, setPromptValue] = useState('');
  const [customConfig, setCustomConfig] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const closeModal = () => {
    setModalType(null);
    setIsSubmitting(false);
  };

  useEffect(() => {
    // 1. Confirm Modal Trigger
    const handleOpenConfirm = (e) => {
      const detail = e.detail || {};
      setConfirmConfig(detail);
      setModalType('confirm');
    };

    // 2. Role Selector Modal Trigger
    const handleOpenRole = (e) => {
      const detail = e.detail || {};
      setRoleConfig(detail);
      setSelectedRole(detail.currentRole || 'user');
      setModalType('role');
    };

    // 3. Alert Modal Trigger
    const handleOpenAlert = (e) => {
      const detail = e.detail || {};
      setAlertConfig(detail);
      setModalType('alert');
    };

    // 4. Prompt Modal Trigger
    const handleOpenPrompt = (e) => {
      const detail = e.detail || {};
      setPromptConfig(detail);
      setPromptValue(detail.defaultValue || '');
      setModalType('prompt');
    };

    // 5. Custom Dynamic Modal Trigger
    const handleOpenCustom = (e) => {
      const detail = e.detail || {};
      setCustomConfig(detail);
      setModalType('custom');
    };

    const handleClose = () => {
      closeModal();
    };

    // Register custom window events
    window.addEventListener('open-confirm-modal', handleOpenConfirm);
    window.addEventListener('open-role-modal', handleOpenRole);
    window.addEventListener('open-alert-modal', handleOpenAlert);
    window.addEventListener('open-prompt-modal', handleOpenPrompt);
    window.addEventListener('open-custom-modal', handleOpenCustom);
    window.addEventListener('close-global-modal', handleClose);

    // Register globally accessible window helper methods
    window.confirmModal = (options = {}) => {
      window.dispatchEvent(new CustomEvent('open-confirm-modal', { detail: options }));
    };

    window.openRoleModal = (options = {}) => {
      window.dispatchEvent(new CustomEvent('open-role-modal', { detail: options }));
    };

    window.alertModal = (options = {}) => {
      window.dispatchEvent(new CustomEvent('open-alert-modal', { detail: options }));
    };

    window.promptModal = (options = {}) => {
      window.dispatchEvent(new CustomEvent('open-prompt-modal', { detail: options }));
    };

    window.openModal = (options = {}) => {
      window.dispatchEvent(new CustomEvent('open-custom-modal', { detail: options }));
    };

    window.closeModal = () => {
      window.dispatchEvent(new CustomEvent('close-global-modal'));
    };

    // Global Delegated Click Listener for Blade HTML Data-Attributes
    const handleDocumentClick = (e) => {
      // Check for Role Modal Trigger
      const roleBtn = e.target.closest('[data-modal-role]');
      if (roleBtn) {
        e.preventDefault();
        window.openRoleModal({
          userId: roleBtn.dataset.userId || roleBtn.dataset.id,
          userName: roleBtn.dataset.userName || roleBtn.dataset.name,
          userEmail: roleBtn.dataset.userEmail || roleBtn.dataset.email,
          currentRole: roleBtn.dataset.currentRole || roleBtn.dataset.role || 'user',
          actionUrl: roleBtn.dataset.action || roleBtn.dataset.actionUrl,
        });
        return;
      }

      // Check for Confirm Modal Trigger
      const confirmBtn = e.target.closest('[data-modal-confirm]');
      if (confirmBtn) {
        e.preventDefault();
        const formSelector = confirmBtn.dataset.form;
        const formAction = confirmBtn.dataset.action || confirmBtn.getAttribute('href');
        
        window.confirmModal({
          title: confirmBtn.dataset.title || 'Konfirmasi Tindakan',
          message: confirmBtn.dataset.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
          icon: confirmBtn.dataset.icon || 'alert-circle',
          variant: confirmBtn.dataset.variant || 'amber',
          confirmText: confirmBtn.dataset.confirmText || 'Lanjutkan',
          cancelText: confirmBtn.dataset.cancelText || 'Batal',
          onConfirm: () => {
            if (formSelector) {
              const targetForm = document.querySelector(formSelector);
              if (targetForm) targetForm.submit();
            } else if (confirmBtn.closest('form')) {
              confirmBtn.closest('form').submit();
            } else if (formAction && formAction !== '#' && !formAction.startsWith('javascript:')) {
              window.location.href = formAction;
            }
          },
        });
        return;
      }

      // Check for Alert Modal Trigger
      const alertBtn = e.target.closest('[data-modal-alert]');
      if (alertBtn) {
        e.preventDefault();
        window.alertModal({
          title: alertBtn.dataset.title || 'Informasi',
          message: alertBtn.dataset.message || '',
          icon: alertBtn.dataset.icon || 'info',
          variant: alertBtn.dataset.variant || 'default',
        });
        return;
      }
    };

    document.addEventListener('click', handleDocumentClick);

    return () => {
      window.removeEventListener('open-confirm-modal', handleOpenConfirm);
      window.removeEventListener('open-role-modal', handleOpenRole);
      window.removeEventListener('open-alert-modal', handleOpenAlert);
      window.removeEventListener('open-prompt-modal', handleOpenPrompt);
      window.removeEventListener('open-custom-modal', handleOpenCustom);
      window.removeEventListener('close-global-modal', handleClose);
      document.removeEventListener('click', handleDocumentClick);
    };
  }, []);

  // Handle Confirm Submission
  const handleConfirmAction = async () => {
    if (!confirmConfig) return;

    if (typeof confirmConfig.onConfirm === 'function') {
      setIsSubmitting(true);
      try {
        await confirmConfig.onConfirm();
      } catch (err) {
        console.error('Confirm action error:', err);
      } finally {
        setIsSubmitting(false);
        closeModal();
      }
    } else if (confirmConfig.formSelector) {
      const form = document.querySelector(confirmConfig.formSelector);
      if (form) form.submit();
      closeModal();
    } else {
      closeModal();
    }
  };

  // Helper to retrieve CSRF token
  const getCsrfToken = () => {
    return (
      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
      document.querySelector('input[name="_token"]')?.value ||
      ''
    );
  };

  return (
    <>
      {/* 1. CONFIRMATION MODAL */}
      {modalType === 'confirm' && confirmConfig && (
        <Modal
          isOpen={true}
          onClose={closeModal}
          title={confirmConfig.title || 'Konfirmasi'}
          subtitle={confirmConfig.subtitle || ''}
          icon={confirmConfig.icon || 'alert-triangle'}
          variant={confirmConfig.variant || 'amber'}
          size={confirmConfig.size || 'md'}
          footer={
            <>
              <button
                type="button"
                onClick={() => {
                  if (typeof confirmConfig.onCancel === 'function') confirmConfig.onCancel();
                  closeModal();
                }}
                disabled={isSubmitting}
                className="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer"
              >
                {confirmConfig.cancelText || 'Batal'}
              </button>
              <button
                type="button"
                onClick={handleConfirmAction}
                disabled={isSubmitting}
                className={`px-5 py-2 rounded-xl text-xs font-bold shadow-lg transition-all cursor-pointer flex items-center gap-1.5 ${
                  confirmConfig.variant === 'rose' || confirmConfig.variant === 'danger'
                    ? 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-600/20'
                    : confirmConfig.variant === 'emerald' || confirmConfig.variant === 'success'
                    ? 'bg-emerald-500 hover:bg-emerald-400 text-zinc-950 shadow-emerald-500/20'
                    : confirmConfig.variant === 'sky' || confirmConfig.variant === 'info'
                    ? 'bg-sky-500 hover:bg-sky-400 text-zinc-950 shadow-sky-500/20'
                    : 'bg-amber-500 hover:bg-amber-400 text-zinc-950 shadow-amber-500/20'
                }`}
              >
                {isSubmitting ? (
                  <>
                    <LucideIcons.Loader2 className="w-3.5 h-3.5 animate-spin" />
                    <span>Memproses...</span>
                  </>
                ) : (
                  <>
                    <DynamicIcon name={confirmConfig.confirmIcon || 'check'} className="w-3.5 h-3.5" />
                    <span>{confirmConfig.confirmText || 'Lanjutkan'}</span>
                  </>
                )}
              </button>
            </>
          }
        >
          <div className="space-y-3">
            {confirmConfig.message && (
              <p className="text-xs text-zinc-300 leading-relaxed">
                {confirmConfig.message}
              </p>
            )}
            {confirmConfig.customHtml && (
              <div
                dangerouslySetInnerHTML={{ __html: confirmConfig.customHtml }}
                className="text-xs text-zinc-300"
              />
            )}
          </div>
        </Modal>
      )}

      {/* 2. MULTI-ROLE SELECTION MODAL */}
      {modalType === 'role' && roleConfig && (
        <Modal
          isOpen={true}
          onClose={closeModal}
          title="Ubah Hak Akses / Role"
          subtitle={`${roleConfig.userName || 'Pengguna'} (${roleConfig.userEmail || ''})`}
          icon="shield-check"
          variant="amber"
          size="md"
        >
          <form
            action={roleConfig.actionUrl || `/admin/users/${roleConfig.userId}/role`}
            method="POST"
            className="space-y-3"
          >
            <input type="hidden" name="_token" value={getCsrfToken()} />

            <div className="space-y-2">
              {/* Option 1: Regular User */}
              <label
                onClick={() => setSelectedRole('user')}
                className={`flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition-all ${
                  selectedRole === 'user'
                    ? 'bg-white/5 border-amber-500/40 text-white shadow-sm'
                    : 'border-zinc-800 hover:border-zinc-700 text-zinc-400'
                }`}
              >
                <input
                  type="radio"
                  name="role"
                  value="user"
                  checked={selectedRole === 'user'}
                  onChange={() => setSelectedRole('user')}
                  className="mt-1 accent-amber-500"
                />
                <div className="space-y-0.5">
                  <p className="font-bold text-xs text-zinc-200">Pengguna Biasa (User)</p>
                  <p className="text-[11px] text-zinc-500 leading-tight">
                    Hanya memiliki hak akses frontend standar (nonton film, watchlist, ulasan & komentar).
                  </p>
                </div>
              </label>

              {/* Option 2: Admin Konten */}
              <label
                onClick={() => setSelectedRole('admin')}
                className={`flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition-all ${
                  selectedRole === 'admin'
                    ? 'bg-sky-500/10 border-sky-500/40 text-white shadow-sm'
                    : 'border-zinc-800 hover:border-zinc-700 text-zinc-400'
                }`}
              >
                <input
                  type="radio"
                  name="role"
                  value="admin"
                  checked={selectedRole === 'admin'}
                  onChange={() => setSelectedRole('admin')}
                  className="mt-1 accent-sky-500"
                />
                <div className="space-y-0.5">
                  <p className="font-bold text-xs text-sky-300 flex items-center gap-1.5">
                    <span>Admin Konten</span>
                    <span className="px-1.5 py-0.2 rounded text-[9px] bg-sky-500/20 text-sky-300 border border-sky-500/30">
                      Operasional
                    </span>
                  </p>
                  <p className="text-[11px] text-zinc-400 leading-tight">
                    Dapat mengelola Film, Episode, Genre, Aktor, Banner CMS, serta moderasi Ulasan & Komentar.
                  </p>
                </div>
              </label>

              {/* Option 3: Administrator (Superadmin) */}
              <label
                onClick={() => setSelectedRole('administrator')}
                className={`flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition-all ${
                  selectedRole === 'administrator'
                    ? 'bg-amber-500/10 border-amber-500/40 text-white shadow-sm'
                    : 'border-zinc-800 hover:border-zinc-700 text-zinc-400'
                }`}
              >
                <input
                  type="radio"
                  name="role"
                  value="administrator"
                  checked={selectedRole === 'administrator'}
                  onChange={() => setSelectedRole('administrator')}
                  className="mt-1 accent-amber-500"
                />
                <div className="space-y-0.5">
                  <p className="font-bold text-xs text-amber-300 flex items-center gap-1.5">
                    <span>Administrator (Superadmin)</span>
                    <span className="px-1.5 py-0.2 rounded text-[9px] bg-amber-500/20 text-amber-300 border border-amber-500/30">
                      Master
                    </span>
                  </p>
                  <p className="text-[11px] text-zinc-400 leading-tight">
                    Akses penuh ke seluruh sistem, iklan, settings website, menu navigasi, dan manajemen role pengguna.
                  </p>
                </div>
              </label>
            </div>

            <div className="flex justify-end gap-2.5 pt-3 border-t border-zinc-800">
              <button
                type="button"
                onClick={closeModal}
                className="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                className="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition-all cursor-pointer flex items-center gap-1.5"
              >
                <LucideIcons.Check className="w-3.5 h-3.5" />
                <span>Simpan Perubahan Role</span>
              </button>
            </div>
          </form>
        </Modal>
      )}

      {/* 3. ALERT / INFO MODAL */}
      {modalType === 'alert' && alertConfig && (
        <Modal
          isOpen={true}
          onClose={() => {
            if (typeof alertConfig.onClose === 'function') alertConfig.onClose();
            closeModal();
          }}
          title={alertConfig.title || 'Informasi'}
          subtitle={alertConfig.subtitle || ''}
          icon={alertConfig.icon || 'info'}
          variant={alertConfig.variant || 'default'}
          size={alertConfig.size || 'sm'}
          footer={
            <button
              type="button"
              onClick={() => {
                if (alertConfig.actionUrl) window.location.href = alertConfig.actionUrl;
                if (typeof alertConfig.onClose === 'function') alertConfig.onClose();
                closeModal();
              }}
              className="px-5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs transition-colors cursor-pointer"
            >
              {alertConfig.buttonText || 'Mengerti'}
            </button>
          }
        >
          <p className="text-xs text-zinc-300 leading-relaxed">
            {alertConfig.message}
          </p>
        </Modal>
      )}

      {/* 4. PROMPT MODAL */}
      {modalType === 'prompt' && promptConfig && (
        <Modal
          isOpen={true}
          onClose={closeModal}
          title={promptConfig.title || 'Input'}
          subtitle={promptConfig.subtitle || ''}
          icon={promptConfig.icon || 'edit-3'}
          variant={promptConfig.variant || 'default'}
          size={promptConfig.size || 'sm'}
          footer={
            <>
              <button
                type="button"
                onClick={closeModal}
                className="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition-colors cursor-pointer"
              >
                {promptConfig.cancelText || 'Batal'}
              </button>
              <button
                type="button"
                onClick={() => {
                  if (typeof promptConfig.onConfirm === 'function') {
                    promptConfig.onConfirm(promptValue);
                  }
                  closeModal();
                }}
                className="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition-all cursor-pointer"
              >
                {promptConfig.confirmText || 'Simpan'}
              </button>
            </>
          }
        >
          <div className="space-y-2">
            {promptConfig.message && (
              <p className="text-xs text-zinc-300 leading-relaxed">
                {promptConfig.message}
              </p>
            )}
            <input
              type={promptConfig.inputType || 'text'}
              value={promptValue}
              onChange={(e) => setPromptValue(e.target.value)}
              placeholder={promptConfig.placeholder || ''}
              className="w-full bg-zinc-950 border border-zinc-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500 transition-colors"
              autoFocus
            />
          </div>
        </Modal>
      )}

      {/* 5. CUSTOM DYNAMIC MODAL */}
      {modalType === 'custom' && customConfig && (
        <Modal
          isOpen={true}
          onClose={closeModal}
          title={customConfig.title}
          subtitle={customConfig.subtitle}
          icon={customConfig.icon}
          variant={customConfig.variant || 'default'}
          size={customConfig.size || 'md'}
          footer={customConfig.footer}
        >
          {customConfig.htmlContent ? (
            <div dangerouslySetInnerHTML={{ __html: customConfig.htmlContent }} />
          ) : (
            customConfig.content
          )}
        </Modal>
      )}
    </>
  );
}
