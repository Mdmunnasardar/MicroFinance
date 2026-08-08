import { Navigate, Route, Routes } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import InstallmentsApp from './pages/installments/InstallmentsApp';
import LoginPage from './pages/LoginPage';

export default function InstallmentsStandaloneApp() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <InstallmentsApp defaultTab="collect" />
          </ProtectedRoute>
        }
      />
      <Route path="/collect-payment" element={<Navigate to="/" replace />} />
      <Route path="/installments" element={<Navigate to="/" replace />} />
      <Route path="/dashboard" element={<Navigate to="/" replace />} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
