import { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import client, { getErrorMessage, getShared } from '../api/client';
import { useAuth } from '../context/AuthContext';
import { StatusBadge, PriorityBadge } from '../components/Badges';
import { PageSkeleton, EmptyState } from '../components/UI';
import { formatDate } from '../utils/labels';
import '../styles/pages.css';

const PAGE_SIZE = 10;

export default function Tickets() {
  const { user } = useAuth();
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState('');
  const [exportError, setExportError] = useState('');
  const [filters, setFilters] = useState({ search: '', status: '', priority: '' });
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ page: 1, pages: 1, total: 0, limit: PAGE_SIZE });

  const buildParams = useCallback(() => {
    const params = {};
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.priority) params.priority = filters.priority;
    return params;
  }, [filters]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params = { ...buildParams(), page, limit: PAGE_SIZE };
      const { data } = await getShared('/tickets', { params });
      setTickets(data.data.tickets);
      if (data.data.pagination) setPagination(data.data.pagination);
    } finally {
      setLoading(false);
    }
  }, [buildParams, page]);

  // Débounce léger sur la recherche / les filtres
  useEffect(() => {
    const t = setTimeout(load, 250);
    return () => clearTimeout(t);
  }, [load]);

  // Revenir à la première page quand un filtre change
  const updateFilter = (key, value) => {
    setFilters((f) => ({ ...f, [key]: value }));
    setPage(1);
  };

  const handleExport = async (format) => {
    setExportError('');
    setExporting(format);
    try {
      const params = { ...buildParams(), format };
      const res = await client.get('/tickets/export', { params, responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const a = document.createElement('a');
      a.href = url;
      a.download = `tickets_${new Date().toISOString().slice(0, 10)}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      let message = getErrorMessage(err, 'Impossible de générer l’export.');
      const responseData = err?.response?.data;
      if (responseData instanceof Blob) {
        const text = await responseData.text();
        try {
          const payload = JSON.parse(text);
          if (typeof payload.message === 'string') message = payload.message;
        } catch {
          // Conserve le message générique si la réponse n'est pas une erreur JSON de l'API.
        }
      }
      setExportError(message);
    } finally {
      setExporting('');
    }
  };

  const canCreate = user.role === 'user' || user.role === 'manager';
  const hasTickets = tickets.length > 0;

  return (
    <div>
      <div className="page-header">
        <div>
          <div className="page-eyebrow"><span className="page-eyebrow-dot" /> CENTRE DE SUPPORT</div>
          <h1 className="page-title">Tickets</h1>
          <div className="page-subtitle">
            {user.role === 'user' && 'Vos demandes de dépannage'}
            {user.role === 'technician' && 'Tickets qui vous sont attribués'}
            {user.role === 'manager' && 'Tous les tickets de l\'organisation'}
          </div>
        </div>
        <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
          <button
            className="btn btn-ghost"
            onClick={() => handleExport('xlsx')}
            disabled={exporting !== '' || !hasTickets}
            title="Exporter au format Excel"
          >
            {exporting === 'xlsx' ? 'Export…' : 'Excel'}
          </button>
          <button
            className="btn btn-ghost"
            onClick={() => handleExport('pdf')}
            disabled={exporting !== '' || !hasTickets}
            title="Exporter au format PDF"
          >
            {exporting === 'pdf' ? 'Export…' : 'PDF'}
          </button>
          {canCreate && (
            <Link to="/tickets/nouveau" className="btn btn-primary">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 5v14M5 12h14" strokeLinecap="round" />
              </svg>
              Nouveau ticket
            </Link>
          )}
        </div>
      </div>

      {exportError && <div className="alert alert-error" role="alert">{exportError}</div>}

      <div className="filters-panel card">
        <div className="search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8" />
            <path d="M21 21l-4.35-4.35" strokeLinecap="round" />
          </svg>
          <input
            id="ticket-search"
            className="form-control"
            aria-label="Rechercher un ticket"
            placeholder="Rechercher (titre, référence…)"
            value={filters.search}
            onChange={(e) => updateFilter('search', e.target.value)}
          />
        </div>
        <select
          id="ticket-status"
          className="form-control"
          aria-label="Filtrer par statut"
          value={filters.status}
          onChange={(e) => updateFilter('status', e.target.value)}
        >
          <option value="">Tous les statuts</option>
          <option value="en_attente">En attente</option>
          <option value="en_cours">En cours</option>
          <option value="traite">Traité</option>
        </select>
        <select
          id="ticket-priority"
          className="form-control"
          aria-label="Filtrer par priorité"
          value={filters.priority}
          onChange={(e) => updateFilter('priority', e.target.value)}
        >
          <option value="">Toutes priorités</option>
          <option value="urgente">Urgente</option>
          <option value="haute">Haute</option>
          <option value="normale">Normale</option>
          <option value="basse">Basse</option>
        </select>
      </div>

      {loading ? (
        <PageSkeleton variant="tickets" />
      ) : !hasTickets ? (
        <div className="card">
          <EmptyState
            title="Aucun ticket"
            message="Aucun ticket ne correspond à votre recherche."
            action={
              canCreate && (
                <Link to="/tickets/nouveau" className="btn btn-primary">
                  Créer un ticket
                </Link>
              )
            }
          />
        </div>
      ) : (
        <>
          {/* Vue tableau (bureau) */}
          <div className="card ticket-table-wrapper">
            <table className="ticket-table">
              <thead>
                <tr>
                  <th scope="col">Réf.</th>
                  <th scope="col">Titre</th>
                  <th scope="col">Priorité</th>
                  <th scope="col">Statut</th>
                  {user.role !== 'user' && <th scope="col">Demandeur</th>}
                  <th scope="col">Technicien</th>
                  <th scope="col">Créé le</th>
                </tr>
              </thead>
              <tbody>
                {tickets.map((t) => (
                  <tr key={t.id}>
                    <td className="ref-cell">{t.reference}</td>
                    <td className="title-cell">
                      <Link className="ticket-row-link" to={`/tickets/${t.id}`}>{t.title}</Link>
                      <div className="cat">{t.category}</div>
                    </td>
                    <td><PriorityBadge priority={t.priority} /></td>
                    <td><StatusBadge status={t.status} /></td>
                    {user.role !== 'user' && <td>{t.creator_name}</td>}
                    <td>{t.technician_name || <span style={{ color: 'var(--text-muted)' }}>Non assigné</span>}</td>
                    <td style={{ color: 'var(--text-muted)', fontSize: 13 }}>{formatDate(t.created_at)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Vue cartes (mobile) */}
          <div className="ticket-cards">
            {tickets.map((t) => (
              <Link key={t.id} to={`/tickets/${t.id}`} className="card ticket-mini">
                <div className="ticket-mini-head">
                  <span className="ref-cell">{t.reference}</span>
                  <StatusBadge status={t.status} />
                </div>
                <div className="ticket-mini-title">{t.title}</div>
                <div className="ticket-mini-meta">
                  <PriorityBadge priority={t.priority} />
                  <span className="badge badge-basse">{t.category}</span>
                </div>
              </Link>
            ))}
          </div>

          <Pagination pagination={pagination} onChange={setPage} />
        </>
      )}
    </div>
  );
}

function Pagination({ pagination, onChange }) {
  const { page, pages, total, limit } = pagination;
  if (pages <= 1) {
    return (
      <div className="pagination">
        <span className="pagination-info">{total} ticket{total > 1 ? 's' : ''}</span>
      </div>
    );
  }

  const from = (page - 1) * limit + 1;
  const to = Math.min(page * limit, total);

  return (
    <div className="pagination">
      <span className="pagination-info">
        {from}–{to} sur {total}
      </span>
      <div className="pagination-controls">
        <button
          className="btn btn-ghost btn-sm"
          disabled={page <= 1}
          onClick={() => onChange(page - 1)}
        >
          Précédent
        </button>
        <span className="pagination-page">Page {page} / {pages}</span>
        <button
          className="btn btn-ghost btn-sm"
          disabled={page >= pages}
          onClick={() => onChange(page + 1)}
        >
          Suivant
        </button>
      </div>
    </div>
  );
}
