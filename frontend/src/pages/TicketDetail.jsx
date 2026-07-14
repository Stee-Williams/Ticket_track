import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link, useOutletContext } from 'react-router-dom';
import client, { getErrorMessage } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { StatusBadge, PriorityBadge } from '../components/Badges';
import { Spinner } from '../components/UI';
import { formatDate, initials, ROLE_LABELS } from '../utils/labels';
import '../styles/pages.css';

const ACTION_META = {
  created: { label: 'Création', cls: 'is-created' },
  assigned: { label: 'Attribution', cls: 'is-assigned' },
  status_changed: { label: 'Changement de statut', cls: 'is-status' },
  commented: { label: 'Commentaire', cls: 'is-commented' },
};

export default function TicketDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { reloadNotifications } = useOutletContext();

  const [ticket, setTicket] = useState(null);
  const [comments, setComments] = useState([]);
  const [history, setHistory] = useState([]);
  const [technicians, setTechnicians] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [comment, setComment] = useState('');
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    try {
      const { data } = await client.get(`/tickets/${id}`);
      setTicket(data.data.ticket);
      setComments(data.data.comments);
      setHistory(data.data.history || []);
    } catch (err) {
      setError(getErrorMessage(err, 'Ticket introuvable.'));
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    load();
  }, [load]);

  // Le gestionnaire charge la liste des techniciens pour l'attribution
  useEffect(() => {
    if (user.role === 'manager') {
      client.get('/users/technicians').then(({ data }) => setTechnicians(data.data.technicians)).catch(() => {});
    }
  }, [user.role]);

  const refresh = async () => {
    await load();
    reloadNotifications?.();
  };

  const handleAssign = async (technicianId) => {
    if (!technicianId) return;
    setBusy(true);
    try {
      await client.put(`/tickets/${id}/assign`, { assigned_to: Number(technicianId) });
      await refresh();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setBusy(false);
    }
  };

  const handleStatus = async (status) => {
    setBusy(true);
    try {
      await client.put(`/tickets/${id}/status`, { status });
      await refresh();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setBusy(false);
    }
  };

  const handleComment = async (e) => {
    e.preventDefault();
    if (!comment.trim()) return;
    setBusy(true);
    try {
      await client.post(`/tickets/${id}/comments`, { message: comment });
      setComment('');
      await refresh();
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setBusy(false);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Supprimer définitivement ce ticket ?')) return;
    try {
      await client.delete(`/tickets/${id}`);
      navigate('/tickets');
    } catch (err) {
      setError(getErrorMessage(err));
    }
  };

  if (loading) return <Spinner />;
  if (error && !ticket) return <div className="alert alert-error">{error}</div>;
  if (!ticket) return null;

  const isManager = user.role === 'manager';
  const isAssignedTech = user.role === 'technician' && ticket.assigned_to === user.id;
  const canChangeStatus = isManager || isAssignedTech;
  const canDelete = isManager || (ticket.created_by === user.id && ticket.status === 'en_attente');

  return (
    <div>
      <Link to="/tickets" className="link-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M19 12H5M12 19l-7-7 7-7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
        Retour aux tickets
      </Link>

      {error && <div className="alert alert-error">{error}</div>}

      <div className="detail-grid">
        <div className="card detail-main">
          <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
            <span className="ref-cell">{ticket.reference}</span>
            <PriorityBadge priority={ticket.priority} />
            <StatusBadge status={ticket.status} />
          </div>
          <h1 className="detail-title">{ticket.title}</h1>
          <div style={{ color: 'var(--text-muted)', fontSize: 13 }}>
            {ticket.category} · Créé le {formatDate(ticket.created_at)}
          </div>

          <div className="detail-desc">{ticket.description}</div>

          <div className="section-label">Historique des actions</div>
          {history.length === 0 ? (
            <p style={{ color: 'var(--text-muted)', fontSize: 14 }}>
              Aucune action enregistrée.
            </p>
          ) : (
            <ul className="timeline">
              {history.map((h) => {
                const meta = ACTION_META[h.action] || { label: h.action, cls: '' };
                return (
                  <li key={h.id} className={`timeline-item ${meta.cls}`}>
                    <div className="timeline-details">{h.details || meta.label}</div>
                    <div className="timeline-meta">
                      {h.actor ? `${h.actor} · ` : ''}{formatDate(h.created_at)}
                    </div>
                  </li>
                );
              })}
            </ul>
          )}

          <div className="section-label">Commentaires</div>

          {comments.length === 0 && (
            <p style={{ color: 'var(--text-muted)', fontSize: 14 }}>
              Aucun commentaire pour l'instant.
            </p>
          )}
          {comments.map((c) => (
            <div className="comment" key={c.id}>
              <div className="comment-avatar">{initials(c.author)}</div>
              <div className="comment-body">
                <div className="comment-head">
                  <span className="comment-author">{c.author}</span>
                  <span className="badge badge-role">{ROLE_LABELS[c.author_role]}</span>
                  <span className="comment-time">· {formatDate(c.created_at)}</span>
                </div>
                <div className="comment-text">{c.message}</div>
              </div>
            </div>
          ))}

          <form onSubmit={handleComment} style={{ marginTop: 18 }}>
            <textarea
              className="form-control"
              placeholder="Ajouter un commentaire…"
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              style={{ minHeight: 80 }}
            />
            <div style={{ marginTop: 10 }}>
              <button className="btn btn-primary btn-sm" disabled={busy || !comment.trim()}>
                Publier le commentaire
              </button>
            </div>
          </form>
        </div>

        <div className="card detail-side">
          <div className="detail-row">
            <span className="label">Statut</span>
            <StatusBadge status={ticket.status} />
          </div>
          <div className="detail-row">
            <span className="label">Priorité</span>
            <PriorityBadge priority={ticket.priority} />
          </div>
          <div className="detail-row">
            <span className="label">Demandeur</span>
            <span>{ticket.creator_name}</span>
          </div>
          <div className="detail-row">
            <span className="label">Technicien</span>
            <span>{ticket.technician_name || 'Non assigné'}</span>
          </div>
          {ticket.resolved_at && (
            <div className="detail-row">
              <span className="label">Résolu le</span>
              <span>{formatDate(ticket.resolved_at)}</span>
            </div>
          )}

          {/* Attribution (gestionnaire) */}
          {isManager && (
            <>
              <div className="section-label">Attribuer à un technicien</div>
              <select
                className="form-control"
                value={ticket.assigned_to || ''}
                onChange={(e) => handleAssign(e.target.value)}
                disabled={busy}
              >
                <option value="">— Choisir un technicien —</option>
                {technicians.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.name} ({t.active_tickets} en cours)
                  </option>
                ))}
              </select>
            </>
          )}

          {/* Changement de statut */}
          {canChangeStatus && (
            <>
              <div className="section-label">Changer le statut</div>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                <button
                  className="btn btn-ghost btn-block"
                  disabled={busy || ticket.status === 'en_attente'}
                  onClick={() => handleStatus('en_attente')}
                >
                  En attente
                </button>
                <button
                  className="btn btn-secondary btn-block"
                  disabled={busy || ticket.status === 'en_cours'}
                  onClick={() => handleStatus('en_cours')}
                >
                  En cours
                </button>
                <button
                  className="btn btn-primary btn-block"
                  disabled={busy || ticket.status === 'traite'}
                  onClick={() => handleStatus('traite')}
                  style={{ background: 'var(--success)' }}
                >
                  Marquer comme traité
                </button>
              </div>
            </>
          )}

          {canDelete && (
            <>
              <div className="section-label">Zone de danger</div>
              <button className="btn btn-danger btn-block" onClick={handleDelete}>
                Supprimer le ticket
              </button>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
