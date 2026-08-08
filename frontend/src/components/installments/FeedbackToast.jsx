import { useEffect, useState } from 'react';

const CheckIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <polyline points="20 6 9 17 4 12" />
  </svg>
);

const AlertIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="10" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

const InfoIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="12" cy="12" r="9" />
    <line x1="12" y1="8" x2="12" y2="12" />
    <line x1="12" y1="16" x2="12.01" y2="16" />
  </svg>
);

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

const ICONS = { success: CheckIcon, error: AlertIcon, info: InfoIcon, warning: AlertIcon };

export default function FeedbackToast({ feedback, onClose, duration = 4500 }) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    if (!feedback) return undefined;
    setVisible(true);
    const timer = setTimeout(() => {
      setVisible(false);
      if (onClose) onClose();
    }, duration);
    return () => clearTimeout(timer);
  }, [feedback, duration, onClose]);

  if (!feedback || !visible) return null;

  const Icon = ICONS[feedback.kind] || InfoIcon;

  return (
    <div className={`inst-toast inst-toat-v2 ${feedback.kind}`} role={feedback.kind === 'error' ? 'alert' : 'status'} aria-live="polite">
      <span className="inst-toast-icon" aria-hidden><Icon /></span>
      <div className="inst-toast-body">
        {feedback.title ? <strong className="inst-toast-title">{feedback.title}</strong> : null}
        <span className="inst-toast-message">{feedback.message}</span>
      </div>
      <button type="button" className="inst-toast-close" onClick={() => { setVisible(false); onClose?.(); }} aria-label="Dismiss notification">
        <CloseIcon />
      </button>
    </div>
  );
}
