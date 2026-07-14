import {
  STATUS_LABELS,
  STATUS_CLASS,
  PRIORITY_LABELS,
  PRIORITY_CLASS,
} from '../utils/labels';

export function StatusBadge({ status }) {
  return (
    <span className={`badge ${STATUS_CLASS[status] || ''}`}>
      <span className="dot" />
      {STATUS_LABELS[status] || status}
    </span>
  );
}

export function PriorityBadge({ priority }) {
  return (
    <span className={`badge ${PRIORITY_CLASS[priority] || ''}`}>
      {PRIORITY_LABELS[priority] || priority}
    </span>
  );
}
