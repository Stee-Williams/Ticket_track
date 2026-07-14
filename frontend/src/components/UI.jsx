export function Spinner() {
  return <div className="spinner" />;
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
