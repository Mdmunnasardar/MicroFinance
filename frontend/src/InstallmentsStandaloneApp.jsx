import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import InstallmentsPage from './pages/InstallmentsPage';
import CollectPaymentPage from './pages/CollectPaymentPage';
import LoginPage from './pages/LoginPage';

export default function InstallmentsStandaloneApp() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <InstallmentsPage />
          </ProtectedRoute>
        }
      />
      <Route
        path="/collect-payment"
        element={
          <ProtectedRoute>
            <CollectPaymentPage />
          </ProtectedRoute>
        }
      />
      <Route path="/installments" element={<Navigate to="/" replace />} />
      <Route path="/dashboard" element={<Navigate to="/" replace />} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
