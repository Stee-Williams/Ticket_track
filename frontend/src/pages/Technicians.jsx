import { useState, useEffect } from 'react';
import client from '../api/client';
import { PageSkeleton, EmptyState } from '../components/UI';
import { initials } from '../utils/labels';
import '../styles/pages.css';

export default function Technicians() {
  const [technicians, setTechnicians] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    client
      .get('/users/technicians')
      .then(({ data }) => setTechnicians(data.data.technicians))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div>
      <div className="page-header">
        <div>
          <div className="page-eyebrow"><span className="page-eyebrow-dot" /> ÉQUIPE SUPPORT</div>
          <h1 className="page-title">Techniciens</h1>
          <div className="page-subtitle">Équipe de maintenance et charge de travail actuelle</div>
        </div>
      </div>

      {loading ? (
        <PageSkeleton variant="technicians" />
      ) : technicians.length === 0 ? (
        <div className="card">
          <EmptyState title="Aucun technicien" message="Aucun technicien n'est enregistré pour le moment." />
        </div>
      ) : (
        <div className="tech-grid">
          {technicians.map((t) => (
            <div key={t.id} className="card tech-card">
              <div className="tech-avatar">{initials(t.name)}</div>
              <div className="tech-name">{t.name}</div>
              <div className="tech-email">{t.email}</div>
              {t.phone && <div className="tech-email">{t.phone}</div>}
              <div className="tech-load">
                <strong>{t.active_tickets}</strong>
                <div style={{ color: 'var(--text-muted)' }}>
                  ticket{t.active_tickets > 1 ? 's' : ''} en cours
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
