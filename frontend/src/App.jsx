import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import Tickets from './pages/Tickets';
import NewTicket from './pages/NewTicket';
import TicketDetail from './pages/TicketDetail';
import Technicians from './pages/Technicians';

export default function App() {
  return (
    <Routes>
      {/* Pages publiques */}
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />

      {/* Pages protégées */}
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
  );
}
