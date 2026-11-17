import { lazy, Suspense } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import { PageSkeleton } from './components/UI';

const Login = lazy(() => import('./pages/Login'));
const Register = lazy(() => import('./pages/Register'));
const Dashboard = lazy(() => import('./pages/Dashboard'));
const Tickets = lazy(() => import('./pages/Tickets'));
const NewTicket = lazy(() => import('./pages/NewTicket'));
const TicketDetail = lazy(() => import('./pages/TicketDetail'));
const Technicians = lazy(() => import('./pages/Technicians'));

export default function App() {
  return (
    <Suspense fallback={<PageSkeleton variant="route" />}>
      <Routes>
        {/* Pages publiques */}
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        <Route
          element={
            <ProtectedRoute>
              <Layout />
            </ProtectedRoute>
          }
        >
          <Route path="/" element={<Dashboard />} />
          <Route path="/tickets" element={<Tickets />} />
          <Route
            path="/tickets/nouveau"
            element={
              <ProtectedRoute roles={['user', 'manager']}>
                <NewTicket />
              </ProtectedRoute>
            }
          />
          <Route path="/tickets/:id" element={<TicketDetail />} />
          <Route
            path="/techniciens"
            element={
              <ProtectedRoute roles={['manager']}>
                <Technicians />
              </ProtectedRoute>
            }
          />
        </Route>

        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Suspense>
  );
}
