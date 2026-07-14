// Libellés et correspondances d'affichage centralisés.

export const STATUS_LABELS = {
  en_attente: 'En attente',
  en_cours: 'En cours',
  traite: 'Traité',
};

export const STATUS_CLASS = {
  en_attente: 'badge-attente',
  en_cours: 'badge-cours',
  traite: 'badge-traite',
};

export const PRIORITY_LABELS = {
  basse: 'Basse',
  normale: 'Normale',
  haute: 'Haute',
  urgente: 'Urgente',
};

export const PRIORITY_CLASS = {
  basse: 'badge-basse',
  normale: 'badge-normale',
  haute: 'badge-haute',
  urgente: 'badge-urgente',
};

export const ROLE_LABELS = {
  user: 'Client',
  technician: 'Technicien',
  manager: 'Gestionnaire',
};

export function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr.replace(' ', 'T'));
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function initials(name = '') {
  return name
    .split(' ')
    .map((n) => n[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase();
}
