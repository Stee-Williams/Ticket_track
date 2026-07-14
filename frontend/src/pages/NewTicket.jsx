import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import client, { getErrorMessage } from '../api/client';
import '../styles/pages.css';

const CATEGORIES = ['Général', 'Matériel', 'Logiciel', 'Réseau', 'Sécurité', 'Autre'];

export default function NewTicket() {
  const navigate = useNavigate();
  const [form, setForm] = useState({
    title: '',
    description: '',
    category: 'Général',
    priority: 'normale',
  });
  const [errors, setErrors] = useState({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const update = (field) => (e) => setForm({ ...form, [field]: e.target.value });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setErrors({});
    setLoading(true);
    try {
      const { data } = await client.post('/tickets', form);
      navigate(`/tickets/${data.data.ticket.id}`);
    } catch (err) {
      setError(getErrorMessage(err, 'Création impossible.'));
      setErrors(err?.response?.data?.errors || {});
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="form-page">
      <Link to="/tickets" className="link-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M19 12H5M12 19l-7-7 7-7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
        Retour aux tickets
      </Link>

      <div className="page-header">
        <div>
          <div className="page-title">Signaler une panne</div>
          <div className="page-subtitle">
            Décrivez le problème rencontré, un technicien sera assigné rapidement.
          </div>
        </div>
      </div>

      <div className="card form-card">
        {error && <div className="alert alert-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Titre du problème *</label>
            <input
              type="text"
              className="form-control"
              value={form.title}
              onChange={update('title')}
              placeholder="Ex : L'imprimante ne fonctionne plus"
              required
            />
            {errors.title && <div className="field-error">{errors.title}</div>}
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>Catégorie</label>
              <select className="form-control" value={form.category} onChange={update('category')}>
                {CATEGORIES.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
            </div>
            <div className="form-group">
              <label>Priorité</label>
              <select className="form-control" value={form.priority} onChange={update('priority')}>
                <option value="basse">Basse</option>
                <option value="normale">Normale</option>
                <option value="haute">Haute</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>
          </div>

          <div className="form-group">
            <label>Description détaillée *</label>
            <textarea
              className="form-control"
              value={form.description}
              onChange={update('description')}
              placeholder="Décrivez le problème : quand est-il survenu, quels sont les symptômes, quel matériel/logiciel est concerné…"
              required
            />
            {errors.description && <div className="field-error">{errors.description}</div>}
          </div>

          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Envoi…' : 'Soumettre le ticket'}
            </button>
            <button type="button" className="btn btn-ghost" onClick={() => navigate('/tickets')}>
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
