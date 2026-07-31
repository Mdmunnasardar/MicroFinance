import { Routes, Route, Navigate } from 'react-router-dom';
import AppLayout from './layouts/AppLayout';
import InstallmentsListPage from './pages/InstallmentsListPage';
import PaymentPage from './pages/PaymentPage';
import PaymentListPage from './pages/PaymentListPage';
import OverduePage from './pages/OverduePage';
import DueListPage from './pages/DueListPage';
import ReportPage from './pages/ReportPage';

export default function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<InstallmentsListPage />} />
        <Route path="payment" element={<PaymentPage />} />
        <Route path="payment-list" element={<PaymentListPage />} />
        <Route path="overdue" element={<OverduePage />} />
        <Route path="due-list" element={<DueListPage />} />
        <Route path="report" element={<ReportPage />} />
        <Route path="*" element={<Navigate to="." replace />} />
      </Route>
    </Routes>
  );
}