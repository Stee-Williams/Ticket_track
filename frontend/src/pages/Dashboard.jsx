import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {PieChart, Pie, Cell, ResponsiveContainer, Tooltip, BarChart,
   Bar, XAxis, YAxis, CartesianGrid, Legend, AreaChart, Area,
} from 'recharts';
import client from '../api/client';
import { useAuth } from '../context/AuthContext';
import { Spinner } from '../components/UI';
import { PRIORITY_LABELS } from '../utils/labels';
import '../styles/pages.css';

const STATUS_COLORS = { en_attente: '#f59e0b', en_cours: '#3b82f6', traite: '#22c55e' };
const PRIORITY_COLORS = { basse: '#94a3b8', normale: '#3b82f6', haute: '#f97316', urgente: '#ef4444' };

function StatCard({ icon, color, value, label }) {
  return (
    <div className="card stat-card">
      <div className={`stat-icon ${color}`}>{icon}</div>
      <div>
        <div className="stat-value">{value}</div>
        <div className="stat-label">{label}</div>
      </div>
    </div>
  );
}

export default function Dashboard() {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    client
      .get('/stats')
      .then(({ data }) => setStats(data.data))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <Spinner />;
  if (!stats) return <div className="alert alert-error">Impossible de charger les statistiques.</div>;

  const statusData = [
    { name: 'En attente', value: stats.by_status.en_attente, key: 'en_attente' },
    { name: 'En cours', value: stats.by_status.en_cours, key: 'en_cours' },
    { name: 'Traité', value: stats.by_status.traite, key: 'traite' },
  ].filter((d) => d.value > 0);

  const priorityData = Object.entries(stats.by_priority).map(([key, value]) => ({
    name: PRIORITY_LABELS[key],
    value,
    key,
  }));

  const trendData = stats.trend.map((d) => ({
    day: new Date(d.day).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' }),
    total: d.total,
  }));

  return (
    <div>
      <div className="page-header">
        <div>
          <div className="page-title">Bonjour, {user.name.split(' ')[0]} </div>
          <div className="page-subtitle">Voici un aperçu de l'activité des tickets de pannes.</div>
        </div>
        {(user.role === 'user' || user.role === 'manager') && (
          <Link to="/tickets/nouveau" className="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M12 5v14M5 12h14" strokeLinecap="round" />
            </svg>
            Nouveau ticket
          </Link>
        )}
      </div>

      <div className="stats-grid">
        <StatCard
          color="purple"
          value={stats.total}
          label="Total des tickets"
          icon={<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z" /></svg>}
        />
        <StatCard
          color="amber"
          value={stats.by_status.en_attente}
          label="En attente"
          icon={<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" strokeLinecap="round" /></svg>}
        />
        <StatCard
          color="blue"
          value={stats.by_status.en_cours}
          label="En cours"
          icon={<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 2v4M12 18v4M4.9 4.9l2.9 2.9M16.2 16.2l2.9 2.9M2 12h4M18 12h4" strokeLinecap="round" /></svg>}
        />
        <StatCard
          color="green"
          value={`${stats.resolution_rate}%`}
          label="Taux de résolution"
          icon={<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><path d="M22 4L12 14.01l-3-3" strokeLinecap="round" strokeLinejoin="round" /></svg>}
        />
      </div>

      <div className="charts-grid">
        <div className="card chart-card">
          <div className="chart-title">Tickets créés (7 derniers jours)</div>
          <div className="chart-sub">Évolution des demandes de dépannage</div>
          <ResponsiveContainer width="100%" height={260}>
            <AreaChart data={trendData}>
              <defs>
                <linearGradient id="colorTrend" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.3} />
                  <stop offset="95%" stopColor="#4f46e5" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#eef2f7" vertical={false} />
              <XAxis dataKey="day" tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
              <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
              <Tooltip />
              <Area type="monotone" dataKey="total" stroke="#4f46e5" strokeWidth={2.5} fill="url(#colorTrend)" name="Tickets" />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        <div className="card chart-card">
          <div className="chart-title">Répartition par statut</div>
          <div className="chart-sub">État actuel des tickets</div>
          {statusData.length === 0 ? (
            <p style={{ color: 'var(--text-muted)', textAlign: 'center', padding: 40 }}>Aucune donnée</p>
          ) : (
            <ResponsiveContainer width="100%" height={260}>
              <PieChart>
                <Pie data={statusData} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={55} outerRadius={90} paddingAngle={3}>
                  {statusData.map((entry) => (
                    <Cell key={entry.key} fill={STATUS_COLORS[entry.key]} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend iconType="circle" />
              </PieChart>
            </ResponsiveContainer>
          )}
        </div>
      </div>

      <div className="charts-grid">
        <div className="card chart-card">
          <div className="chart-title">Répartition par priorité</div>
          <div className="chart-sub">Niveau d'urgence des tickets</div>
          <ResponsiveContainer width="100%" height={260}>
            <BarChart data={priorityData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#eef2f7" vertical={false} />
              <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
              <YAxis allowDecimals={false} tick={{ fontSize: 12, fill: '#94a3b8' }} axisLine={false} tickLine={false} />
              <Tooltip cursor={{ fill: '#f8fafc' }} />
              <Bar dataKey="value" name="Tickets" radius={[6, 6, 0, 0]}>
                {priorityData.map((entry) => (
                  <Cell key={entry.key} fill={PRIORITY_COLORS[entry.key]} />
                ))}
              </Bar>
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div className="card chart-card">
          <div className="chart-title">Indicateurs clés</div>
          <div className="chart-sub">Performance de résolution</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 18, marginTop: 10 }}>
            <div className="detail-row">
              <span className="label">Temps moyen de résolution</span>
              <strong>{stats.avg_resolution_hours !== null ? `${stats.avg_resolution_hours} h` : '—'}</strong>
            </div>
            <div className="detail-row">
              <span className="label">Tickets traités</span>
              <strong>{stats.by_status.traite}</strong>
            </div>
            <div className="detail-row">
              <span className="label">Taux de résolution</span>
              <strong style={{ color: 'var(--success)' }}>{stats.resolution_rate}%</strong>
            </div>
            <div>
              <div className="section-label" style={{ marginTop: 4 }}>Par catégorie</div>
              {stats.by_category.length === 0 ? (
                <p style={{ color: 'var(--text-muted)', fontSize: 14 }}>Aucune donnée</p>
              ) : (
                stats.by_category.map((c) => (
                  <div className="detail-row" key={c.category}>
                    <span className="label">{c.category}</span>
                    <strong>{c.total}</strong>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
