import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { getErrorMessage } from '../api/client';
import AuthHero from '../components/AuthHero';
import '../styles/auth.css';

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();

  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '' });
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
      await register(form);
      navigate('/', { replace: true });
    } catch (err) {
      setError(getErrorMessage(err, 'Inscription impossible.'));
      setErrors(err?.response?.data?.errors || {});
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <AuthHero />
      <div className="auth-form-side">
        <div className="auth-card">
          <h2>Créer un compte</h2>
          <p className="subtitle">Inscrivez-vous pour signaler et suivre vos pannes.</p>

          {error && <div className="alert alert-error" role="alert">{error}</div>}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label htmlFor="register-name">Nom complet</label>
              <input
                id="register-name"
                type="text"
                className="form-control"
                value={form.name}
                onChange={update('name')}
                placeholder="Jean Dupont"
                required
              />
              {errors.name && <div className="field-error">{errors.name}</div>}
            </div>
            <div className="form-group">
              <label htmlFor="register-email">Adresse e-mail</label>
              <input
                id="register-email"
                type="email"
                className="form-control"
                value={form.email}
                onChange={update('email')}
                placeholder="vous@exemple.com"
                required
              />
              {errors.email && <div className="field-error">{errors.email}</div>}
            </div>
            <div className="form-group">
              <label htmlFor="register-phone">Téléphone (optionnel)</label>
              <input
                id="register-phone"
                type="tel"
                className="form-control"
                value={form.phone}
                onChange={update('phone')}
                placeholder="+33 6 00 00 00 00"
              />
            </div>
            <div className="form-group">
              <label htmlFor="register-password">Mot de passe</label>
              <input
                id="register-password"
                type="password"
                className="form-control"
                value={form.password}
                onChange={update('password')}
                placeholder="Au moins 6 caractères"
                required
              />
              {errors.password && <div className="field-error">{errors.password}</div>}
            </div>
            <button type="submit" className="btn btn-primary btn-block" disabled={loading}>
              {loading ? 'Création…' : 'Créer mon compte'}
            </button>
          </form>

          <div className="auth-switch">
            Déjà inscrit ? <Link to="/login">Se connecter</Link>
          </div>
        </div>
      </div>
    </div>
  );
}
