const STAT_SLOTS = [0, 1, 2, 3];
const CARD_SLOTS = [0, 1, 2];
const ROW_SLOTS = [0, 1, 2, 3, 4];

function SkeletonBlock({ className = '' }) {
  return <div className={`skeleton-block ${className}`} aria-hidden="true" />;
}

export function PageSkeleton({ variant = 'dashboard' }) {
  return (
    <div
      className={`page-skeleton page-skeleton-${variant}`}
      role="status"
      aria-label="Chargement du contenu"
      aria-busy="true"
    >
      <div className="skeleton-heading">
        <SkeletonBlock className="skeleton-title" />
        <SkeletonBlock className="skeleton-subtitle" />
      </div>

      {(variant === 'dashboard' || variant === 'route') && (
        <>
          <div className="skeleton-stats">
            {STAT_SLOTS.map((slot) => (
              <div className="card skeleton-stat-card" key={slot}>
                <SkeletonBlock className="skeleton-stat-icon" />
                <div className="skeleton-stat-copy">
                  <SkeletonBlock className="skeleton-stat-value" />
                  <SkeletonBlock className="skeleton-stat-label" />
                </div>
              </div>
            ))}
          </div>
          <div className="skeleton-charts">
            {CARD_SLOTS.slice(0, 2).map((slot) => (
              <div className="card skeleton-chart-card" key={slot}>
                <SkeletonBlock className="skeleton-chart-title" />
                <SkeletonBlock className="skeleton-chart-area" />
              </div>
            ))}
          </div>
        </>
      )}

      {variant === 'tickets' && (
        <>
          <div className="skeleton-filters">
            <SkeletonBlock className="skeleton-search" />
            <SkeletonBlock className="skeleton-select" />
            <SkeletonBlock className="skeleton-select" />
          </div>
          <div className="card skeleton-table">
            {ROW_SLOTS.map((row) => (
              <div className="skeleton-table-row" key={row}>
                {CARD_SLOTS.map((cell) => <SkeletonBlock key={cell} />)}
              </div>
            ))}
          </div>
        </>
      )}

      {variant === 'detail' && (
        <div className="skeleton-detail-grid">
          <div className="card skeleton-detail-main">
            <SkeletonBlock className="skeleton-detail-title" />
            <SkeletonBlock className="skeleton-detail-text" />
            <SkeletonBlock className="skeleton-detail-text" />
            <SkeletonBlock className="skeleton-detail-text short" />
          </div>
          <div className="card skeleton-detail-side">
            {ROW_SLOTS.slice(0, 4).map((row) => <SkeletonBlock key={row} className="skeleton-detail-row" />)}
          </div>
        </div>
      )}

      {variant === 'technicians' && (
        <div className="skeleton-technicians">
          {CARD_SLOTS.map((slot) => (
            <div className="card skeleton-technician-card" key={slot}>
              <SkeletonBlock className="skeleton-technician-avatar" />
              <SkeletonBlock className="skeleton-technician-name" />
              <SkeletonBlock className="skeleton-technician-contact" />
              <SkeletonBlock className="skeleton-technician-contact short" />
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export function EmptyState({ title, message, action }) {
  return (
    <div className="empty-state">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <path d="M9 17h6M9 13h6M9 9h2" strokeLinecap="round" />
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <path d="M14 2v6h6" />
      </svg>
      <h3 style={{ marginBottom: 6 }}>{title}</h3>
      <p style={{ marginBottom: action ? 18 : 0 }}>{message}</p>
      {action}
    </div>
  );
}
