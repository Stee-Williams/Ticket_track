import { useState, useEffect, useCallback } from 'react';
import { NavLink, useLocation, useNavigate, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { ROLE_LABELS, initials, formatDate } from '../utils/labels';
import client, { getShared } from '../api/client';

const NAV_ITEMS = [
  { to: '/', label: 'Tableau de bord', icon: 'grid', end: true, roles: ['user', 'technician', 'manager'] },
  { to: '/tickets', label: 'Tickets', icon: 'ticket', roles: ['user', 'technician', 'manager'] },
  { to: '/tickets/nouveau', label: 'Nouveau ticket', icon: 'plus', roles: ['user', 'manager'] },
  { to: '/techniciens', label: 'Techniciens', icon: 'users', roles: ['manager'] },
];

function Icon({ name }) {
  const paths = {
    grid: <path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z" />,
    ticket: <path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z" />,
    plus: <path d="M12 5v14M5 12h14" />,
    users: <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />,
    bell: <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" />,
    logout: <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />,
  };
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      {paths[name]}
    </svg>
  );
}

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [notifications, setNotifications] = useState([]);
  const [unread, setUnread] = useState(0);
  const [showNotif, setShowNotif] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  const loadNotifications = useCallback(async () => {
    try {
      const { data } = await getShared('/notifications');
      setNotifications(data.data.notifications);
      setUnread(data.data.unread);
    } catch {
      /* silencieux */
    }
  }, []);

  // Suivi en temps réel : rafraîchissement périodique des notifications
  useEffect(() => {
    loadNotifications();
    const interval = setInterval(loadNotifications, 15000);
    return () => clearInterval(interval);
  }, [loadNotifications]);

  const handleOpenNotif = async () => {
    const willShow = !showNotif;
    setShowNotif(willShow);
    if (willShow && unread > 0) {
      await client.put('/notifications/read');
      setUnread(0);
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
    }
  };

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const items = NAV_ITEMS.filter((i) => i.roles.includes(user.role));
  const currentPage = location.pathname.startsWith('/tickets/nouveau')
    ? 'Nouveau ticket'
    : location.pathname.startsWith('/tickets/')
      ? 'Détail du ticket'
      : NAV_ITEMS.find((item) => item.to === location.pathname)?.label || 'Tableau de bord';

  return (
    <div className="layout">
      <aside className={`sidebar ${mobileOpen ? 'open' : ''}`}>
        <div className="sidebar-brand">
          <div className="brand-logo" aria-hidden="true">
            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
              <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" strokeLinejoin="round" />
              <path d="M8.5 10.5h7M12 10.5v6" strokeLinecap="round" />
            </svg>
          </div>
          <div>
            <div className="brand-name">Ticket Track</div>
            <div className="brand-sub">Centre de support</div>
          </div>
        </div>

        <nav className="sidebar-nav" id="primary-navigation" aria-label="Navigation principale">
          <div className="nav-section-label">Espace de travail</div>
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`}
              onClick={() => setMobileOpen(false)}
            >
              <Icon name={item.icon} />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="sidebar-status">
            <span className="sidebar-status-dot" />
            <span>Service opérationnel</span>
          </div>
          <button className="nav-item logout" onClick={handleLogout}>
            <Icon name="logout" />
            <span>Déconnexion</span>
          </button>
        </div>
      </aside>

      {mobileOpen && (
        <button
          className="overlay"
          onClick={() => setMobileOpen(false)}
          aria-label="Fermer le menu"
        />
      )}

      <div className="main">
        <header className="topbar">
          <button
            className="menu-toggle"
            onClick={() => setMobileOpen(true)}
            aria-label="Ouvrir le menu"
            aria-expanded={mobileOpen}
            aria-controls="primary-navigation"
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M3 12h18M3 6h18M3 18h18" strokeLinecap="round" />
            </svg>
          </button>

          <div className="topbar-context">
            <span className="topbar-context-root">Ticket Track</span>
            <span className="topbar-context-divider">/</span>
            <span className="topbar-context-page">{currentPage}</span>
          </div>

          <div className="topbar-spacer" />

          <div className="notif-wrapper">
            <button
              className="notif-btn"
              onClick={handleOpenNotif}
              aria-label={`Notifications${unread > 0 ? `, ${unread} non lues` : ''}`}
              aria-expanded={showNotif}
              aria-haspopup="dialog"
            >
              <Icon name="bell" />
              {unread > 0 && <span className="notif-badge">{unread}</span>}
            </button>

            {showNotif && (
              <>
                <button
                  className="notif-backdrop"
                  onClick={() => setShowNotif(false)}
                  aria-label="Fermer les notifications"
                />
                <section className="notif-panel" role="dialog" aria-label="Notifications">
                  <div className="notif-header">
                    <span>Notifications</span>
                    {unread > 0 && <span className="notif-unread-count">{unread} nouvelles</span>}
                  </div>
                  {notifications.length === 0 ? (
                    <div className="notif-empty">Aucune notification</div>
                  ) : (
                    notifications.map((n) => (
                      <div key={n.id} className={`notif-item ${n.is_read ? '' : 'unread'}`}>
                        <div className="notif-msg">{n.message}</div>
                        <div className="notif-time">{formatDate(n.created_at)}</div>
                      </div>
                    ))
                  )}
                </section>
              </>
            )}
          </div>

          <div className="user-chip">
            <div className="avatar">{initials(user.name)}</div>
            <div className="user-meta">
              <div className="user-name">{user.name}</div>
              <div className="user-role">{ROLE_LABELS[user.role]}</div>
            </div>
          </div>
        </header>

        <main className="content">
          <Outlet context={{ reloadNotifications: loadNotifications }} />
        </main>
      </div>
    </div>
  );
}
