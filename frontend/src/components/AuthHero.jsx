const FEATURES = [
  'Création de tickets simplifiée',
  'Attribution aux techniciens',
  'Suivi en temps réel',
  'Rapports et analyses détaillés',
];

export default function AuthHero() {
  return (
    <div className="auth-hero">
      <div className="auth-hero-topline">
        <div className="auth-hero-brand">
          <div className="logo" aria-hidden="true">
            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
              <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" strokeLinejoin="round" />
              <path d="M8.5 10.5h7M12 10.5v6" strokeLinecap="round" />
            </svg>
          </div>
          <span>Ticket Track</span>
        </div>
        <span className="auth-hero-edition">SUPPORT · MAINTENANCE</span>
      </div>
      <div className="auth-hero-copy">
        <div className="auth-eyebrow"><span /> UNE ÉQUIPE. UN SUIVI. AUCUNE DEMANDE PERDUE.</div>
        <h1>Le support, <span>sans angle mort.</span></h1>
        <p>
          Chaque demande au bon endroit, chaque intervention visible.
          Une équipe alignée du signalement jusqu’à la résolution.
        </p>
      </div>
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
      <div className="auth-hero-foot">
        <span>CONÇU POUR LES ÉQUIPES QUI FONT AVANCER LES CHOSES</span>
        <span className="auth-hero-foot-mark">TT <i /> 01</span>
      </div>
    </div>
  );
}
