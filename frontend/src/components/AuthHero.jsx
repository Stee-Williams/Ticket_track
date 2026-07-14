const FEATURES = [
  'Création de tickets simplifiée',
  'Attribution aux techniciens',
  'Suivi en temps réel',
  'Rapports et analyses détaillés',
];

export default function AuthHero() {
  return (
    <div className="auth-hero">
      <div className="auth-hero-brand">
        <div className="logo">TT</div>
        <span style={{ fontWeight: 700, fontSize: 18 }}>Ticket Track</span>
      </div>
      <h1>Gérez vos pannes efficacement, sans en laisser passer une seule.</h1>
      <p>
        Suivez et résolvez rapidement les pannes signalées par vos utilisateurs
        grâce à une plateforme conviviale, intuitive et complète.
      </p>
      <div className="auth-features">
        {FEATURES.map((f) => (
          <div className="auth-feature" key={f}>
            <span className="check">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                <path d="M20 6L9 17l-5-5" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            {f}
          </div>
        ))}
      </div>
    </div>
  );
}
