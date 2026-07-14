import { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { getErrorMessage } from '../api/client';
import AuthHero from '../components/AuthHero';
import '../styles/auth.css';

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = location.state?.from?.pathname || '/';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(email, password);
      navigate(from, { replace: true });
    } catch (err) {
      setError(getErrorMessage(err, 'Connexion impossible.'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <AuthHero />
      <div className="auth-form-side">
        <div className="auth-card">
          <h2>Bon retour !</h2>
          <p className="subtitle">Connectez-vous pour accéder à vos tickets.</p>

          {error && <div className="alert alert-error">{error}</div>}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label>Adresse e-mail</label>
              <input
                type="email"
                className="form-control"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="vous@exemple.com"
                required
              />
            </div>
            <div className="form-group">
              <label>Mot de passe</label>
              <input
                type="password"
                className="form-control"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                required
              />
            </div>
            <button type="submit" className="btn btn-primary btn-block" disabled={loading}>
              {loading ? 'Connexion…' : 'Se connecter'}
            </button>
          </form>

          <div className="demo-box">
            <strong>Comptes de démonstration (mot de passe : password123)</strong>
            manager@tickettrack.com · technicien@tickettrack.com · client@tickettrack.com
          </div>

          <div className="auth-switch">
            Pas encore de compte ? <Link to="/register">Créer un compte</Link>
          </div>
        </div>
      </div>
    </div>
  );
}
